<?php

namespace App\Entity;

use App\Entity\Traits\Timestampable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Job
{
    use Timestampable;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $company;

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
    private string $position;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $positionId = null;

    #[ORM\Column(type: 'bigint', nullable: true)]
    private ?int $adId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToMany(targetEntity: GeoCity::class, inversedBy: 'jobs')]
    #[ORM\JoinTable(name: 'job_geo_city')]
    private Collection $geoCities;

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $importedAt;

    public function __construct()
    {
        $this->geoCities = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompany(): string
    {
        return $this->company;
    }

    public function setCompany(string $company): self
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

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
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

    /** @return Collection<int, GeoCity> */
    public function getGeoCities(): Collection
    {
        return $this->geoCities;
    }

    public function addGeoCity(GeoCity $geoCity): self
    {
        if (!$this->geoCities->contains($geoCity)) {
            $this->geoCities->add($geoCity);
            $geoCity->addJob($this);
        }
        return $this;
    }

    public function removeGeoCity(GeoCity $geoCity): self
    {
        if ($this->geoCities->removeElement($geoCity)) {
            $geoCity->removeJob($this);
        }
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImportedAt(): \DateTimeInterface
    {
        return $this->importedAt;
    }

    public function setImportedAt(\DateTimeImmutable $importedAt): self
    {
        $this->importedAt = $importedAt;

        return $this;
    }
}
