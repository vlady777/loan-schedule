<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Euribor;
use App\Entity\Loan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(Loan::class)]
class LoanTest extends KernelTestCase
{
    public function testValidateDefault(): void
    {
        $this->validateAndAssert(new Loan(), [
            'Amount should be greater than 0',
            'Term should be greater than 0',
        ]);
    }

    #[
        TestWith([-1000000000, ['Amount should be greater than 0', 'Amount is too big. It should have 10 digits or less']]),
        TestWith([-1, ['Amount should be greater than 0']]),
        TestWith([0, ['Amount should be greater than 0']]),
        TestWith([1, []]),
        TestWith([1000000, []]),
        TestWith([9999999999, []]),
        TestWith([10000000000, ['Amount is too big. It should have 10 digits or less']]),
    ]
    public function testValidateAmount(int $amount, array $expectedErrors): void
    {
        $loan = (new Loan())->setTerm(3)->setAmount($amount);

        $this->validateAndAssert($loan, $expectedErrors);
    }

    #[
        TestWith([-100000, ['Term should be greater than 0', 'Term is too big. It should have 5 digits or less']]),
        TestWith([-1, ['Term should be greater than 0']]),
        TestWith([0, ['Term should be greater than 0']]),
        TestWith([1, []]),
        TestWith([12, []]),
        TestWith([99999, []]),
        TestWith([100000, ['Term is too big. It should have 5 digits or less']]),
    ]
    public function testValidateTerm(int $term, array $expectedErrors): void
    {
        $loan = (new Loan())->setAmount(100000)->setTerm($term);

        $this->validateAndAssert($loan, $expectedErrors);
    }

    #[
        TestWith([-100000, [
            'Interest rate should be greater than or equal to 0',
            'Interest rate is too big. It should have 5 digits or less',
        ]]),
        TestWith([-1, ['Interest rate should be greater than or equal to 0']]),
        TestWith([0, []]),
        TestWith([1, []]),
        TestWith([400, []]),
        TestWith([99999, []]),
        TestWith([100000, ['Interest rate is too big. It should have 5 digits or less']]),
    ]
    public function testValidateInterestRate(int $interestRate, array $expectedErrors): void
    {
        $loan = (new Loan())->setAmount(100000)->setTerm(12)->setInterestRate($interestRate);

        $this->validateAndAssert($loan, $expectedErrors);
    }

    #[
        TestWith([-100000, [
            'Default euribor rate should be greater than or equal to 0',
            'Default euribor rate is too big. It should have 5 digits or less',
        ]]),
        TestWith([-1, ['Default euribor rate should be greater than or equal to 0']]),
        TestWith([0, []]),
        TestWith([1, []]),
        TestWith([345, []]),
        TestWith([99999, []]),
        TestWith([100000, ['Default euribor rate is too big. It should have 5 digits or less']]),
    ]
    public function testValidateDefaultEuriborRate(int $defaultEuriborRate, array $expectedErrors): void
    {
        $loan = (new Loan())->setAmount(100000)->setTerm(12)->setDefaultEuriborRate($defaultEuriborRate);

        $this->validateAndAssert($loan, $expectedErrors);
    }

    public function testEuriborsAreValidated(): void
    {
        $loan = (new Loan())
            ->setAmount(10000)
            ->setTerm(3)
            ->addEuribor((new Euribor())->setSegmentNumber(-1))
        ;

        $this->validateAndAssert($loan, ['Segment number should be greater than 0']);
    }

    private function validateAndAssert(Loan $loan, array $expectedErrors): void
    {
        $errors = $this->prepareValidatorInstance()->validate($loan);

        $expectedCount = count($expectedErrors);
        if ($expectedCount) {
            self::assertCount($expectedCount, $errors);
            foreach ($expectedErrors as $k => $errorMessage) {
                self::assertSame($errorMessage, $errors->offsetGet($k)->getMessage());
            }
        } else {
            self::assertEmpty($errors);
        }
    }

    private function prepareValidatorInstance(): ValidatorInterface
    {
        return static::getContainer()->get('validator');
    }
}
