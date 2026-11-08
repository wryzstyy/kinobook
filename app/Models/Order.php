<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'status', 'paid_at', 'expires_at'])]
class Order extends Model
{
    public const HOLD_MINUTES = 15; // сколько минут держатся места до оплаты

    protected function casts(): array
    {
        return [
            'status'     => OrderStatus::class,
            'paid_at'    => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // Удаляет неоплаченные брони с истёкшим временем; билеты удаляются каскадом, места освобождаются
    public static function releaseExpired(): void
    {
        static::where('status', OrderStatus::Pending->value)
            ->where('expires_at', '<', now())
            ->delete();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}