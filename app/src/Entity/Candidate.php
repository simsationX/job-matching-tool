<?php

namespace App\Entity;

namespace App\Entity;

use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Candidate
{
    use Timestampable;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $position = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $industry = null;

    #[ORM\ManyToMany(targetEntity: Industry::class)]
    #[ORM\JoinTable(name: 'candidate_additional_industry')]
    private Collection $additionalIndustries;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $skills = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $additionalActivityAreas = null;

    #[ORM\ManyToMany(targetEntity: ActivityArea::class)]
    #[ORM\JoinTable(name: 'candidate_activity_area')]
    private Collection $activityAreas;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $additionalLocations = null;

    #[ORM\ManyToOne(targetEntity: Consultant::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Consultant $consultant = null;

    #[ORM\OneToMany(mappedBy: 'candidate', targetEntity: CandidateJobMatch::class, cascade: ['persist', 'remove'])]
    private Collection $matches;

    public function __construct()
    {
        $this->matches = new ArrayCollection();
        $this->activityAreas = new ArrayCollection();
        $this->additionalIndustries = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function setIndustry(?string $industry): self
    {
        $this->industry = $industry;
        return $this;
    }

    /**
     * @return Collection<int, Industry>
     */
    public function getAdditionalIndustries(): Collection
    {
        return $this->additionalIndustries;
    }

    public function addAdditionalIndustry(Industry $industry): self
    {
        if (!$this->additionalIndustries->contains($industry)) {
            $this->additionalIndustries->add($industry);
        }
        return $this;
    }

    public function removeAdditionalIndustry(Industry $industry): self
    {
        $this->additionalIndustries->removeElement($industry);
        return $this;
    }

    public function getAdditionalIndustriesText(): string
    {
        if ($this->additionalIndustries->isEmpty()) {
            return '-';
        }

        $names = $this->additionalIndustries->map(fn($i) => $i->getName())->toArray();
        return implode(', ', $names);
    }

    public function getSkills(): ?string
    {
        return $this->skills;
    }

    public function setSkills(?string $skills): self
    {
        $this->skills = $skills;
        return $this;
    }

    /**
     * @return Collection<int, ActivityArea>
     */
    public function getActivityAreas(): Collection
    {
        return $this->activityAreas;
    }

    public function addActivityArea(ActivityArea $activityArea): self
    {
        if (!$this->activityAreas->contains($activityArea)) {
            $this->activityAreas->add($activityArea);
        }
        return $this;
    }

    public function removeActivityArea(ActivityArea $activityArea): self
    {
        $this->activityAreas->removeElement($activityArea);
        return $this;
    }

    public function getAdditionalActivityAreas(): ?string
    {
        return $this->additionalActivityAreas;
    }

    public function setAdditionalActivityAreas(?string $additionalActivityAreas): self
    {
        $this->additionalActivityAreas = $additionalActivityAreas;
        return $this;
    }

    public function getActivityAreasText(): string
    {
        if ($this->activityAreas->isEmpty()) {
            return '-';
        }

        $names = $this->activityAreas->map(fn($i) => $i->getName())->toArray();
        return implode(', ', $names);
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

    public function getAdditionalLocations(): ?string
    {
        return $this->additionalLocations;
    }

    public function setAdditionalLocations(?string $additionalLocations): self
    {
        $this->additionalLocations = $additionalLocations;
        return $this;
    }

    public function getConsultant(): ?Consultant
    {
        return $this->consultant;
    }

    public function setConsultant(?Consultant $consultant): self
    {
        $this->consultant = $consultant;
        return $this;
    }

    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function addMatch(CandidateJobMatch $match): self
    {
        if (!$this->matches->contains($match)) {
            $this->matches->add($match);
            $match->setCandidate($this);
        }
        return $this;
    }

    public function removeMatch(CandidateJobMatch $match): self
    {
        if ($this->matches->removeElement($match)) {
            $match->setCandidate(null);
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
