<?php

namespace App\Contracts\Observability;

interface CacheObservabilityInterface
{
    public function recordHit(string $cacheName): void;

    public function recordMiss(string $cacheName): void;

    /** @return array{hits: int, misses: int} */
    public function snapshot(string $cacheName): array;
}
