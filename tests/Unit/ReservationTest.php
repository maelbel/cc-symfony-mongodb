<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Document\Reservation;

class ReservationTest extends TestCase
{
    public function testDefaultStatusIsConfirmed()
    {
        $r = new Reservation();
        $this->assertEquals('confirmed', $r->getStatus());
    }

    public function testSetAndGetStatus()
    {
        $r = new Reservation();
        $r->setStatus('cancelled');
        $this->assertEquals('cancelled', $r->getStatus());
    }
}
