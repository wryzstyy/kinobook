<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // Отметка на входе: билет становится «использованным»
    public function checkIn(string $code)
    {
        $ticket = DB::transaction(function () use ($code) {
            // Блокируем строку билета: два контролёра не смогут пропустить один билет дважды
            $ticket = Ticket::where('code', Str::upper($code))
                ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Paid->value))
                ->lockForUpdate()
                ->firstOrFail();

            if ($ticket->status === TicketStatus::Used) {
                abort(409, 'Билет уже использован'.($ticket->used_at ? ' '.$ticket->used_at->format('d.m.Y H:i') : '').'.');
            }

            $ticket->load(['seat', 'screening.movie', 'screening.hall']);

            $screening = $ticket->screening;
            $endsAt = $screening->starts_at->copy()->addMinutes($screening->movie->duration_minutes);

            if ($endsAt->isPast()) {
                abort(422, 'Сеанс уже закончился, билет недействителен.');
            }

            $ticket->update([
                'status'  => TicketStatus::Used,
                'used_at' => now(),
            ]);

            return $ticket;
        });

        return new TicketResource($ticket);
    }
}