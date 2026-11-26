<?php

namespace App\Mcp\Tools;

use App\Services\TimeService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_timezones')]
#[Description('Find valid IANA timezone identifiers, e.g. search "sydney" → "Australia/Sydney". Use this when unsure of the exact identifier another tool needs.')]
#[IsReadOnly]
#[IsIdempotent]
class ListTimezonesTool extends Tool
{
    public function handle(Request $request, TimeService $time): Response
    {
        $search = $request->get('search');
        $timezones = $time->timezones(is_string($search) ? $search : null);

        if ($timezones === []) {
            return Response::error("No timezone matches [{$search}]. Try a major city in the same zone, or a region such as 'Europe'.");
        }

        return Response::json(['count' => count($timezones), 'timezones' => $timezones]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()
                ->description("Part of a city or region name, case-insensitive, e.g. 'new york' or 'Europe'. Omit to list all."),
        ];
    }
}
