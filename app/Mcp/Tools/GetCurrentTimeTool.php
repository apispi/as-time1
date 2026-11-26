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
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_current_time')]
#[Description('Get the current date and time in a given IANA timezone (default UTC), formatted as ISO 8601.')]
#[IsReadOnly]
class GetCurrentTimeTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request, TimeService $time): Response
    {
        try {
            return Response::json($time->now((string) $request->get('timezone', 'UTC')));
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
            'timezone' => $schema->string()
                ->description("IANA timezone identifier, e.g. 'America/New_York'. Defaults to UTC.")
                ->default('UTC'),
        ];
    }
}
