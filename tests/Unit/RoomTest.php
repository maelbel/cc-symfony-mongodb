<?php
namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Document\Room;

class RoomTest extends TestCase
{
    public function testGetLabelCombinesFloorAndPaddedRoomCode()
    {
        $room = new Room();
        $room->setFloor(1);
        $room->setRoomCode(5);

        $this->assertEquals('105', $room->getLabel());
    }

    public function testGetLabelHandlesMissingValues()
    {
        $room = new Room();
        $this->assertEquals('', $room->getLabel());

        $room->setRoomCode(12);
        $this->assertEquals('12', $room->getLabel());

        $room->setFloor(2);
        $this->assertEquals('212', $room->getLabel());
    }
}
