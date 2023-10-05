<?php

declare(strict_types=1);

namespace App\Model;

readonly class Payment
{
    public function __construct(
        private int $segmentNumber,
        private int $principalPayment,
        private int $interestPayment,
        private int $euriborPayment,
        private int $totalPayment,
    ) {
    }

    public function getSegmentNumber(): int
    {
        return $this->segmentNumber;
    }

    public function getPrincipalPayment(): int
    {
        return $this->principalPayment;
    }

    public function getInterestPayment(): int
    {
        return $this->interestPayment;
    }

    public function getEuriborPayment(): int
    {
        return $this->euriborPayment;
    }

    public function getTotalPayment(): int
    {
        return $this->totalPayment;
    }
}
