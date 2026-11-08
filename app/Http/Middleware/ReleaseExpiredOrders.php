<?php

namespace App\Http\Middleware;

use App\Models\Order;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReleaseExpiredOrders
{
    public function handle(Request $request, Closure $next): Response
    {
        Order::releaseExpired();

        return $next($request);
    }
}