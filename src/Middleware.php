<?php

namespace A17\Localization;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Localization::setRequest($request);

        return $next($request);
    }
}
