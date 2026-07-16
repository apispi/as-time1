<?php

use App\Mcp\Servers\TimeServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', TimeServer::class);
