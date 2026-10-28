<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $tickets = $this->tickets;
        $screening = $tickets->first()?->screening;

        return [
            'id'            => $this->id,
            'created_at'    => $this->created_at->toIso8601String(),
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
            ])->values(),
            'tickets_count' => $tickets->count(),
            'total'         => $screening
                ? number_format($screening->price * $tickets->count(), 2, '.', '')
                : '0.00',
            // можно ли ещё отменить (не позднее чем за 60 минут до начала)
            'can_cancel'    => $screening
                ? $screening->starts_at->gte(now()->addMinutes(60))
                : false,
        ];
    }
}