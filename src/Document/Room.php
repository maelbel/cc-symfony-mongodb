<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ODM\Document(collection: 'rooms')]
class Room
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    protected ?int $roomCode = null;

    #[ODM\Field(type: 'int')]
    protected ?int $floor = null;

    #[ODM\Field(type: 'string')]
    protected ?string $type = null;

    #[ODM\Field(type: 'int')]
    protected ?int $numberOfBeds = null;

    #[ODM\ReferenceOne(targetDocument: Hotel::class, inversedBy: 'rooms', cascade: ['persist'])]
    protected ?Hotel $hotel = null;

    public function getRoomCode(): ?int {
        return $this->roomCode;
    }
    public function setRoomCode(?int $roomCode): self {
        $this->roomCode = $roomCode;
        return $this;
    }
    public function getFloor(): ?int {
        return $this->floor;
    }
    public function setFloor(?int $floor): self {
        $this->floor = $floor;
        return $this;
    }
    public function getType(): ?string {
        return $this->type;
    }
    public function setType(?string $type): self {
        $this->type = $type; return $this;
    }
    public function getNumberOfBeds(): ?int {
        return $this->numberOfBeds;
    }
    public function setNumberOfBeds(?int $numberOfBeds): self {
        $this->numberOfBeds = $numberOfBeds;
        return $this;
    }
    public function getHotel(): ?Hotel {
        return $this->hotel;
    }
    public function setHotel(?Hotel $hotel): self {
        $this->hotel = $hotel;
        return $this;
    }
}
