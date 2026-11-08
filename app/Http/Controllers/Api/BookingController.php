<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayBookingRequest;
use App\Http\Requests\ResendTicketsRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Mail\TicketsMail;
use App\Models\Order;
use App\Models\Screening;
use App\Models\Seat;
use App\Models\Ticket;
use App\Services\FakePaymentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    private const WITH = ['tickets.seat', 'tickets.screening.movie', 'tickets.screening.hall'];
    private const MAX_PENDING = 3; // сколько неоплаченных броней может быть у одного пользователя

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

    // Забронировать места: бронь ждёт оплаты, места удерживаются
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();

        $pending = $request->user()->orders()
            ->where('status', OrderStatus::Pending->value)
            ->count();

        if ($pending >= self::MAX_PENDING) {
            abort(409, 'У вас слишком много неоплаченных броней. Оплатите или отмените их.');
        }

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

                $order = Order::create([
                    'user_id'    => $request->user()->id,
                    'status'     => OrderStatus::Pending,
                    'expires_at' => now()->addMinutes(Order::HOLD_MINUTES),
                ]);

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

    // Оплатить бронь: проверка формы, имитация списания, письмо с билетами
    public function pay(PayBookingRequest $request, FakePaymentService $payments, int $id): JsonResponse
    {
        // Данные карты используются только для проверки и нигде не сохраняются
        $data = $request->validated();

        $order = $request->user()->orders()->with(self::WITH)->findOrFail($id);

        if ($order->status === OrderStatus::Paid) {
            abort(409, 'Бронь уже оплачена.');
        }

        $screening = $order->tickets->first()->screening;

        if ($screening->starts_at->isPast()) {
            abort(422, 'Сеанс уже начался, оплата невозможна.');
        }

        $amount = number_format($screening->price * $order->tickets->count(), 2, '.', '');
        $cardNumber = preg_replace('/\s+/', '', $data['card_number']);

        if (! $payments->charge($cardNumber, $amount)) {
            abort(402, 'Платёж отклонён банком. Попробуйте другую карту.');
        }

        DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === OrderStatus::Paid) {
                abort(409, 'Бронь уже оплачена.'); // защита от двойного нажатия «Оплатить»
            }

            $locked->update([
                'status'     => OrderStatus::Paid,
                'paid_at'    => now(),
                'expires_at' => null,
            ]);
        });

        $order = $order->fresh(self::WITH);

        $email = $data['email'] ?? $request->user()->email;
        $sent = $this->sendTickets($order, $email);

        // Оплата остаётся в силе, даже если письмо не ушло: тогда email_sent = false
        return (new BookingResource($order))
            ->additional(['email_sent' => $sent, 'sent_to' => $email])
            ->response();
    }

    // Отправить билеты на почту ещё раз
    public function resend(ResendTicketsRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        $order = $request->user()->orders()->with(self::WITH)->findOrFail($id);

        if ($order->status !== OrderStatus::Paid) {
            abort(409, 'Бронь не оплачена, билеты ещё не выданы.');
        }

        $email = $data['email'] ?? $request->user()->email;

        if (! $this->sendTickets($order, $email)) {
            abort(503, 'Не удалось отправить письмо. Попробуйте позже.');
        }

        return response()->json(['message' => "Билеты отправлены на {$email}."]);
    }

    // Отменить бронь целиком
    public function destroy(Request $request, int $id): JsonResponse
    {
        $order = $request->user()->orders()->with('tickets.screening')->findOrFail($id);

        // Оплаченную бронь нельзя отменить позже чем за 60 минут до начала; неоплаченную можно в любой момент
        if ($order->status === OrderStatus::Paid) {
            $startsAt = $order->tickets->first()?->screening->starts_at;

            if ($startsAt && $startsAt->lt(now()->addMinutes(60))) {
                abort(422, 'Отмена возможна не позднее чем за 60 минут до начала сеанса.');
            }
        }

        $order->delete(); // билеты удалятся каскадом, места освободятся

        return response()->json(['message' => 'Бронь отменена.']);
    }

    private function sendTickets(Order $order, string $email): bool
    {
        try {
            Mail::to($email)->send(new TicketsMail($order));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}