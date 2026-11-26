<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Daily request counts for the public endpoints (REST API and MCP), kept in
 * the cache — no database writes on the hot path, and nothing about the
 * caller is recorded, only the total per day. Counts are approximate under
 * heavy concurrency on the file cache, which is fine for a trend line.
 */
class UsageCounter
{
    public const KINDS = ['api', 'mcp'];

    private const KEEP_DAYS = 35;

    public function record(string $kind): void
    {
        $key = $this->key($kind, Carbon::today());

        Cache::add($key, 0, now()->addDays(self::KEEP_DAYS));
        Cache::increment($key);
    }

    /**
     * @return list<array{date: string, api: int, mcp: int}> oldest first
     */
    public function daily(int $days = 14): array
    {
        $rows = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $row = ['date' => $day->toDateString()];
            foreach (self::KINDS as $kind) {
                $row[$kind] = (int) Cache::get($this->key($kind, $day), 0);
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function key(string $kind, Carbon $day): string
    {
        return "usage:{$kind}:{$day->toDateString()}";
    }
}
