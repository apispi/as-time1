<?php

namespace App\Mcp\Tools;

use App\Services\TimeService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('time_difference')]
#[Description('Compute the elapsed duration between two timestamps, in human-readable and machine formats.')]
#[IsReadOnly]
#[IsIdempotent]
class TimeDifferenceTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, TimeService $time): Response
    {
        $validated = $request->validate([
            'start' => ['required', 'string'],
            'end' => ['required', 'string'],
            'unit' => ['sometimes', 'string', Rule::in(TimeService::UNITS)],
        ]);

        try {
            return Response::json($time->difference($validated['start'], $validated['end'], $validated['unit'] ?? null));
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
            'start' => $schema->string()
                ->description('The starting timestamp, ISO 8601 preferred. Include an offset or timezone for accuracy.')
                ->required(),
            'end' => $schema->string()
                ->description('The ending timestamp, ISO 8601 preferred.')
                ->required(),
            'unit' => $schema->string()
                ->description('Optionally express the total difference in a single unit.')
                ->enum(TimeService::UNITS),
        ];
    }
}
