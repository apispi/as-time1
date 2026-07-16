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

#[Name('time_difference')]
#[Description('Compute the elapsed duration between two timestamps, in human-readable and machine formats.')]
class TimeDifferenceTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'start' => ['required', 'string'],
            'end' => ['required', 'string'],
            'unit' => ['sometimes', 'string', 'in:seconds,minutes,hours,days,weeks,months,years'],
        ]);

        try {
            $start = CarbonImmutable::parse($validated['start']);
            $end = CarbonImmutable::parse($validated['end']);
        } catch (InvalidFormatException $e) {
            return Response::error("Could not parse timestamp: {$e->getMessage()}. Use an ISO 8601 or 'YYYY-MM-DD HH:MM:SS' format.");
        }

        $result = [
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'human' => $start->diffForHumans($end, ['syntax' => CarbonImmutable::DIFF_ABSOLUTE, 'parts' => 3]),
            'iso8601_duration' => $start->diff($end)->format('%rP%yY%mM%dDT%hH%iM%sS'),
            'total_seconds' => $end->getTimestamp() - $start->getTimestamp(),
        ];

        if (isset($validated['unit'])) {
            $unit = $validated['unit'];
            $result[$unit] = $start->{'diffIn'.ucfirst($unit)}($end);
        }

        return Response::json($result);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'start' => $schema->string()
                ->description('The starting timestamp, ISO 8601 preferred. Include an offset or timezone for accuracy.')
                ->required(),
            'end' => $schema->string()
                ->description('The ending timestamp, ISO 8601 preferred.')
                ->required(),
            'unit' => $schema->string()
                ->description('Optionally express the total difference in a single unit.')
                ->enum(['seconds', 'minutes', 'hours', 'days', 'weeks', 'months', 'years']),
        ];
    }
}
