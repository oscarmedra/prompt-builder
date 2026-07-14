<?php

namespace NoahMedra\PromptBuilder\History;

/**
 * A conversation history store: an ordered list of {role, content}
 * messages that survives across PromptBuilder invocations.
 *
 * The interface is deliberately tiny and framework-free so the default
 * implementation (InMemoryHistoryStore) works everywhere — CLI scripts,
 * tests, queue workers — with no filesystem or session dependency. A
 * Laravel-backed implementation (History\Laravel\CacheHistoryStore) is
 * offered separately for callers who want persistence across requests.
 */
interface HistoryStoreInterface
{
    /** @return array<int, array{role: string, content: string}> */
    public function all(): array;

    public function push(string $role, string $content): void;

    public function clear(): void;
}
