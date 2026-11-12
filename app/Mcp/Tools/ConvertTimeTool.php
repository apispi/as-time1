<?php

namespace App\Mcp\Tools;

use App\Services\TimeService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
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
    public function handle(Request $request, TimeService $time): Response
    {
        $validated = $request->validate([
            'time' => ['required', 'string'],
            'from_tz' => ['required', 'string', 'timezone:all'],
            'to_tz' => ['required', 'string', 'timezone:all'],
        ]);

        try {
            return Response::json($time->convert($validated['time'], $validated['from_tz'], $validated['to_tz']));
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        }
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
