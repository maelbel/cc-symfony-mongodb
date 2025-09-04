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
use Knp\Component\Pager\PaginatorInterface;
use App\Service\RoomService;

class RoomController extends AbstractController
{
    private DocumentManager $dm;
    private LoggerInterface $logger;
    private $hotelRepository;
    private $paginator;
    private RoomService $roomService;

    public function __construct(DocumentManager $dm, LoggerInterface $logger, PaginatorInterface $paginator, RoomService $roomService)
    {
        $this->dm = $dm;
        $this->logger = $logger;
        $this->hotelRepository = $this->dm->getRepository(Hotel::class);
        $this->paginator = $paginator;
        $this->roomService = $roomService;
    }

    #[Route('/room', name: 'room_index', methods: ['GET'])]
    public function getAll(Request $request): Response
    {
        return new JsonResponse($this->roomService->getAll($request));
    }

    #[Route('/room/browse', name: 'room_browse', methods: ['GET'])]
    public function browse(Request $request): Response
    {
        return new JsonResponse($this->roomService->browse($request));
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
        return $this->json($this->roomService->add($data, $hotel), 201);
    }

    #[Route('/room/getByCode/{roomCode}', name: 'room_read', methods: ['GET'])]
    public function getByCode(string $roomCode): Response
    {
        $room = $this->roomService->getByCode($roomCode);
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
        $room = $this->roomService->getByCode($roomCode);
        if (!$room) {
            return $this->json(['error' => 'Room not found'], 404);
        }
        $data = json_decode($request->getContent(), true);
        $hotel = $this->hotelRepository->find($data['hotelCode']);
        
        if (
        !$data
        || !isset($data['floor'], $data['type'], $data['numberOfBeds'], $data['hotelCode'])
        || !$hotel
        ) {
            return $this->json(['error' => 'Required fields: floor, type, numberOfBeds, hotelCode, and valid hotel'], 400);
        }
        return $this->json($this->roomService->update($room, $data, $hotel));
    }

    #[Route('/room/delete/{roomCode}', name: 'room_delete', methods: ['DELETE'])]
    public function delete(string $roomCode): Response
    {
        $room = $this->roomService->getByCode($roomCode);
        if (!$room) {
            return $this->json(['error' => 'Room not found'], 404);
        }
        return $this->json($this->roomService->delete($room));
    }
}
