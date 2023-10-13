<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\Euribor;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(Euribor::class)]
class EuriborApiTest extends ApiTestCase
{
    public function testCrud(): void
    {
        $loanId = $this->createLoan();
        $loanIri = sprintf('/loans/%d', $loanId);

        // create
        $response = static::createClient()->request('POST', '/euribors', ['json' => [
            'segmentNumber' => 6,
            'rate' => 410,
            'loan' => $loanIri,
        ]]);

        self::assertResponseIsSuccessful();

        $responseArray = $response->toArray();
        $id = $responseArray['id'];
        $euriborRoute = sprintf('/euribors/%d', $responseArray['id']);

        // get list
        $response = static::createClient()->request('GET', '/euribors', ['headers' => ['accept' => 'application/json']]);
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($response->toArray());

        // get created
        static::createClient()->request('GET', $euriborRoute);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'segmentNumber' => 6,
            'rate' => 410,
            'loan' => $loanIri,
        ]);

        // update
        static::createClient()->request('PATCH', $euriborRoute, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'rate' => 450,
            ],
        ]);

        // get updated
        static::createClient()->request('GET', $euriborRoute);
        self::assertResponseIsSuccessful();
        self::assertJsonContains([
            'id' => $id,
            'segmentNumber' => 6,
            'rate' => 450,
            'loan' => $loanIri,
        ]);

        // delete
        static::createClient()->request('DELETE', $euriborRoute);
        self::assertResponseIsSuccessful();

        // get deleted
        static::createClient()->request('GET', $euriborRoute);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $this->deleteLoan($loanId);
    }

    public function testGetWhenErrors(): void
    {
        static::createClient()->request('GET', '/euribors/0');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testPostWhenErrors(): void
    {
        static::createClient()->request('POST', '/euribors', ['json' => [
            'segmentNumber' => 'some string',
        ]]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $loanId = $this->createLoan();
        static::createClient()->request('POST', '/euribors', ['json' => [
            'segmentNumber' => 0,
            'rate' => 0,
            'loan' => '/loans/'.$loanId,
        ]]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->deleteLoan($loanId);
    }

    public function testPatchWhenErrors(): void
    {
        static::createClient()->request('PATCH', '/euribors/0', [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'segmentNumber' => 7,
                'rate' => 500,
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $loanId = $this->createLoan();
        $id = $this->createEuribor($loanId);

        static::createClient()->request('PATCH', '/euribors/'.$id, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'segmentNumber' => 'yes',
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        static::createClient()->request('PATCH', '/euribors/'.$id, [
            'headers' => [
                'Content-Type' => 'application/merge-patch+json',
            ],
            'json' => [
                'segmentNumber' => 0,
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->deleteLoan($loanId);
    }

    public function testDeleteWhenErrors(): void
    {
        static::createClient()->request('DELETE', '/euribors/0');
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

    private function createEuribor(int $loanId): int
    {
        $response = static::createClient()->request('POST', '/euribors', ['json' => [
            'segmentNumber' => 6,
            'rate' => 410,
            'loan' => '/loans/'.$loanId,
        ]]);

        return (int) $response->toArray()['id'];
    }

    private function deleteLoan(int $id): void
    {
        static::createClient()->request('DELETE', '/loans/'.$id);
    }

    private function deleteEuribor(int $id): void
    {
        static::createClient()->request('DELETE', '/euribors/'.$id);
    }
}
