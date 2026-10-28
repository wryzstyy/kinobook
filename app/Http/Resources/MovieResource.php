<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'age_rating'       => $this->age_rating,
            'poster_url'       => $this->poster_url,
            'genres'           => GenreResource::collection($this->whenLoaded('genres')),
            'sessions'         => ScreeningResource::collection($this->whenLoaded('screenings')),
        ];
    }
}