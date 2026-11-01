<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHallRequest;
use App\Http\Requests\Admin\UpdateHallRequest;
use App\Http\Resources\HallResource;
use App\Models\Hall;
use App\Models\Seat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HallController extends Controller
{
    private function generateSeats(Hall $hall): void
    {
        $now = now();
        $rows = [];

        for ($r = 1; $r <= $hall->rows_count; $r++) {
            for ($s = 1; $s <= $hall->seats_per_row; $s++) {
                $rows[] = [
                    'hall_id'     => $hall->id,
                    'row_number'  => $r,
                    'seat_number' => $s,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        Seat::insert($rows);
    }

    public function index()
    {
        return HallResource::collection(Hall::orderBy('id')->get());
    }

    public function show(Hall $hall)
    {
        return new HallResource($hall);
    }

    public function store(StoreHallRequest $request): JsonResponse
    {
        $data = $request->validated();

        $hall = DB::transaction(function () use ($data) {
            $hall = Hall::create($data);
            $this->generateSeats($hall);

            return $hall;
        });

        return (new HallResource($hall))->response()->setStatusCode(201);
    }

    public function update(UpdateHallRequest $request, Hall $hall)
    {
        $data = $request->validated();

        $resize = (isset($data['rows_count']) && $data['rows_count'] != $hall->rows_count)
            || (isset($data['seats_per_row']) && $data['seats_per_row'] != $hall->seats_per_row);

        if ($resize && $hall->screenings()->exists()) {
            abort(409, 'В зале есть сеансы, менять его размер нельзя.');
        }

        DB::transaction(function () use ($hall, $data, $resize) {
            $hall->update($data);

            if ($resize) {
                $hall->seats()->delete();
                $this->generateSeats($hall);
            }
        });

        return new HallResource($hall);
    }

    public function destroy(Hall $hall): JsonResponse
    {
        if ($hall->screenings()->exists()) {
            abort(409, 'В зале есть сеансы, удалить его нельзя.');
        }

        $hall->delete(); // места удалятся каскадом

        return response()->json(['message' => 'Зал удалён.']);
    }
}