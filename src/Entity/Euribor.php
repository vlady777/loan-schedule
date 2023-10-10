<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EuriborRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[
    ORM\Entity(repositoryClass: EuriborRepository::class),
    ORM\Table(name: 'euribor'),
    ORM\UniqueConstraint(name: 'loan_id_segment_number', columns: ['loan_id', 'segment_number']),
    ORM\Index(name: 'load_id', columns: ['loan_id']),
    UniqueEntity(
        fields: ['loan', 'segmentNumber'], errorPath: 'segmentNumber',
        message: 'Segment number already exists in Loan',
    ),
]
class Euribor
{
    #[
        ORM\Id,
        ORM\GeneratedValue,
        ORM\Column(name: 'id', type: Types::INTEGER, options: ['unsigned' => true]),
    ]
    private ?int $id = null;

    #[
        ORM\Column(name: 'segment_number', type: Types::SMALLINT, length: 5, options: ['unsigned' => true]),
        Assert\GreaterThan(0),
        Assert\Length(max: 5),
    ]
    private int $segmentNumber = 0;

    #[
        ORM\Column(
            name: 'rate', type: Types::SMALLINT, length: 5,
            options: ['unsigned' => true, 'comment' => 'in basis points']
        ),
        Assert\GreaterThanOrEqual(0),
        Assert\Length(max: 5),
    ]
    private int $rate = 0;

    #[
        ORM\ManyToOne(targetEntity: Loan::class, inversedBy: 'euribors'),
        ORM\JoinColumn(name: 'loan_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE'),
        Assert\NotNull(message: 'Please provide Loan'),
    ]
    private ?Loan $loan = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setSegmentNumber(int $segmentNumber): self
    {
        $this->segmentNumber = $segmentNumber;

        return $this;
    }

    public function getSegmentNumber(): int
    {
        return $this->segmentNumber;
    }

    public function setRate(int $rate): self
    {
        $this->rate = $rate;

        return $this;
    }

    public function getRate(): int
    {
        return $this->rate;
    }

    public function setLoan(?Loan $loan): self
    {
        if ($this->loan !== $loan) {
            $this->loan?->removeEuribor($this);
            $loan?->addEuribor($this);
            $this->loan = $loan;
        }

        return $this;
    }

    public function getLoan(): ?Loan
    {
        return $this->loan;
    }
}
