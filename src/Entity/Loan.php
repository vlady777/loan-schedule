<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LoanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[
    ORM\Entity(repositoryClass: LoanRepository::class),
    ORM\Table(name: 'loan'),
]
class Loan
{
    #[
        ORM\Id,
        ORM\GeneratedValue,
        ORM\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true]),
    ]
    private ?int $id = null;

    #[
        ORM\Column(
            name: 'amount', type: Types::INTEGER, length: 10,
            options: ['unsigned' => true, 'comment' => 'in cents']
        ),
        Assert\GreaterThan(0),
        Assert\Length(max: 10),
    ]
    private int $amount = 0;

    #[
        ORM\Column(
            name: 'term', type: Types::SMALLINT, length: 5,
            options: ['unsigned' => true, 'comment' => 'in months']
        ),
        Assert\GreaterThan(0),
        Assert\Length(max: 5),
    ]
    private int $term = 0;

    #[
        ORM\Column(
            name: 'interest_rate', type: Types::SMALLINT, length: 5,
            options: ['unsigned' => true, 'comment' => 'in basis points'],
        ),
        Assert\GreaterThan(0),
        Assert\Length(max: 5),
    ]
    private int $interestRate = 0;

    #[
        ORM\Column(
            name: 'default_euribor_rate', type: Types::SMALLINT, length: 5,
            options: ['unsigned' => true, 'comment' => 'in basis points'],
        ),
        Assert\GreaterThanOrEqual(0),
        Assert\Length(max: 5),
    ]
    private int $defaultEuriborRate = 0;

    #[
        ORM\OneToMany(
            targetEntity: Euribor::class, mappedBy: 'loan', fetch: 'EXTRA_LAZY',
            cascade: ['persist', 'remove'], orphanRemoval: true,
        ),
        Assert\Valid,
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

    public function getEuribors(): Collection
    {
        return $this->euribors;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        // TODO: implement validation for collection
    }
}
