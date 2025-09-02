<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ODM\Document(collection: 'hotels')]
class Hotel
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    protected ?int $hotelCode = null;

    #[ODM\Field]
    protected ?string $hotelName = null;

    #[ODM\Field]
    protected ?string $hotelAddress = null;

    #[ODM\Field]
    protected ?string $hotelCategory = null;

    #[ODM\ReferenceMany(targetDocument: Chambre::class, mappedBy: 'hotel', cascade: ['all'])]
    protected Collection $rooms;

    public function __construct()
    {
        $this->rooms = new ArrayCollection();
    }

    // Getters and setters for all fields
    public function getHotelCode(): ?int {
        return $this->hotelCode;
    }
    public function setHotelCode(int $hotelCode): self {
        $this->hotelCode = $hotelCode;
        return $this;
    }
    public function getHotelName(): ?string {
        return $this->hotelName;
    }
    public function setHotelName(?string $hotelName): self {
        $this->hotelName = $hotelName;
        return $this;
    }
    public function getHotelAddress(): ?string {
        return $this->hotelAddress;
    }
    public function setHotelAddress(?string $hotelAddress): self {
        $this->hotelAddress = $hotelAddress;
        return $this;
    }
    public function getHotelCategory(): ?string {
        return $this->hotelCategory;
    }
    public function setHotelCategory(?string $hotelCategory): self {
        $this->hotelCategory = $hotelCategory;
        return $this;
    }
    public function getRooms(): Collection {
        return $this->rooms;
    }
    public function addRoom(Room $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms->add($room);
            $room->setHotel($this);
        }
        return $this;
    }
    public function removeRoom(Room $room): self
    {
        if ($this->rooms->removeElement($room) && $room->getHotel() === $this) {
            $room->setHotel(null);
        }
        return $this;
    }
}
