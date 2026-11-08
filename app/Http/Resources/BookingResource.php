<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tickets = $this->tickets;
        $screening = $tickets->first()?->screening;
        $paid = $this->status === OrderStatus::Paid;
        $anyUsed = $tickets->contains(fn ($t) => $t->status === TicketStatus::Used);

        return [
            'id'            => $this->id,
            'status'        => $this->status->value, // pending | paid
            'created_at'    => $this->created_at->toIso8601String(),
            'paid_at'       => $this->paid_at?->toIso8601String(),
            'expires_at'    => $this->expires_at?->toIso8601String(), // до этого момента места удерживаются для оплаты
            'session'       => $screening ? [
                'id'        => $screening->id,
                'starts_at' => $screening->starts_at->toIso8601String(),
                'price'     => $screening->price,
                'movie'     => [
                    'id'         => $screening->movie->id,
                    'title'      => $screening->movie->title,
                    'poster_url' => $screening->movie->poster_url,
                ],
                'hall'      => [
                    'id'   => $screening->hall->id,
                    'name' => $screening->hall->name,
                ],
            ] : null,
            'tickets'       => $tickets->map(fn ($t) => [
                'id'      => $t->id,
                'seat_id' => $t->seat_id,
                'row'     => $t->seat->row_number,
                'seat'    => $t->seat->seat_number,
                'code'    => $paid ? $t->code : null, // код выдаётся только после оплаты
                'status'  => $t->status->value,       // active | used
                'used_at' => $t->used_at?->toIso8601String(),
            ])->values(),
            'tickets_count' => $tickets->count(),
            'total'         => $screening
                ? number_format($screening->price * $tickets->count(), 2, '.', '')
                : '0.00',
            'can_pay'       => ! $paid && $this->expires_at !== null && $this->expires_at->isFuture(),
            // неоплаченную можно отменить всегда, оплаченную не позднее чем за 60 минут до начала, использованную нельзя
            'can_cancel'    => match (true) {
                $anyUsed => false,
                $paid    => $screening ? $screening->starts_at->gte(now()->addMinutes(60)) : false,
                default  => true,
            },
        ];
    }
}