<?php

namespace App\Services;

use App\Contracts\Observability\CacheObservabilityInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class LogCacheObservability implements CacheObservabilityInterface
{
    public function recordHit(string $cacheName): void
    {
        $this->increment($cacheName, 'hits');
        Log::debug('cache.telemetry', ['cache' => $cacheName, 'outcome' => 'hit']);
    }

    public function recordMiss(string $cacheName): void
    {
        $this->increment($cacheName, 'misses');
        Log::debug('cache.telemetry', ['cache' => $cacheName, 'outcome' => 'miss']);
    }

    public function snapshot(string $cacheName): array
    {
        return [
            'hits' => (int) Cache::get($this->counterKey($cacheName, 'hits'), 0),
            'misses' => (int) Cache::get($this->counterKey($cacheName, 'misses'), 0),
        ];
    }

    private function increment(string $cacheName, string $outcome): void
    {
        $key = $this->counterKey($cacheName, $outcome);
        Cache::add($key, 0, now()->addDay());
        Cache::increment($key);
    }

    private function counterKey(string $cacheName, string $outcome): string
    {
        return "lms:cache-observability:{$cacheName}:{$outcome}";
    }
}
