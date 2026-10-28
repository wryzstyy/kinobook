<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreeningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hallLoaded = $this->relationLoaded('hall');
        $total = $hallLoaded ? $this->hall->rows_count * $this->hall->seats_per_row : null;

        return [
            'id'          => $this->id,
            'starts_at'   => $this->starts_at->toIso8601String(),
            'price'       => $this->price,
            'movie'       => new MovieResource($this->whenLoaded('movie')),
            'hall'        => new HallResource($this->whenLoaded('hall')),
            'seats_total' => $this->when($hallLoaded, $total),
            'seats_free'  => $this->when(
                $hallLoaded && isset($this->tickets_count),
                fn () => $total - $this->tickets_count
            ),
        ];
    }
}