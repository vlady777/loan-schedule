<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Loan;
use Symfony\Component\Serializer\Annotation\Groups;

readonly class Payment
{
    #[Groups([Loan::GROUP_PAYMENT_LIST])]
    private int $totalPayment;

    public function __construct(
        #[Groups([Loan::GROUP_PAYMENT_LIST])]
        private int $segmentNumber,
        #[Groups([Loan::GROUP_PAYMENT_LIST])]
        private int $principalPayment,
        #[Groups([Loan::GROUP_PAYMENT_LIST])]
        private int $interestPayment,
        #[Groups([Loan::GROUP_PAYMENT_LIST])]
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
