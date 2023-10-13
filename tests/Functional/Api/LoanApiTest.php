<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Loan;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(Loan::class)]
class LoanApiTest extends ApiTestCase
{
    public function testCrud(): void
    {
        // create
        $response = static::createClient()->request('POST', '/loans', ['json' => [
            'amount' => 100000,
            'term' => 12,
            'interestRate' => 450,
            'defaultEuriborRate' => 230,
        ]]);

        self::assertResponseIsSuccessful();

        $responseArray = $response->toArray();
        $id = $responseArray['id'];
        $loanRoute = sprintf('/loans/%d', $responseArray['id']);

        // get list
        $response = static::createClient()->request('GET', '/loans', ['headers' => ['accept' => 'application/json']]);
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($response->toArray());

        // get created
        static::createClient()->request('GET', $loanRoute);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'amount' => 100000,
            'interestRate' => 450,
            'defaultEuriborRate' => 230,
        ]);

        // update
        static::createClient()->request('PATCH', $loanRoute, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'interestRate' => 500,
                'defaultEuriborRate' => 300,
            ],
        ]);

        // get updated
        static::createClient()->request('GET', $loanRoute);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'amount' => 100000,
            'interestRate' => 500,
            'defaultEuriborRate' => 300,
        ]);

        // delete
        static::createClient()->request('DELETE', $loanRoute);
        self::assertResponseIsSuccessful();

        // get deleted
        static::createClient()->request('GET', $loanRoute);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetWhenErrors(): void
    {
        static::createClient()->request('GET', '/loans/0');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPostWhenErrors(): void
    {
        static::createClient()->request('POST', '/loans', ['json' => [
            'amount' => 'some string',
        ]]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        static::createClient()->request('POST', '/loans', ['json' => [
            'amount' => 0,
            'term' => 0,
            'interestRate' => 0,
            'defaultEuriborRate' => 0,
        ]]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testPatchWhenErrors(): void
    {
        static::createClient()->request('PATCH', '/loans/0', [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'interestRate' => 500,
                'defaultEuriborRate' => 300,
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $id = $this->createLoan();

        static::createClient()->request('PATCH', '/loans/'.$id, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'interestRate' => 'yes',
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        static::createClient()->request('PATCH', '/loans/'.$id, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'amount' => 0,
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->deleteLoan($id);
    }

    public function testDeleteWhenErrors(): void
    {
        static::createClient()->request('DELETE', '/loans/0');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createLoan(): int
    {
        $response = static::createClient()->request('POST', '/loans', ['json' => [
            'amount' => 1000,
            'term' => 2,
            'interestRate' => 220,
            'defaultEuriborRate' => 100,
        ]]);

        return (int) $response->toArray()['id'];
    }

    private function deleteLoan(int $id): void
    {
        static::createClient()->request('DELETE', '/loans/'.$id);
    }
}
