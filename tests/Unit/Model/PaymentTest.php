<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model;

use App\Model\Payment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Payment::class)]
class PaymentTest extends TestCase
{
    public function testModelCreation(): void
    {
        $model = new Payment(11, 222, 333, 444);

        self::assertSame(11, $model->getSegmentNumber());
        self::assertSame(222, $model->getPrincipalPayment());
        self::assertSame(333, $model->getInterestPayment());
        self::assertSame(444, $model->getEuriborPayment());
        self::assertSame(999, $model->getTotalPayment());
    }
}
