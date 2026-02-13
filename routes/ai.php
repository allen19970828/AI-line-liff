<?php

use App\Mcp\Servers\PublicServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/public', PublicServer::class);
Mcp::local('public', PublicServer::class);
