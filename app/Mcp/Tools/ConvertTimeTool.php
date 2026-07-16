<?php

namespace App\Mcp\Tools;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('convert_time')]
#[Description('Convert a timestamp from one IANA timezone to another, with daylight-saving awareness.')]
class ConvertTimeTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'time' => ['required', 'string'],
            'from_tz' => ['required', 'string', 'timezone:all'],
            'to_tz' => ['required', 'string', 'timezone:all'],
        ]);

        try {
            $source = CarbonImmutable::parse($validated['time'], $validated['from_tz']);
        } catch (InvalidFormatException) {
            return Response::error("Could not parse time [{$validated['time']}]. Use an ISO 8601 or 'YYYY-MM-DD HH:MM:SS' format.");
        }

        $converted = $source->setTimezone($validated['to_tz']);

        return Response::json([
            'source' => [
                'timezone' => $validated['from_tz'],
                'datetime' => $source->toIso8601String(),
                'is_dst' => (bool) $source->format('I'),
            ],
            'converted' => [
                'timezone' => $validated['to_tz'],
                'datetime' => $converted->toIso8601String(),
                'is_dst' => (bool) $converted->format('I'),
            ],
            'offset_difference' => sprintf('%+g hours', ($converted->utcOffset() - $source->utcOffset()) / 60),
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
            'time' => $schema->string()
                ->description("The timestamp to convert, e.g. '2026-07-17 09:30:00' or ISO 8601.")
                ->required(),
            'from_tz' => $schema->string()
                ->description("Source IANA timezone, e.g. 'Australia/Sydney'.")
                ->required(),
            'to_tz' => $schema->string()
                ->description("Target IANA timezone, e.g. 'America/New_York'.")
                ->required(),
        ];
    }
}
