<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Loan;
use App\Exception\PaymentCalculationException;
use App\Helper\Money;
use App\Model\Payment;
use App\Service\Factory\ModelFactory;
use RangeException;

#[\Symfony\Component\DependencyInjection\Attribute\Autoconfigure(public: true)]
readonly class PaymentCalculator
{
    public function __construct(
        private ModelFactory $modelFactory,
    ) {
    }

    /** @return Payment[] */
    public function calculate(Loan $loan): array
    {
        $result = [];
        try {
            $annuityPayment = $loan->getAnnuityPayment();
        } catch (RangeException $e) {
            throw new PaymentCalculationException($e->getMessage());
        }

        $monthlyInterest = $loan->getMonthlyInterestValue();
        $monthlyEuribor = Money::basisPointsToValue(Money::yearlyToMonthly($loan->getDefaultEuriborRate()));
        $remainingAmount = $loan->getAmount();
        for ($segmentNr = 1; $segmentNr <= $loan->getTerm(); $segmentNr++) {
            if (($segmentEuribor = $loan->getEuriborForSegment($segmentNr)) !== null) {
                $monthlyEuribor = Money::basisPointsToValue(Money::yearlyToMonthly($segmentEuribor->getRate()));
            }

            $interest = $remainingAmount * $monthlyInterest;
            $euribor = $remainingAmount * $monthlyEuribor;
            $principal = $annuityPayment - $interest;

            $remainingAmount -= $principal;

            $result[] = $this->modelFactory->createPayment(
                segmentNumber: $segmentNr,
                principalPayment: $principal,
                interestPayment: $interest,
                euriborPayment: $euribor,
            );

//            echo sprintf(
//                '%s | %s | %s | %s | %s',
//                $remainingAmount,
//                $segmentNr,
//                round($principal),
//                round($interest),
//                round($euribor),
//            ).PHP_EOL;
        }

        return $result;
    }
}
