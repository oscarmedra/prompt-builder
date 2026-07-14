<?php

namespace NoahMedra\PromptBuilder\History\Laravel;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use NoahMedra\PromptBuilder\History\HistoryStoreInterface;

/**
 * Optional, Laravel-only history store that persists a conversation in
 * the cache so it survives across HTTP requests / jobs.
 *
 * This lives under History\Laravel on purpose: it is the ONLY history
 * class that touches the framework. The composition layer never references
 * it — PromptBuilder type-hints HistoryStoreInterface and defaults to the
 * framework-free InMemoryHistoryStore.
 */
class CacheHistoryStore implements HistoryStoreInterface
{
    public function __construct(
        private readonly string $conversationId,
        private readonly ?Repository $cache = null,
        private readonly int $ttlSeconds = 3600,
    ) {
    }

    /** @return array<int, array{role: string, content: string}> */
    public function all(): array
    {
        return $this->cache()->get($this->key(), []);
    }

    public function push(string $role, string $content): void
    {
        $messages = $this->all();
        $messages[] = ['role' => $role, 'content' => $content];
        $this->cache()->put($this->key(), $messages, $this->ttlSeconds);
    }

    public function clear(): void
    {
        $this->cache()->forget($this->key());
    }

    private function cache(): Repository
    {
        return $this->cache ?? Cache::store();
    }

    private function key(): string
    {
        return 'promptbuilder:history:' . $this->conversationId;
    }
}
