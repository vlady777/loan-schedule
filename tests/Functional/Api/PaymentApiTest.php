<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Model\Payment;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(Payment::class)]
class PaymentApiTest extends ApiTestCase
{
    public function testGet(): void
    {
        $loanId = $this->createLoan();
        $paymentsRoute = sprintf('/loans/%d/payments', $loanId);

        $response = static::createClient()->request('GET', $paymentsRoute);
        self::assertResponseIsSuccessful();

        $responseArray = $response->toArray();
        self::assertArrayHasKey('id', $responseArray);
        self::assertArrayHasKey('payments', $responseArray);
        self::assertSame($loanId, $responseArray['id']);
        self::assertCount(12, $responseArray['payments']);

        $firstPayment = $responseArray['payments'][0];
        self::assertArrayHasKey('segmentNumber', $firstPayment);
        self::assertArrayHasKey('principalPayment', $firstPayment);
        self::assertArrayHasKey('interestPayment', $firstPayment);
        self::assertArrayHasKey('euriborPayment', $firstPayment);
        self::assertArrayHasKey('totalPayment', $firstPayment);

        $this->deleteLoan($loanId);
    }

    public function testGetWhenErrors(): void
    {
        static::createClient()->request('GET', '/payments/0');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createLoan(): int
    {
        $response = static::createClient()->request('POST', '/loans', ['json' => [
            'amount' => 1000000,
            'term' => 12,
            'interestRate' => 400,
            'defaultEuriborRate' => 394,
        ]]);

        return (int) $response->toArray()['id'];
    }

    private function deleteLoan(int $id): void
    {
        static::createClient()->request('DELETE', '/loans/'.$id);
    }
}
