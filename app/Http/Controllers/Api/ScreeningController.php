<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScreeningResource;
use App\Models\Screening;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScreeningController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'date'     => ['nullable', 'date_format:Y-m-d'],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
            'hall_id'  => ['nullable', 'integer', 'exists:halls,id'],
        ]);

        $screenings = Screening::with(['movie.genres', 'hall'])
            ->withCount('tickets')
            ->when(
                $data['date'] ?? null,
                fn ($q, $date) => $q->whereDate('starts_at', $date),
                fn ($q) => $q->where('starts_at', '>', now()) // без даты: только будущие
            )
            ->when($data['movie_id'] ?? null, fn ($q, $id) => $q->where('movie_id', $id))
            ->when($data['hall_id'] ?? null, fn ($q, $id) => $q->where('hall_id', $id))
            ->orderBy('starts_at')
            ->get();

        return ScreeningResource::collection($screenings);
    }

    public function show(Screening $screening)
    {
        $screening->load(['movie.genres', 'hall'])->loadCount('tickets');

        return new ScreeningResource($screening);
    }

    public function seats(Screening $screening): JsonResponse
    {
        $screening->load('hall');

        // id мест, уже купленных на этот сеанс
        $taken = $screening->tickets()->pluck('seat_id')->flip();

        $rows = Seat::where('hall_id', $screening->hall_id)
            ->orderBy('row_number')
            ->orderBy('seat_number')
            ->get()
            ->groupBy('row_number')
            ->map(fn ($seats, $row) => [
                'row'   => (int) $row,
                'seats' => $seats->map(fn ($seat) => [
                    'id'       => $seat->id,
                    'number'   => $seat->seat_number,
                    'is_taken' => $taken->has($seat->id),
                ])->values(),
            ])
            ->values();

        return response()->json([
            'session_id' => $screening->id,
            'starts_at'  => $screening->starts_at->toIso8601String(),
            'price'      => $screening->price,
            'hall'       => [
                'id'            => $screening->hall->id,
                'name'          => $screening->hall->name,
                'rows_count'    => $screening->hall->rows_count,
                'seats_per_row' => $screening->hall->seats_per_row,
            ],
            'rows' => $rows,
        ]);
    }
}