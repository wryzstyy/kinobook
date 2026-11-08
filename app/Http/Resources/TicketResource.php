<?php

namespace App\Http\Resources;

use App\Enums\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $screening = $this->screening;
        $endsAt = $screening->starts_at->copy()->addMinutes($screening->movie->duration_minutes);

        // active - можно пройти, used - уже использован, expired - сеанс закончился
        $state = match (true) {
            $this->status === TicketStatus::Used => 'used',
            $endsAt->isPast()                    => 'expired',
            default                              => 'active',
        };

        return [
            'code'    => $this->code,
            'state'   => $state,
            'used_at' => $this->used_at?->toIso8601String(),
            'row'     => $this->seat->row_number,
            'seat'    => $this->seat->seat_number,
            'price'   => $screening->price,
            'session' => [
                'id'        => $screening->id,
                'starts_at' => $screening->starts_at->toIso8601String(),
                'ends_at'   => $endsAt->toIso8601String(),
                'movie'     => [
                    'id'               => $screening->movie->id,
                    'title'            => $screening->movie->title,
                    'poster_url'       => $screening->movie->poster_url,
                    'age_rating'       => $screening->movie->age_rating,
                    'duration_minutes' => $screening->movie->duration_minutes,
                ],
                'hall'      => [
                    'id'   => $screening->hall->id,
                    'name' => $screening->hall->name,
                ],
            ],
        ];
    }
}