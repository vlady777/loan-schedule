<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Loan;
use App\Exception\PaymentCalculationException;
use App\Helper\Money;
use App\Model\Payment;
use RangeException;

readonly class PaymentCalculator
{
    /** @return Payment[] */
    public function calculate(Loan $loan): array
    {
        $result = [];
        try {
            $annuityPayment = Money::roundCents($loan->getAnnuityPayment());
        } catch (RangeException $e) {
            throw new PaymentCalculationException($e->getMessage());
        }

        $remainingAmount = $loan->getAmount();
        if ($remainingAmount === 0) {
            return $result;
        }

        $term = $loan->getTerm();
        $monthlyInterest = $loan->getMonthlyInterestValue();
        $monthlyEuribor = Money::basisPointsToValue(Money::yearlyToMonthly($loan->getDefaultEuriborRate()));
        for ($segmentNr = 1; $segmentNr <= $term; $segmentNr++) {
            if (($segmentEuribor = $loan->getEuriborForSegment($segmentNr)) !== null) {
                $monthlyEuribor = Money::basisPointsToValue(Money::yearlyToMonthly($segmentEuribor->getRate()));
            }

            $interest = Money::roundCents($remainingAmount * $monthlyInterest);
            $euribor = Money::roundCents($remainingAmount * $monthlyEuribor);
            $principal = $annuityPayment - $interest;
            if ($segmentNr === $term && $principal !== $remainingAmount) {
                $principal = $remainingAmount;
            }

            $remainingAmount -= $principal;

            $result[] = new Payment(
                segmentNumber: $segmentNr,
                principalPayment: $principal,
                interestPayment: $interest,
                euriborPayment: $euribor,
            );
        }

        return $result;
    }
}
