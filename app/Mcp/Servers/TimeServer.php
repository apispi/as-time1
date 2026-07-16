<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\ConvertTimeTool;
use App\Mcp\Tools\GetCurrentTimeTool;
use App\Mcp\Tools\TimeDifferenceTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('MCP Time Service')]
#[Version('1.0.0')]
#[Instructions('Provides accurate current time, timezone conversion, and duration calculations. Timezones use IANA identifiers (e.g. "Australia/Sydney"); timestamps are ISO 8601.')]
class TimeServer extends Server
{
    protected array $tools = [
        GetCurrentTimeTool::class,
        ConvertTimeTool::class,
        TimeDifferenceTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        //
    ];
}
