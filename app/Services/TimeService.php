<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use InvalidArgumentException;

/**
 * The time logic behind both the MCP tools and the REST API, so the two
 * transports cannot drift apart. Invalid input raises InvalidArgumentException
 * with a message that is safe to show to the caller.
 */
class TimeService
{
    public const UNITS = ['seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years'];

    /**
     * @return array<string, mixed>
     */
    public function now(string $timezone = 'UTC'): array
    {
        $this->assertTimezone($timezone);

        $now = CarbonImmutable::now($timezone);

        return [
            'timezone' => $timezone,
            'datetime' => $now->toIso8601String(),
            'unix_timestamp' => $now->getTimestamp(),
            'utc_offset' => $now->format('P'),
            'is_dst' => (bool) $now->format('I'),
            'day_of_week' => $now->format('l'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function convert(string $time, string $fromTz, string $toTz): array
    {
        $this->assertTimezone($fromTz);
        $this->assertTimezone($toTz);

        $source = $this->parse($time, $fromTz);
        $converted = $source->setTimezone($toTz);

        return [
            'source' => [
                'timezone' => $fromTz,
                'datetime' => $source->toIso8601String(),
                'is_dst' => (bool) $source->format('I'),
            ],
            'converted' => [
                'timezone' => $toTz,
                'datetime' => $converted->toIso8601String(),
                'is_dst' => (bool) $converted->format('I'),
            ],
            'offset_difference' => sprintf('%+g hours', ($converted->utcOffset() - $source->utcOffset()) / 60),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function difference(string $start, string $end, ?string $unit = null): array
    {
        if ($unit !== null && ! in_array($unit, self::UNITS, true)) {
            throw new InvalidArgumentException("Unknown unit [{$unit}]. Use one of: ".implode(', ', self::UNITS).'.');
        }

        $from = $this->parse($start);
        $to = $this->parse($end);

        $result = [
            'start' => $from->toIso8601String(),
            'end' => $to->toIso8601String(),
            'human' => $from->diffForHumans($to, ['syntax' => CarbonImmutable::DIFF_ABSOLUTE, 'parts' => 3]),
            'iso8601_duration' => $from->diff($to)->format('%rP%yY%mM%dDT%hH%iM%sS'),
            'total_seconds' => $to->getTimestamp() - $from->getTimestamp(),
        ];

        if ($unit !== null) {
            $result[$unit] = $from->{'diffIn'.ucfirst($unit)}($to);
        }

        return $result;
    }

    /**
     * Every accepted IANA identifier, optionally narrowed to those containing
     * $search (case-insensitive; spaces match underscores, so "new york"
     * finds America/New_York).
     *
     * @return list<string>
     */
    public function timezones(?string $search = null): array
    {
        $all = timezone_identifiers_list();
        $needle = str_replace(' ', '_', trim((string) $search));

        if ($needle === '') {
            return $all;
        }

        return array_values(array_filter($all, fn (string $tz) => stripos($tz, $needle) !== false));
    }

    private function assertTimezone(string $timezone): void
    {
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new InvalidArgumentException("Unknown timezone [{$timezone}]. Use an IANA identifier such as 'UTC', 'America/New_York', or 'Australia/Sydney'.");
        }
    }

    private function parse(string $time, ?string $timezone = null): CarbonImmutable
    {
        try {
            return CarbonImmutable::parse($time, $timezone);
        } catch (InvalidFormatException) {
            throw new InvalidArgumentException("Could not parse time [{$time}]. Use an ISO 8601 or 'YYYY-MM-DD HH:MM:SS' format.");
        }
    }
}
