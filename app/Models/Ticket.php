<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['order_id', 'screening_id', 'seat_id', 'status', 'used_at'])]
class Ticket extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            $ticket->code ??= Str::upper(Str::random(12));
        });
    }

    protected function casts(): array
    {
        return [
            'status'  => TicketStatus::class,
            'used_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function screening(): BelongsTo
    {
        return $this->belongsTo(Screening::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }
}