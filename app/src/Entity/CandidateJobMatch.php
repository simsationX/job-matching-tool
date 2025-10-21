<?php

namespace App\Entity;

use App\Entity\Traits\Timestampable;
use App\Entity\Enum\CandidateJobMatchStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class CandidateJobMatch
{
    use Timestampable;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Candidate::class, inversedBy: 'matches')]
    #[ORM\JoinColumn(nullable: false)]
    private Candidate $candidate;

    #[ORM\Column(type: 'string')]
    private ?string $company = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $companyPhone = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $website = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $contactPerson = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $contactPhone = null;

    #[ORM\Column(type: 'string')]
    private ?string $position = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $positionId = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $adId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'float')]
    private ?float $score = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $foundAt;

    #[ORM\Column(type: 'boolean')]
    private bool $exported = false;

    #[ORM\Column(type: "boolean")]
    private bool $manuallyAdded = false;

    #[ORM\Column(type: 'string', length: 10, enumType: CandidateJobMatchStatus::class)]
    private CandidateJobMatchStatus $status = CandidateJobMatchStatus::ACTIVE;

    public function getId(): int
    {
        return $this->id;
    }

    public function getCandidate(): Candidate
    {
        return $this->candidate;
    }

    public function setCandidate(Candidate $candidate): self
    {
        $this->candidate = $candidate;

        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getCompanyPhone(): ?string
    {
        return $this->companyPhone;
    }

    public function setCompanyPhone(?string $companyPhone): self
    {
        $this->companyPhone = $companyPhone;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getContactPerson(): ?string
    {
        return $this->contactPerson;
    }

    public function setContactPerson(?string $contactPerson): self
    {
        $this->contactPerson = $contactPerson;

        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): self
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(?string $contactPhone): self
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPositionId(): ?int
    {
        return $this->positionId;
    }

    public function setPositionId(?int $positionId): self
    {
        $this->positionId = $positionId;

        return $this;
    }

    public function getAdId(): ?int
    {
        return $this->adId;
    }

    public function setAdId(?int $adId): self
    {
        $this->adId = $adId;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(?float $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getFoundAt(): \DateTimeInterface
    {
        return $this->foundAt;
    }

    public function setFoundAt(\DateTimeInterface $foundAt): self
    {
        $this->foundAt = $foundAt;

        return $this;
    }

    public function getStatus(): CandidateJobMatchStatus
    {
        return $this->status;
    }

    public function setStatus(CandidateJobMatchStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === CandidateJobMatchStatus::ACTIVE;
    }

    public function isIgnored(): bool
    {
        return $this->status === CandidateJobMatchStatus::IGNORED;
    }

    public function isExported(): bool
    {
        return $this->exported;
    }

    public function setExported(bool $exported): self
    {
        $this->exported = $exported;
        return $this;
    }

    public function isManuallyAdded(): bool
    {
        return $this->manuallyAdded;
    }

    public function setManuallyAdded(bool $manuallyAdded): self
    {
        $this->manuallyAdded = $manuallyAdded;
        return $this;
    }
}
