<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // Страница билета по коду (доступна без входа)
    public function show(string $code)
    {
        $ticket = Ticket::where('code', Str::upper($code))
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid->value)) // неоплаченные не показываем
            ->with(['seat', 'screening.movie', 'screening.hall'])
            ->firstOrFail();

        return new TicketResource($ticket);
    }
}