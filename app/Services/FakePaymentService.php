<?php

namespace App\Services;

class FakePaymentService
{
    
    public const DECLINED_CARD = '4000000000000002';

    public function charge(string $cardNumber, string $amount): bool
    {
        return $cardNumber !== self::DECLINED_CARD;
    }
}