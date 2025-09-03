<?php

namespace App\Controller;

use App\Document\Room;
use App\Document\Hotel;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoomController extends AbstractController
{
    private DocumentManager $dm;
    private LoggerInterface $logger;
    private $roomRepository;
    private $hotelRepository;

    public function __construct(DocumentManager $dm, LoggerInterface $logger)
    {
        $this->dm = $dm;
        $this->logger = $logger;
        $this->roomRepository = $this->dm->getRepository(Room::class);
        $this->hotelRepository = $this->dm->getRepository(Hotel::class);
    }

    #[Route('/room', name: 'room_index', methods: ['GET'])]
    public function getAll(): Response
    {
        $rooms = $this->roomRepository->findAll();

        $data = [];
        foreach ($rooms as $room) {
            $data[] = [
                'roomCode' => $room->getRoomCode(),
                'floor' => $room->getFloor(),
                'type' => $room->getType(),
                'numberOfBeds' => $room->getNumberOfBeds(),
                'hotelCode' => $room->getHotel() ? $room->getHotel()->getHotelCode() : null,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/room/browse', name: 'room_browse', methods: ['GET'])]
    public function browse(Request $request): Response
    {
        $queryBuilder = $this->roomRepository->createQueryBuilder();

        // Params from URL
        $type = $request->query->get('type');
        $floor = $request->query->get('floor');
        $numberOfBeds = $request->query->get('numberOfBeds');
        $hotelCode = $request->query->get('hotelCode');

        // Add criteria based on parameters
        if ($type) {
            $queryBuilder->field('type')->equals(new Regex($type, 'i'));
        }

        if ($floor) {
            $queryBuilder->field('floor')->equals((int)$floor);
        }

        if ($numberOfBeds) {
            $queryBuilder->field('numberOfBeds')->equals((int)$numberOfBeds);
        }

        if ($hotelCode) {
            $hotel = $this->hotelRepository->find($hotelCode);
            if ($hotel) {
                $queryBuilder->field('hotel')->references($hotel);
            } else {
                return $this->json(['error' => 'Hotel not found'], 404);
            }
        }

        $rooms = $queryBuilder->getQuery()->execute();

        $result = [];
        foreach ($rooms as $room) {
            $result[] = [
                'roomCode' => $room->getRoomCode(),
                'floor' => $room->getFloor(),
                'type' => $room->getType(),
                'numberOfBeds' => $room->getNumberOfBeds(),
                'hotelCode' => $room->getHotel() ? $room->getHotel()->getHotelCode() : null,
            ];
        }

        return $this->json($result);
    }

    #[Route('/room/add', name: 'room_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['floor'], $data['type'], $data['numberOfBeds'], $data['hotelCode'])) {
            return $this->json(['error' => 'Required fields: floor, type, numberOfBeds, hotelCode'], 400);
        }

        $hotel = $this->hotelRepository->find($data['hotelCode']);
        if (!$hotel) {
            return $this->json(['error' => 'Hotel not found'], 404);
        }

        $room = new Room();
        $room->setFloor((int)$data['floor']);
        $room->setType($data['type']);
        $room->setNumberOfBeds((int)$data['numberOfBeds']);
        $room->setHotel($hotel);

        $this->dm->persist($room);
        $this->dm->flush();

        return $this->json([
            'message' => 'Room created successfully',
            'room' => [
                'roomCode' => $room->getRoomCode(),
                'floor' => $room->getFloor(),
                'type' => $room->getType(),
                'numberOfBeds' => $room->getNumberOfBeds(),
                'hotelCode' => $hotel->getHotelCode(),
            ]
        ], 201);
    }

    #[Route('/room/getByCode/{roomCode}', name: 'room_read', methods: ['GET'])]
    public function getByCode(string $roomCode): Response
    {
        $room = $this->roomRepository->find($roomCode);

        if (!$room) {
            return $this->json(['error' => 'Room not found'], 404);
        }

        return $this->json([
            'roomCode' => $room->getRoomCode(),
            'floor' => $room->getFloor(),
            'type' => $room->getType(),
            'numberOfBeds' => $room->getNumberOfBeds(),
            'hotelCode' => $room->getHotel() ? $room->getHotel()->getHotelCode() : null,
        ]);
    }

    #[Route('/room/update/{roomCode}', name: 'room_edit', methods: ['PUT'])]
    public function update(string $roomCode, Request $request): Response
    {
        $room = $this->roomRepository->find($roomCode);
        $responseData = ['error' => null, 'message' => 'Room updated successfully'];

        if (!$room || !$this->validateUpdateData($request, $room, $responseData)) {
            return new JsonResponse(['error' => $responseData['error'] ?: 'Room not found'], $room ? 400 : 404);
        }

        return new JsonResponse($responseData);
    }
    private function validateUpdateData(Request $request, Room $room, array &$responseData): bool
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['floor'], $data['type'], $data['numberOfBeds'], $data['hotelCode'])) {
            $responseData['error'] = 'Required fields: floor, type, numberOfBeds, hotelCode';
            return false;
        }

        $hotel = $this->hotelRepository->find($data['hotelCode']);
        if (!$hotel) {
            $responseData['error'] = 'Hotel not found';
            return false;
        }

        $room->setFloor((int)$data['floor']);
        $room->setType($data['type']);
        $room->setNumberOfBeds((int)$data['numberOfBeds']);
        $room->setHotel($hotel);

        $this->dm->flush();
        $responseData['room'] = [
            'roomCode' => $room->getRoomCode(),
            'floor' => $room->getFloor(),
            'type' => $room->getType(),
            'numberOfBeds' => $room->getNumberOfBeds(),
            'hotelCode' => $hotel->getHotelCode(),
        ];
        return true;
    }
    #[Route('/room/delete/{roomCode}', name: 'room_delete', methods: ['DELETE'])]
    public function delete(string $roomCode): Response
    {
        $room = $this->roomRepository->find($roomCode);

        if (!$room) {
            return $this->json(['error' => 'Room not found'], 404);
        }

        $this->dm->remove($room);
        $this->dm->flush();

        return $this->json(['message' => 'Room deleted successfully']);
    }
}
