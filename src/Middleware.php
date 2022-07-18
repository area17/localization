<?php

namespace A17\Localization;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class Middleware
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse|JsonResponse
    {
        Localization::setRequest($request);

        return $next($request);
    }
}
