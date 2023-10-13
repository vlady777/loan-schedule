<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Helper\Money;
use App\Model\Payment;
use App\Repository\LoanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use RangeException;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[
    ORM\Entity(repositoryClass: LoanRepository::class),
    ORM\Table(name: 'loan'),
    ApiResource(
        operations: [
            new GetCollection(normalizationContext: ['groups' => self::GROUP_LIST]),
            new Get(normalizationContext: ['groups' => self::GROUP_READ]),
            new Post(
                denormalizationContext: ['groups' => self::GROUP_WRITE],
                normalizationContext: ['groups' => self::GROUP_READ]
            ),
            new Patch(
                denormalizationContext: ['groups' => self::GROUP_WRITE],
                normalizationContext: ['groups' => self::GROUP_READ]
            ),
            new Delete(),
        ],
    ),
    ApiResource(
        shortName: 'Payment',
        uriTemplate: '/loans/{id}/payments',
        paginationEnabled: false,
        operations: [
            new Get(normalizationContext: ['groups' => self::GROUP_PAYMENT_LIST]),
        ],
    ),
]
class Loan
{
    private const GROUP_LIST = __CLASS__.':list';
    private const GROUP_READ = __CLASS__.':read';
    private const GROUP_WRITE = __CLASS__.':write';
    public const GROUP_PAYMENT_LIST = __CLASS__.':payment:list';

    #[
        ORM\Id,
        ORM\GeneratedValue,
        ORM\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true]),
        Groups([self::GROUP_LIST, self::GROUP_READ, self::GROUP_PAYMENT_LIST]),
    ]
    private ?int $id = null;

    #[
        ORM\Column(
            name: 'amount', type: Types::INTEGER, length: 10,
            options: ['unsigned' => true, 'comment' => 'in cents']
        ),
        Assert\GreaterThan(value: 0, message: 'Amount should be greater than 0'),
        Assert\Length(max: 10, maxMessage: 'Amount is too big. It should have 10 digits or less'),
        Groups([self::GROUP_LIST, self::GROUP_READ, self::GROUP_WRITE]),
    ]
    private int $amount = 0;

    #[
        ORM\Column(
            name: 'term', type: Types::SMALLINT, length: 5,
            options: ['unsigned' => true, 'comment' => 'in months']
        ),
        Assert\GreaterThan(value: 0, message: 'Term should be greater than 0'),
        Assert\Length(max: 5, maxMessage: 'Term is too big. It should have 5 digits or less'),
        Groups([self::GROUP_LIST, self::GROUP_READ, self::GROUP_WRITE]),
    ]
    private int $term = 0;

    #[
        ORM\Column(
            name: 'interest_rate', type: Types::SMALLINT, length: 5,
            options: ['unsigned' => true, 'comment' => 'in basis points'],
        ),
        Assert\GreaterThanOrEqual(value: 0, message: 'Interest rate should be greater than or equal to 0'),
        Assert\Length(max: 5, maxMessage: 'Interest rate is too big. It should have 5 digits or less'),
        Groups([self::GROUP_LIST, self::GROUP_READ, self::GROUP_WRITE]),
    ]
    private int $interestRate = 0;

    #[
        ORM\Column(
            name: 'default_euribor_rate', type: Types::SMALLINT, length: 5,
            options: ['unsigned' => true, 'comment' => 'in basis points'],
        ),
        Assert\GreaterThanOrEqual(value: 0, message: 'Default euribor rate should be greater than or equal to 0'),
        Assert\Length(max: 5, maxMessage: 'Default euribor rate is too big. It should have 5 digits or less'),
        Groups([self::GROUP_LIST, self::GROUP_READ, self::GROUP_WRITE]),
    ]
    private int $defaultEuriborRate = 0;

    #[
        ORM\OneToMany(
            targetEntity: Euribor::class, mappedBy: 'loan', fetch: 'EXTRA_LAZY',
            cascade: ['persist', 'remove'], orphanRemoval: true,
        ),
        ORM\OrderBy(['segmentNumber' => 'ASC']),
        Assert\Valid,
        Groups([self::GROUP_LIST, self::GROUP_READ]),
    ]
    private Collection $euribors;

    public function __construct()
    {
        $this->euribors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setTerm(int $term): self
    {
        $this->term = $term;

        return $this;
    }

    public function getTerm(): int
    {
        return $this->term;
    }

    public function setInterestRate(int $interestRate): self
    {
        $this->interestRate = $interestRate;

        return $this;
    }

    public function getInterestRate(): int
    {
        return $this->interestRate;
    }

    public function setDefaultEuriborRate(int $defaultEuriborRate): self
    {
        $this->defaultEuriborRate = $defaultEuriborRate;

        return $this;
    }

    public function getDefaultEuriborRate(): int
    {
        return $this->defaultEuriborRate;
    }

    public function addEuribor(Euribor $euribor): self
    {
        if (!$this->euribors->contains($euribor)) {
            $this->euribors->add($euribor);
            $euribor->setLoan($this);
        }

        return $this;
    }

    public function removeEuribor(Euribor $euribor): self
    {
        if ($this->euribors->contains($euribor)) {
            $this->euribors->removeElement($euribor);
            $euribor->setLoan(null);
        }

        return $this;
    }

    /** @return Collection<Euribor> */
    public function getEuribors(): Collection
    {
        return $this->euribors;
    }

    /** @return Payment[] */
    #[Groups([self::GROUP_PAYMENT_LIST])]
    public function getPayments(): array
    {
        $result = [];
        if ($this->amount === 0) {
            return $result;
        }

        $annuityPayment = Money::roundCents($this->getAnnuityPayment());
        $monthlyInterest = $this->getMonthlyInterestValue();
        $monthlyEuribor = Money::basisPointsToValue(Money::yearlyToMonthly($this->defaultEuriborRate));
        $remainingAmount = $this->amount;
        for ($segmentNr = 1; $segmentNr <= $this->term; $segmentNr++) {
            if (($segmentEuribor = $this->getEuriborForSegment($segmentNr)) !== null) {
                $monthlyEuribor = Money::basisPointsToValue(Money::yearlyToMonthly($segmentEuribor->getRate()));
            }

            $interest = Money::roundCents($remainingAmount * $monthlyInterest);
            $euribor = Money::roundCents($remainingAmount * $monthlyEuribor);
            $principal = $annuityPayment - $interest;
            if ($segmentNr === $this->term && $principal !== $remainingAmount) {
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

    public function getEuriborForSegment(int $segmentNumber): ?Euribor
    {
        return $this->euribors
            ->filter(static fn (Euribor $euribor) => $euribor->getSegmentNumber() === $segmentNumber)
            ->first() ?: null;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $existingSegmentNumbers = new ArrayCollection();
        foreach ($this->getEuribors() as $index => $euribor) {
            $segmentNr = $euribor->getSegmentNumber();
            if ($existingSegmentNumbers->contains($segmentNr)) {
                $context
                    ->buildViolation(sprintf(
                        'Euribor with segment number "%d" already exists in Loan #%s',
                        $segmentNr,
                        $this->id ? (string) $this->getId() : 'new',
                    ))
                    ->atPath(sprintf('euribor[%d].segmentNumber', $index))
                    ->addViolation();
                break;
            }

            $existingSegmentNumbers->add($segmentNr);
        }
    }

    private function getAnnuityPayment(): int|float
    {
        if ($this->term < 1) {
            throw new RangeException(sprintf('Loan term "%d" cannot be lower than 1', $this->term));
        }

        $monthlyInterest = $this->getMonthlyInterestValue();

        return $monthlyInterest > 0
            ? ($monthlyInterest * $this->amount) / (1 - (1 + $monthlyInterest) ** (-$this->term))
            : $this->amount / $this->term;
    }

    private function getMonthlyInterestValue(): float
    {
        return Money::basisPointsToValue(Money::yearlyToMonthly($this->interestRate));
    }
}
