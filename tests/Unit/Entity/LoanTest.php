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

    public function testGetPaymentsWhenException(): void
    {
        $loan = (new Loan())->setAmount(1000);

        $this->expectException(RangeException::class);
        $loan->getPayments();
    }

    #[
        //#0: all zeros, one month
        TestWith([
            'amount' => 0,
            'term' => 1,
            'interest' => 0,
            'def_euribor' => 0,
            'euribors' => [],
            'expected' => [],
        ]),
        //#1: all zeros, 6 months
        TestWith([
            'amount' => 0,
            'term' => 6,
            'interest' => 0,
            'def_euribor' => 0,
            'euribors' => [],
            'expected' => [],
        ]),
        //#2: 1000 euro, one month, zero interest and d.euribor
        TestWith([
            'amount' => 100000,
            'term' => 1,
            'interest' => 0,
            'def_euribor' => 0,
            'euribors' => [],
            'expected' => [
                1 => [100000, 0, 0, 100000],
            ],
        ]),
        //#3: 1000 euro, one month, 400 interest, zero d.euribor
        TestWith([
            'amount' => 100000,
            'term' => 1,
            'interest' => 400,
            'def_euribor' => 0,
            'euribors' => [],
            'expected' => [
                1 => [100000, 333, 0, 100333],
            ],
        ]),
        //#4: 1000 euro, one month, 400 interest, 356 d.euribor
        TestWith([
            'amount' => 100000,
            'term' => 1,
            'interest' => 400,
            'def_euribor' => 356,
            'euribors' => [],
            'expected' => [
                1 => [100000, 333, 297, 100630],
            ],
        ]),
        //#5: 1000 euro, one month, 400 interest, 356 d.euribor, 412 euribor from segment 1
        TestWith([
            'amount' => 100000,
            'term' => 1,
            'interest' => 400,
            'def_euribor' => 356,
            'euribors' => [
                1 => 412,
            ],
            'expected' => [
                1 => [100000, 333, 343, 100676],
            ],
        ]),
        //#6: 1000 euro, 6 months, zero interest and d.euribor (rounding check)
        TestWith([
            'amount' => 100000,
            'term' => 6,
            'interest' => 0,
            'def_euribor' => 0,
            'euribors' => [],
            'expected' => [
                1 => [16667, 0, 0, 16667],
                2 => [16667, 0, 0, 16667],
                3 => [16667, 0, 0, 16667],
                4 => [16667, 0, 0, 16667],
                5 => [16667, 0, 0, 16667],
                6 => [16665, 0, 0, 16665],
            ],
        ]),
        //#7: 1000 euro, 12 months, zero interest and d.euribor (rounding check)
        TestWith([
            'amount' => 100000,
            'term' => 12,
            'interest' => 0,
            'def_euribor' => 0,
            'euribors' => [],
            'expected' => [
                1 => [8333, 0, 0, 8333],
                2 => [8333, 0, 0, 8333],
                3 => [8333, 0, 0, 8333],
                4 => [8333, 0, 0, 8333],
                5 => [8333, 0, 0, 8333],
                6 => [8333, 0, 0, 8333],
                7 => [8333, 0, 0, 8333],
                8 => [8333, 0, 0, 8333],
                9 => [8333, 0, 0, 8333],
                10 => [8333, 0, 0, 8333],
                11 => [8333, 0, 0, 8333],
                12 => [8337, 0, 0, 8337],
            ],
        ]),
        //#8: 1000 euro, 6 months, 420 interest, zero d.euribor
        TestWith([
            'amount' => 100000,
            'term' => 6,
            'interest' => 420,
            'def_euribor' => 0,
            'euribors' => [],
            'expected' => [
                1 => [16521, 350, 0, 16871],
                2 => [16579, 292, 0, 16871],
                3 => [16637, 234, 0, 16871],
                4 => [16695, 176, 0, 16871],
                5 => [16754, 117, 0, 16871],
                6 => [16814, 59, 0, 16873],
            ],
        ]),
        //#9: 1000000 euro, 12 months, 400 interest, 394 d.euribor
        // (example, when euribor not changed over time)
        TestWith([
            'amount' => 1000000,
            'term' => 12,
            'interest' => 400,
            'def_euribor' => 394,
            'euribors' => [],
            'expected' => [
                1 => [81817, 3333, 3283, 88433],
                2 => [82089, 3061, 3015, 88165],
                3 => [82363, 2787, 2745, 87895],
                4 => [82638, 2512, 2475, 87625], // diff: principal and total +1 cent
                5 => [82913, 2237, 2203, 87353],
                6 => [83189, 1961, 1931, 87081],
                7 => [83467, 1683, 1658, 86808],
                8 => [83745, 1405, 1384, 86534],
                9 => [84024, 1126, 1109, 86259],
                10 => [84304, 846, 833, 85983],
                11 => [84585, 565, 556, 85706],
                12 => [84866, 283, 279, 85428], // diff: principal and total -1 cent
            ],
        ]),
        //#10: 1000000 euro, 12 months, 400 interest, 394 d.euribor, 410 euribor from 6th
        // (example2: euribor adjustment of 410 basis points applied from 6th segment)
        TestWith([
            'amount' => 1000000,
            'term' => 12,
            'interest' => 400,
            'def_euribor' => 394,
            'euribors' => [
                6 => 410,
            ],
            'expected' => [
                1 => [81817, 3333, 3283, 88433],
                2 => [82089, 3061, 3015, 88165],
                3 => [82363, 2787, 2745, 87895],
                4 => [82638, 2512, 2475, 87625], // diff: principal and total +1 cent
                5 => [82913, 2237, 2203, 87353],
                6 => [83189, 1961, 2010, 87160],
                7 => [83467, 1683, 1725, 86875],
                8 => [83745, 1405, 1440, 86590],
                9 => [84024, 1126, 1154, 86304],
                10 => [84304, 846, 867, 86017],
                11 => [84585, 565, 579, 85729],
                12 => [84866, 283, 290, 85439], // diff: principal and total -1 cent
            ],
        ]),
        //#11: 1000000 euro, 24 months, 420 interest, 256 d.euribor, euribor changed many times during period
        TestWith([
            'amount' => 1000000,
            'term' => 24,
            'interest' => 420,
            'def_euribor' => 256,
            'euribors' => [
                4 => 312,
                6 => 356,
                8 => 394,
                9 => 410,
                14 => 444,
                16 => 420,
                17 => 376,
                20 => 290,
                22 => 340,
            ],
            'expected' => [
                1 => [40014, 3500, 2133, 45647],
                2 => [40154, 3360, 2048, 45562],
                3 => [40295, 3219, 1962, 45476],
                4 => [40436, 3078, 2287, 45801],
                5 => [40577, 2937, 2182, 45696],
                6 => [40719, 2795, 2369, 45883],
                7 => [40862, 2652, 2248, 45762],
                8 => [41005, 2509, 2354, 45868],
                9 => [41148, 2366, 2309, 45823],
                10 => [41292, 2222, 2169, 45683],
                11 => [41437, 2077, 2028, 45542],
                12 => [41582, 1932, 1886, 45400],
                13 => [41727, 1787, 1744, 45258],
                14 => [41873, 1641, 1734, 45248],
                15 => [42020, 1494, 1579, 45093],
                16 => [42167, 1347, 1347, 44861],
                17 => [42315, 1199, 1074, 44588],
                18 => [42463, 1051, 941, 44455],
                19 => [42611, 903, 808, 44322],
                20 => [42760, 754, 520, 44034],
                21 => [42910, 604, 417, 43931],
                22 => [43060, 454, 367, 43881],
                23 => [43211, 303, 245, 43759],
                24 => [43362, 152, 123, 43637],
            ],
        ]),
    ]
    public function testGetPayments(
        int $amount,
        int $term,
        int $interest,
        int $defEuribor,
        array $euribors,
        // format: segment nr => array<principal, interest, euribor, total>
        array $expected,
    ): void {
        $loan = (new Loan())
            ->setAmount($amount)
            ->setTerm($term)
            ->setInterestRate($interest)
            ->setDefaultEuriborRate($defEuribor);
        foreach ($euribors as $segmentNr => $rate) {
            $loan->addEuribor(
                (new Euribor())
                    ->setSegmentNumber($segmentNr)
                    ->setRate($rate)
            );
        }

        $result = $loan->getPayments();

        self::assertCount(count($expected), $result, 'Term is not equals to payment count');
        self::assertIsList($result);
        $totalPrincipalPayment = 0;
        foreach ($expected as $k => $expectedPayment) {
            $resultPayment = $result[$k - 1];
            self::assertSame($k, $resultPayment->getSegmentNumber());
            self::assertSame($expectedPayment[0], $resultPayment->getPrincipalPayment());
            self::assertSame($expectedPayment[1], $resultPayment->getInterestPayment());
            self::assertSame($expectedPayment[2], $resultPayment->getEuriborPayment());
            self::assertSame($expectedPayment[3], $resultPayment->getTotalPayment());
            $totalPrincipalPayment += $resultPayment->getPrincipalPayment();
        }

        self::assertSame($amount, $totalPrincipalPayment, 'Total principal payment not equals to loan amount');
    }
}
