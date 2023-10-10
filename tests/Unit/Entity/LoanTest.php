<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Euribor;
use App\Entity\Loan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RangeException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

#[CoversClass(Loan::class)]
class LoanTest extends TestCase
{
    private ExecutionContextInterface&MockObject $executionContext;
    private ConstraintViolationBuilderInterface&MockObject $violationBuilder;

    protected function setUp(): void
    {
        $this->executionContext = $this->createMock(ExecutionContextInterface::class);
        $this->violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
    }

    public function testAddEuribor(): void
    {
        $loan = new Loan();
        $euribor1 = new Euribor();
        $euribor2 = new Euribor();
        $euribor3 = new Euribor();

        $loan->addEuribor($euribor1);

        self::assertCount(1, $result = $loan->getEuribors());
        self::assertContains($euribor1, $result);

        $loan
            ->addEuribor($euribor2)
            ->addEuribor($euribor3);

        self::assertCount(3, $result = $loan->getEuribors());
        self::assertContains($euribor1, $result);
        self::assertContains($euribor1, $result);
        self::assertContains($euribor1, $result);

        // try to add duplicate - not allowed
        $loan->addEuribor($euribor2);

        self::assertCount(3, $result = $loan->getEuribors());
        self::assertContains($euribor1, $result);
        self::assertContains($euribor1, $result);
        self::assertContains($euribor1, $result);
    }

    public function testRemoveEuribor(): void
    {
        $loan = new Loan();
        $euribor1 = new Euribor();
        $euribor2 = new Euribor();
        $euribor3 = new Euribor();

        $loan
            ->addEuribor($euribor1)
            ->addEuribor($euribor2);

        self::assertCount(2, $result = $loan->getEuribors());
        self::assertContains($euribor1, $result);
        self::assertContains($euribor2, $result);

        $loan->removeEuribor($euribor1);

        self::assertCount(1, $result = $loan->getEuribors());
        self::assertContains($euribor2, $result);

        // try to remove not existing
        $loan->removeEuribor($euribor3);
        self::assertCount(1, $result = $loan->getEuribors());
        self::assertContains($euribor2, $result);

        $loan->removeEuribor($euribor2);
        self::assertEmpty($loan->getEuribors());
    }

    public function testGetEuriborForSegment(): void
    {
        $loan = new Loan();
        $euribor1 = (new Euribor())->setSegmentNumber(2);
        $euribor2 = (new Euribor())->setSegmentNumber(5);
        $loan->addEuribor($euribor1)->addEuribor($euribor2);

        self::assertNull($loan->getEuriborForSegment(1));
        self::assertSame($euribor1, $loan->getEuriborForSegment(2));
        self::assertNull($loan->getEuriborForSegment(3));
        self::assertNull($loan->getEuriborForSegment(4));
        self::assertSame($euribor2, $loan->getEuriborForSegment(5));
        self::assertNull($loan->getEuriborForSegment(6));
    }

    #[
        TestWith([0, 0.0]),
        TestWith([60, 0.0005]),
        TestWith([400, 0.0033333333333333335]),
        TestWith([1200, 0.01]),
        TestWith([1800, 0.015]),
        TestWith([12000, 0.1]),
        TestWith([120000, 1.0]),
    ]
    public function testGetMonthlyInterestValue(int $interestRate, float $expected): void
    {
        $loan = (new Loan())->setInterestRate($interestRate);

        self::assertSame($expected, $loan->getMonthlyInterestValue());
    }

    #[
        TestWith([0, 'Loan term "0" cannot be lower than 1']),
        TestWith([-1, 'Loan term "-1" cannot be lower than 1']),
        TestWith([-12, 'Loan term "-12" cannot be lower than 1']),
    ]
    public function testTestGetAnnuityPaymentWhenWrongTerm(int $term, string $expectedMsg): void
    {
        $loan = (new Loan())->setTerm($term);

        $this->expectException(RangeException::class);
        $this->expectExceptionMessage($expectedMsg);

        $loan->getAnnuityPayment();
    }

    #[
        TestWith([0, 12, 0, 0]),
        TestWith([0, 12, 400, 0.0]),
        TestWith([600000, 6, 0, 100000]),
        TestWith([600000, 12, 0, 50000]),
        TestWith([600000, 6, 300, 100876.8206348143]),
        TestWith([1000000, 12, 400, 85149.90419555597]),
    ]
    public function testGetAnnuityPayment(int $amount, int $term, int $interestRate, int|float $expected): void
    {
        $loan = (new Loan())
            ->setAmount($amount)
            ->setTerm($term)
            ->setInterestRate($interestRate);

        self::assertSame($expected, $loan->getAnnuityPayment());
    }

    public function testValidateWhenOneDuplicatedSegmentNumber(): void
    {
        $loan = new Loan();
        $euribor1 = (new Euribor())->setSegmentNumber(3);
        $euribor2 = (new Euribor())->setSegmentNumber(7);
        $euribor3 = (new Euribor())->setSegmentNumber(3);
        $euribor4 = (new Euribor())->setSegmentNumber(10);
        $loan
            ->addEuribor($euribor1)
            ->addEuribor($euribor2)
            ->addEuribor($euribor3)
            ->addEuribor($euribor4)
        ;

        $this->executionContext->expects(self::once())
            ->method('buildViolation')
            ->with(self::stringStartsWith('Euribor with segment number "3" already exists in Loan'))
            ->willReturn($this->violationBuilder);

        $this->violationBuilder->expects(self::once())
            ->method('atPath')
            ->with('euribor[2].segmentNumber')
            ->willReturn($this->violationBuilder);

        $this->violationBuilder->expects(self::once())->method('addViolation');

        // act
        $loan->validate($this->executionContext);
    }

    public function testValidateWhenFewDuplicatedSegmentNumbers(): void
    {
        $loan = new Loan();
        $euribor1 = (new Euribor())->setSegmentNumber(3);
        $euribor2 = (new Euribor())->setSegmentNumber(7);
        $euribor5 = (new Euribor())->setSegmentNumber(12);
        $euribor3 = (new Euribor())->setSegmentNumber(10);
        $euribor4 = (new Euribor())->setSegmentNumber(12);
        $euribor6 = (new Euribor())->setSegmentNumber(7);
        $loan
            ->addEuribor($euribor1)
            ->addEuribor($euribor2)
            ->addEuribor($euribor3)
            ->addEuribor($euribor4)
            ->addEuribor($euribor5)
            ->addEuribor($euribor6)
        ;

        $this->executionContext->expects(self::once())
            ->method('buildViolation')
            ->with(self::stringStartsWith('Euribor with segment number "12" already exists in Loan'))
            ->willReturn($this->violationBuilder);

        $this->violationBuilder->expects(self::once())
            ->method('atPath')
            ->with('euribor[4].segmentNumber')
            ->willReturn($this->violationBuilder);

        $this->violationBuilder->expects(self::once())->method('addViolation');

        // act
        $loan->validate($this->executionContext);
    }

    public function testValidateNoDuplicates(): void
    {
        $loan = new Loan();
        $euribor1 = (new Euribor())->setSegmentNumber(3);
        $euribor2 = (new Euribor())->setSegmentNumber(7);
        $euribor3 = (new Euribor())->setSegmentNumber(10);
        $loan
            ->addEuribor($euribor1)
            ->addEuribor($euribor2)
            ->addEuribor($euribor3)
        ;

        $this->executionContext->expects(self::never())->method('buildViolation');
        $this->violationBuilder->expects(self::never())->method('atPath');
        $this->violationBuilder->expects(self::never())->method('addViolation');
    }
}
