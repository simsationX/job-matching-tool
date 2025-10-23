<?php

namespace App\Entity;

use App\Entity\Traits\Timestampable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Consultant
{
    use Timestampable;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    private string $name;

    #[ORM\Column(type: 'string')]
    private string $email;

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

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
