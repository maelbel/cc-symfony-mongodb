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

class RoomController extends AbstractController
{
    private DocumentManager $dm;
    private LoggerInterface $logger;
    private $roomRepository;
    private $hotelRepository;
    private $paginator;

    public function __construct(DocumentManager $dm, LoggerInterface $logger, PaginatorInterface $paginator)
    {
        $this->dm = $dm;
        $this->logger = $logger;
        $this->roomRepository = $this->dm->getRepository(Room::class);
        $this->hotelRepository = $this->dm->getRepository(Hotel::class);
        $this->paginator = $paginator;
    }

    #[Route('/room', name: 'room_index', methods: ['GET'])]
    public function getAll(Request $request): Response
    {
        $query = $this->roomRepository->createQueryBuilder()->getQuery();
        $pagination = $this->paginator->paginate($query, $request->query->getInt('page', 1), 10);

        $data = array_map(fn($room) => [
            'roomCode' => $room->getRoomCode(),
            'floor' => $room->getFloor(),
            'type' => $room->getType(),
            'numberOfBeds' => $room->getNumberOfBeds(),
            'hotelCode' => $room->getHotel() ? $room->getHotel()->getHotelCode() : null,
        ], $pagination->getItems());

        return new JsonResponse([
            'data' => $data,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalItems' => $pagination->getTotalItemCount(),
                'itemsPerPage' => $pagination->getItemNumberPerPage(),
            ]
        ]);
    }

    #[Route('/room/browse', name: 'room_browse', methods: ['GET'])]
    public function browse(Request $request): Response
    {
        $queryBuilder = $this->roomRepository->createQueryBuilder();
        $responseData = ['data' => [], 'pagination' => []];

        $this->applyBrowseFilters($queryBuilder, $request);
        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $responseData['data'] = array_map(fn($room) => [
            'roomCode' => $room->getRoomCode(),
            'floor' => $room->getFloor(),
            'type' => $room->getType(),
            'numberOfBeds' => $room->getNumberOfBeds(),
            'hotelCode' => $room->getHotel() ? $room->getHotel()->getHotelCode() : null,
        ], $pagination->getItems());

        $responseData['pagination'] = [
            'currentPage' => $pagination->getCurrentPageNumber(),
            'totalItems' => $pagination->getTotalItemCount(),
            'itemsPerPage' => $pagination->getItemNumberPerPage(),
        ];

        return new JsonResponse($responseData);
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
