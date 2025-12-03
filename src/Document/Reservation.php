<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;


#[ODM\Document(collection: 'reservations')]
class Reservation
{
    #[ODM\Id(strategy: 'INCREMENT', type: 'int')]
    private $reservationCode;

    #[ODM\ReferenceOne(targetDocument: Customer::class)]
    private $customer;

    #[ODM\ReferenceOne(targetDocument: Hotel::class)]
    private $hotel;

    #[ODM\ReferenceOne(targetDocument: Room::class)]
    private $room;

    #[ODM\Field(type: 'date')]
    private $startDate;

    #[ODM\Field(type: 'date')]
    private $endDate;

    // Getters & setters
    public function getReservationCode(): ?string
    {
        return $this->reservationCode;
    }
    public function setReservationCode(string $reservationCode): static
    {
        $this->reservationCode = $reservationCode;

        return $this;
    }
    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }
    public function setCustomer(Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }
    public function getHotel(): ?Hotel
    {
        return $this->hotel;
    }
    public function setHotel(Hotel $hotel): static
    {
        $this->hotel = $hotel;

        return $this;
    }
    public function getRoom(): ?Room
    {
        return $this->room;
    }
    public function setRoom(Room $room): static
    {
        $this->room = $room;

        return $this;
    }
    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }
    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }
    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }
    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }
    
}
