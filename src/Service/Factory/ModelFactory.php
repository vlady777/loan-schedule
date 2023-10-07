<?php

declare(strict_types=1);

namespace App\Service\Factory;

use App\Model\Payment;

class ModelFactory
{
    public function createPayment(
        int $segmentNumber,
        int|float $principalPayment,
        int|float $interestPayment,
        int|float $euriborPayment,
    ): Payment {
        return new Payment(
            segmentNumber: $segmentNumber,
            principalPayment: (int) round($principalPayment),
            interestPayment: (int) round($interestPayment),
            euriborPayment: (int) round($euriborPayment),
        );
    }
}
