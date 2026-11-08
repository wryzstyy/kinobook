@php
    $screening = $order->tickets->first()->screening;
    $total = number_format($screening->price * $order->tickets->count(), 2, '.', '');
@endphp
<!DOCTYPE html>
<html lang="ru">
<body style="margin:0; padding:24px; background:#f4f4f7; font-family:Arial, sans-serif; color:#222;">
  <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:8px; padding:24px;">
    <h2 style="margin-top:0;">Ваши билеты на «{{ $screening->movie->title }}»</h2>
    <p>Бронь №{{ $order->id }} оплачена. Покажите код билета на входе в кинотеатр.</p>

    <p style="line-height:1.7;">
      <strong>Дата и время:</strong> {{ $screening->starts_at->format('d.m.Y H:i') }}<br>
      <strong>Зал:</strong> {{ $screening->hall->name }}<br>
      <strong>Билетов:</strong> {{ $order->tickets->count() }}<br>
      <strong>Сумма:</strong> {{ $total }}
    </p>

    <table style="width:100%; border-collapse:collapse;">
      <tr style="background:#f0f0f4;">
        <th align="left" style="padding:8px;">Ряд</th>
        <th align="left" style="padding:8px;">Место</th>
        <th align="left" style="padding:8px;">Код билета</th>
      </tr>
      @foreach ($order->tickets as $ticket)
        <tr>
          <td style="padding:8px; border-bottom:1px solid #eee;">{{ $ticket->seat->row_number }}</td>
          <td style="padding:8px; border-bottom:1px solid #eee;">{{ $ticket->seat->seat_number }}</td>
          <td style="padding:8px; border-bottom:1px solid #eee; font-family:monospace; font-size:16px;">
            <strong>{{ $ticket->code }}</strong>
          </td>
        </tr>
      @endforeach
    </table>

    <p style="color:#777; font-size:13px; margin-bottom:0;">
      Отменить бронь можно не позднее чем за 60 минут до начала сеанса.
    </p>
  </div>
</body>
</html>