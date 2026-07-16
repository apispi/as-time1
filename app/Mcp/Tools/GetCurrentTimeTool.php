<?php

namespace App\Mcp\Tools;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('get_current_time')]
#[Description('Get the current date and time in a given IANA timezone (default UTC), formatted as ISO 8601.')]
class GetCurrentTimeTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $timezone = $request->get('timezone', 'UTC');

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            return Response::error("Unknown timezone [{$timezone}]. Use an IANA identifier such as 'UTC', 'America/New_York', or 'Australia/Sydney'.");
        }

        $now = CarbonImmutable::now($timezone);

        return Response::json([
            'timezone' => $timezone,
            'datetime' => $now->toIso8601String(),
            'unix_timestamp' => $now->getTimestamp(),
            'utc_offset' => $now->format('P'),
            'is_dst' => (bool) $now->format('I'),
            'day_of_week' => $now->format('l'),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'timezone' => $schema->string()
                ->description("IANA timezone identifier, e.g. 'America/New_York'. Defaults to UTC.")
                ->default('UTC'),
        ];
    }
}
