<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Euribor;
use App\Entity\Loan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[CoversClass(Euribor::class)]
class EuriborTest extends TestCase
{
    public function testInstance(): void
    {
        $entity = new Euribor();

        $class = new ReflectionClass($entity);
        $classAttrs = $class->getAttributes(UniqueEntity::class);
        self::assertCount(1, $classAttrs, 'UniqueEntity validator is missing');

        $uniqueEntityArgs = $classAttrs[0]->getArguments();
        self::assertSame(['loan', 'segmentNumber'], $uniqueEntityArgs['fields']);
        self::assertSame('segmentNumber', $uniqueEntityArgs['errorPath']);
        self::assertSame('Segment number already exists in Loan', $uniqueEntityArgs['message']);
    }

    public function testSetLoan(): void
    {
        $euribor = new Euribor();
        $loan1 = new Loan();
        $loan2 = new Loan();

        // set first
        $euribor->setLoan($loan1);
        self::assertSame($loan1, $euribor->getLoan());
        self::assertCount(1, $loan1->getEuribors());
        self::assertContains($euribor, $loan1->getEuribors());

        // move to second
        $euribor->setLoan($loan2);
        self::assertSame($loan2, $euribor->getLoan());
        self::assertEmpty($loan1->getEuribors());
        self::assertCount(1, $loan2->getEuribors());
        self::assertContains($euribor, $loan2->getEuribors());

        // set null (detach)
        $euribor->setLoan(null);
        self::assertNull($euribor->getLoan());
        self::assertEmpty($loan1->getEuribors());
        self::assertEmpty($loan2->getEuribors());
    }
}
