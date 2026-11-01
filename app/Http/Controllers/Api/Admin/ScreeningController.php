<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScreeningResource;
use App\Models\Movie;
use App\Models\Screening;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ScreeningController extends Controller
{
    private function rules(bool $update): array
    {
        $req = $update ? ['sometimes', 'required'] : ['required'];

        return [
            'movie_id'  => [...$req, 'integer', 'exists:movies,id'],
            'hall_id'   => [...$req, 'integer', 'exists:halls,id'],
            'starts_at' => [...$req, 'date', 'after:now'],
            'price'     => [...$req, 'numeric', 'gt:0', 'max:99999'],
        ];
    }

    // В зале не должно быть двух сеансов, пересекающихся по времени
    private function assertNoOverlap(int $hallId, int $movieId, Carbon $start, ?int $ignoreId = null): void
    {
        $end = $start->copy()->addMinutes(Movie::findOrFail($movieId)->duration_minutes);

        $conflict = Screening::with('movie')
            ->where('hall_id', $hallId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereBetween('starts_at', [$start->copy()->subDay(), $end])
            ->get()
            ->contains(fn ($s) => $s->starts_at->lt($end)
                && $s->starts_at->copy()->addMinutes($s->movie->duration_minutes)->gt($start));

        if ($conflict) {
            throw ValidationException::withMessages([
                'starts_at' => ['В этом зале в это время уже идёт другой сеанс.'],
            ]);
        }
    }

    // Все сеансы, включая прошедшие
    public function index(Request $request)
    {
        $data = $request->validate([
            'date'     => ['nullable', 'date_format:Y-m-d'],
            'movie_id' => ['nullable', 'integer', 'exists:movies,id'],
            'hall_id'  => ['nullable', 'integer', 'exists:halls,id'],
        ]);

        $screenings = Screening::with(['movie', 'hall'])
            ->withCount('tickets')
            ->when($data['date'] ?? null, fn ($q, $d) => $q->whereDate('starts_at', $d))
            ->when($data['movie_id'] ?? null, fn ($q, $id) => $q->where('movie_id', $id))
            ->when($data['hall_id'] ?? null, fn ($q, $id) => $q->where('hall_id', $id))
            ->orderBy('starts_at')
            ->paginate(20);

        return ScreeningResource::collection($screenings);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(false));

        $this->assertNoOverlap($data['hall_id'], $data['movie_id'], Carbon::parse($data['starts_at']));

        $screening = Screening::create($data);

        return (new ScreeningResource($screening->load(['movie', 'hall'])->loadCount('tickets')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Screening $screening)
    {
        $data = $request->validate($this->rules(true));

        if ($screening->tickets()->exists()) {
            $changed = (isset($data['hall_id']) && (int) $data['hall_id'] !== $screening->hall_id)
                || (isset($data['movie_id']) && (int) $data['movie_id'] !== $screening->movie_id)
                || (isset($data['price']) && (float) $data['price'] !== (float) $screening->price);

            if ($changed) {
                abort(409, 'На сеанс проданы билеты: менять зал, фильм и цену нельзя.');
            }
        }

        $this->assertNoOverlap(
            $data['hall_id'] ?? $screening->hall_id,
            $data['movie_id'] ?? $screening->movie_id,
            isset($data['starts_at']) ? Carbon::parse($data['starts_at']) : $screening->starts_at,
            $screening->id
        );

        $screening->update($data);

        return new ScreeningResource($screening->fresh(['movie', 'hall'])->loadCount('tickets'));
    }

    public function destroy(Screening $screening): JsonResponse
    {
        if ($screening->tickets()->exists()) {
            abort(409, 'На сеанс проданы билеты, удалить его нельзя.');
        }

        $screening->delete();

        return response()->json(['message' => 'Сеанс удалён.']);
    }
}