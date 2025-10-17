<?php

namespace App\Entity;

use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class MatchLog
{
    use Timestampable;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $runAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $exportedAt = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getRunAt(): \DateTimeInterface
    {
        return $this->runAt;
    }

    public function setRunAt(\DateTimeInterface $runAt): self
    {
        $this->runAt = $runAt;
        return $this;
    }

    public function getExportedAt(): ?\DateTimeInterface
    {
        return $this->exportedAt;
    }

    public function setExportedAt(?\DateTimeInterface $exportedAt): self
    {
        $this->exportedAt = $exportedAt;
        return $this;
    }
}
