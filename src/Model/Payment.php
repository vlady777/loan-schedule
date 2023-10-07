<?php

declare(strict_types=1);

namespace App\Model;

readonly class Payment
{
    private int $totalPayment;

    public function __construct(
        private int $segmentNumber,
        private int $principalPayment,
        private int $interestPayment,
        private int $euriborPayment,
    ) {
        $this->totalPayment = $this->principalPayment + $this->interestPayment + $this->euriborPayment;
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
