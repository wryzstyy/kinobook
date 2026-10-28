<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Order;
use App\Models\Screening;
use App\Models\Seat;
use App\Models\Ticket;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    private const WITH = ['tickets.seat', 'tickets.screening.movie', 'tickets.screening.hall'];

    // Мои брони
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->with(self::WITH)
            ->latest()
            ->get();

        return BookingResource::collection($orders);
    }

    // Одна моя бронь (чужая даст 404)
    public function show(Request $request, int $id)
    {
        $order = $request->user()->orders()->with(self::WITH)->findOrFail($id);

        return new BookingResource($order);
    }

    // Купить билеты
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'   => ['required', 'integer', 'exists:screenings,id'],
            'seat_ids'     => ['required', 'array', 'min:1', 'max:6'],
            'seat_ids.*'   => ['integer', 'distinct'],
        ]);

        try {
            $order = DB::transaction(function () use ($data, $request) {
                // Блокируем строку сеанса: параллельные покупки на этот сеанс идут по очереди
                $screening = Screening::whereKey($data['session_id'])->lockForUpdate()->firstOrFail();

                if ($screening->starts_at->isPast()) {
                    throw ValidationException::withMessages([
                        'session_id' => ['Сеанс уже начался или прошёл.'],
                    ]);
                }

                // Все места должны принадлежать залу этого сеанса
                $validSeats = Seat::where('hall_id', $screening->hall_id)
                    ->whereIn('id', $data['seat_ids'])
                    ->count();

                if ($validSeats !== count($data['seat_ids'])) {
                    throw ValidationException::withMessages([
                        'seat_ids' => ['Некоторые места не относятся к залу этого сеанса.'],
                    ]);
                }

                $alreadyTaken = Ticket::where('screening_id', $screening->id)
                    ->whereIn('seat_id', $data['seat_ids'])
                    ->exists();

                if ($alreadyTaken) {
                    abort(409, 'Одно или несколько мест уже заняты.');
                }

                $order = Order::create(['user_id' => $request->user()->id]);

                $order->tickets()->createMany(
                    array_map(fn ($seatId) => [
                        'screening_id' => $screening->id,
                        'seat_id'      => $seatId,
                    ], $data['seat_ids'])
                );

                return $order;
            });
        } catch (UniqueConstraintViolationException) {
            // Запасная защита на уровне БД: уникальный индекс (screening_id, seat_id)
            abort(409, 'Одно или несколько мест уже заняты.');
        }

        $order->load(self::WITH);

        return (new BookingResource($order))->response()->setStatusCode(201);
    }

    // Отменить бронь целиком
    public function destroy(Request $request, int $id): JsonResponse
    {
        $order = $request->user()->orders()->with('tickets.screening')->findOrFail($id);

        $startsAt = $order->tickets->first()?->screening->starts_at;

        if ($startsAt && $startsAt->lt(now()->addMinutes(60))) {
            abort(422, 'Отмена возможна не позднее чем за 60 минут до начала сеанса.');
        }

        $order->delete(); // билеты удалятся каскадом, места освободятся

        return response()->json(['message' => 'Бронь отменена.']);
    }
}