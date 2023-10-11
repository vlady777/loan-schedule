<?php

declare(strict_types=1);

namespace App\Tests\Functional\Entity;

use App\Entity\Euribor;
use App\Entity\Loan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(Euribor::class)]
class EuriborTest extends KernelTestCase
{
    public function testValidateDefault(): void
    {
        $this->validateAndAssert(new Euribor(), [
            'Segment number should be greater than 0',
            'Loan is missing',
        ]);
    }

    #[
        TestWith([-100000, [
            'Segment number should be greater than 0',
            'Segment number is too big. It should have 5 digits or less',
        ]]),
        TestWith([-1, ['Segment number should be greater than 0']]),
        TestWith([0, ['Segment number should be greater than 0']]),
        TestWith([1, []]),
        TestWith([12, []]),
        TestWith([99999, []]),
        TestWith([100000, ['Segment number is too big. It should have 5 digits or less']]),
    ]
    public function testValidateSegmentNumber(int $segmentNumber, array $expectedErrors): void
    {
        $euribor = (new Euribor())->setSegmentNumber($segmentNumber)->setLoan(new Loan());

        $this->validateAndAssert($euribor, $expectedErrors);
    }

    #[
        TestWith([-100000, [
            'Rate should be greater than or equal to 0',
            'Rate is too big. It should have 5 digits or less',
        ]]),
        TestWith([-1, ['Rate should be greater than or equal to 0']]),
        TestWith([0, []]),
        TestWith([1, []]),
        TestWith([234, []]),
        TestWith([99999, []]),
        TestWith([100000, ['Rate is too big. It should have 5 digits or less']]),
    ]
    public function testValidateRate(int $rate, array $expectedErrors): void
    {
        $euribor = (new Euribor())->setLoan(new Loan())->setSegmentNumber(1)->setRate($rate);

        $this->validateAndAssert($euribor, $expectedErrors);
    }

    public function testValidateLoan(): void
    {
        $euribor = (new Euribor())->setSegmentNumber(1)->setRate(234);

        $this->validateAndAssert($euribor, ['Loan is missing']);
    }

    private function validateAndAssert(Euribor $euribor, array $expectedErrors): void
    {
        $errors = $this->prepareValidatorInstance()->validate($euribor);

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
