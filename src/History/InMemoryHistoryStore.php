<?php

namespace NoahMedra\PromptBuilder\History;

/**
 * Default history store: keeps the conversation in a PHP array for the
 * lifetime of the process. Zero external dependencies, so it is safe to
 * use as the default in the framework-free composition layer.
 *
 * Persistence across requests is out of scope here — use
 * History\Laravel\CacheHistoryStore for that.
 */
class InMemoryHistoryStore implements HistoryStoreInterface
{
    /** @param array<int, array{role: string, content: string}> $messages */
    public function __construct(private array $messages = [])
    {
    }

    /** @return array<int, array{role: string, content: string}> */
    public function all(): array
    {
        return $this->messages;
    }

    public function push(string $role, string $content): void
    {
        $this->messages[] = ['role' => $role, 'content' => $content];
    }

    public function clear(): void
    {
        $this->messages = [];
    }
}
