<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OccupancyReportRequest;
use App\Models\Hall;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function occupancy(OccupancyReportRequest $request): JsonResponse
    {
        $data = $request->validated();

        $halls = Hall::query()
            ->when($data['hall_id'] ?? null, fn ($q, $id) => $q->where('id', $id))
            ->with(['screenings' => function ($q) use ($data) {
                $q->when($data['date_from'] ?? null, fn ($q, $d) => $q->whereDate('starts_at', '>=', $d))
                    ->when($data['date_to'] ?? null, fn ($q, $d) => $q->whereDate('starts_at', '<=', $d))
                    ->with('movie:id,title')
                    ->withCount('tickets')
                    ->orderBy('starts_at');
            }])
            ->orderBy('id')
            ->get();

        $report = $halls->map(function ($hall) {
            $capacity = $hall->rows_count * $hall->seats_per_row;
            $sold = $hall->screenings->sum('tickets_count');
            $total = $capacity * $hall->screenings->count();

            return [
                'hall_id'        => $hall->id,
                'hall'           => $hall->name,
                'sessions_count' => $hall->screenings->count(),
                'tickets_sold'   => $sold,
                'capacity_total' => $total,
                'fill_percent'   => $total ? round($sold * 100 / $total, 1) : 0,
                'revenue'        => number_format(
                    $hall->screenings->sum(fn ($s) => $s->tickets_count * $s->price), 2, '.', ''
                ),
                'sessions'       => $hall->screenings->map(fn ($s) => [
                    'session_id'   => $s->id,
                    'movie'        => $s->movie->title,
                    'starts_at'    => $s->starts_at->toIso8601String(),
                    'sold'         => $s->tickets_count,
                    'capacity'     => $capacity,
                    'fill_percent' => round($s->tickets_count * 100 / $capacity, 1),
                ])->values(),
            ];
        })->values();

        $totalSold = $report->sum('tickets_sold');
        $totalCapacity = $report->sum('capacity_total');

        return response()->json([
            'filters' => $data,
            'total'   => [
                'tickets_sold'   => $totalSold,
                'capacity_total' => $totalCapacity,
                'fill_percent'   => $totalCapacity ? round($totalSold * 100 / $totalCapacity, 1) : 0,
            ],
            'data'    => $report,
        ]);
    }
}