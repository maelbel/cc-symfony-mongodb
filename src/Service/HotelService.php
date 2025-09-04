<?php
namespace App\Service;

use App\Document\Hotel;
use App\Document\Room;
use Doctrine\ODM\MongoDB\DocumentManager;
use Knp\Component\Pager\PaginatorInterface;
use MongoDB\BSON\Regex;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class HotelService
{
    private DocumentManager $dm;
    private LoggerInterface $logger;
    private $hotelRepository;
    private $roomRepository;
    private PaginatorInterface $paginator;

    public function __construct(DocumentManager $dm, LoggerInterface $logger, PaginatorInterface $paginator)
    {
        $this->dm = $dm;
        $this->logger = $logger;
        $this->hotelRepository = $this->dm->getRepository(Hotel::class);
        $this->roomRepository = $this->dm->getRepository(Room::class);
        $this->paginator = $paginator;
    }

    /**
     * Get all hotels with pagination
     *
     * @param Request $request
     * @return array
     */
    public function getAll(Request $request): array
    {
        $query = $this->hotelRepository->createQueryBuilder()->getQuery();
        $pagination = $this->paginator->paginate($query, $request->query->getInt('page', 1), 10);

        $data = array_map(fn($hotel) => [
            'hotelCode' => $hotel->getHotelCode(),
            'hotelName' => $hotel->getHotelName(),
            'hotelCategory' => $hotel->getHotelCategory(),
            'hotelAddress' => $hotel->getHotelAddress(),
        ], $pagination->getItems());

        return [
            'data' => $data,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalItems' => $pagination->getTotalItemCount(),
                'itemsPerPage' => $pagination->getItemNumberPerPage(),
            ],
            'paginationObject' => $pagination,
        ];
    }

    /**
     * Browse hotels with filters and pagination
     *
     * @param Request $request
     * @return array
     */
    public function browse(Request $request): array
    {
        $queryBuilder = $this->hotelRepository->createQueryBuilder();

        $category = $request->query->get('hotelCategory');
        $searchTerm = $request->query->get('hotelName');
        $searchByAddress = $request->query->get('hotelAddress');

        if ($category) {
            if (!preg_match('/^\*+$/', $category)) {
                throw new \InvalidArgumentException('Category Hotel must contain only asterisks ("*", "**", "***"...)');
            }
            $queryBuilder->field('hotelCategory')->equals($category);
        }

        if ($searchTerm) {
            $queryBuilder->field('hotelName')->equals(new Regex($searchTerm, 'i'));
        }

        if ($searchByAddress) {
            $queryBuilder->field('hotelAddress')->equals(new Regex($searchByAddress, 'i'));
        }

        $pagination = $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $data = array_map(fn($hotel) => [
            'hotelCode' => $hotel->getHotelCode(),
            'hotelName' => $hotel->getHotelName(),
            'hotelAddress' => $hotel->getHotelAddress(),
            'hotelCategory' => $hotel->getHotelCategory(),
        ], $pagination->getItems());

        return [
            'data' => $data,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalItems' => $pagination->getTotalItemCount(),
                'itemsPerPage' => $pagination->getItemNumberPerPage(),
            ],
            'paginationObject' => $pagination,
        ];
    }

    /**
     * Add a new hotel
     *
     * @param array $data
     * @return array
     */
    public function add(array $data): array
    {
        if (!isset($data['hotelName'], $data['hotelAddress'], $data['hotelCategory'])) {
            throw new \InvalidArgumentException('Required Field: hotelName, hotelAddress, hotelCategory');
        }

        $hotel = new Hotel();
        $hotel->setHotelName($data['hotelName']);
        $hotel->setHotelAddress($data['hotelAddress']);
        $hotel->setHotelCategory($data['hotelCategory']);

        $this->dm->persist($hotel);
        $this->dm->flush();

        return [
            'message' => 'Hotel created successfully',
            'hotel' => [
                'hotelCode' => $hotel->getHotelCode(),
                'hotelName' => $hotel->getHotelName(),
                'hotelAddress' => $hotel->getHotelAddress(),
                'hotelCategory' => $hotel->getHotelCategory(),
            ]
        ];
    }

    /**
     * Get a hotel by its code
     *
     * @param string $hotelCode
     * @return Hotel|null
     */
    public function getByCode(string $hotelCode): ?Hotel
    {
        return $this->hotelRepository->find($hotelCode);
    }

    /**
     * Update a hotel
     *
     * @param Hotel $hotel
     * @param array $data
     * @return array
     */
    public function update(Hotel $hotel, array $data): array
    {
        if (!isset($data['hotelName'], $data['hotelAddress'], $data['hotelCategory'])) {
            throw new \InvalidArgumentException('Required Field: hotelName, hotelAddress, hotelCategory');
        }

        $hotel->setHotelName($data['hotelName']);
        $hotel->setHotelAddress($data['hotelAddress']);
        $hotel->setHotelCategory($data['hotelCategory']);

        $this->dm->flush();

        return [
            'message' => 'Hotel updated successfully',
            'hotel' => [
                'hotelCode' => $hotel->getHotelCode(),
                'hotelName' => $hotel->getHotelName(),
                'hotelAddress' => $hotel->getHotelAddress(),
                'hotelCategory' => $hotel->getHotelCategory(),
            ]
        ];
    }

    /**
     * Delete a hotel
     *
     * @param Hotel $hotel
     * @return array
     */
    public function delete(Hotel $hotel): array
    {
        $this->dm->remove($hotel);
        $this->dm->flush();

        return ['message' => 'Hotel deleted successfully'];
    }

    /**
     * Add a room to a hotel
     *
     * @param array $data
     * @param Hotel $hotel
     * @return array
     */
    public function addRoomToHotel(array $data, Hotel $hotel): array
    {
        if (!isset($data['floor'], $data['type'], $data['numberOfBeds'])) {
            throw new \InvalidArgumentException('Required Field: floor, type, numberOfBeds');
        }

        $room = new Room();
        $room->setFloor((int)$data['floor']);
        $room->setType($data['type']);
        $room->setNumberOfBeds((int)$data['numberOfBeds']);
        $room->setHotel($hotel);

        $this->dm->persist($room);
        $this->dm->flush();

        return [
            'message' => 'Room added to hotel successfully',
            'room' => [
                'roomCode' => $room->getRoomCode(),
                'floor' => $room->getFloor(),
                'type' => $room->getType(),
                'numberOfBeds' => $room->getNumberOfBeds(),
                'hotelCode' => $hotel->getHotelCode(),
            ]
        ];
    }
    /**
     * Remove a room from a hotel
     *
     * @param string $roomCode
     * @param Hotel $hotel
     * @return array
     * @throws \InvalidArgumentException if the room is not found or not associated with the hotel
     */
    public function removeRoomFromHotel(string $roomCode, Hotel $hotel): array
    {
        $room = $this->roomRepository->find($roomCode);
        if (!$room) {
            throw new \InvalidArgumentException('Room not found');
        }

        if ($room->getHotel() !== $hotel) {
            throw new \InvalidArgumentException('Room does not belong to the specified hotel');
        }

        $this->dm->remove($room);
        $this->dm->flush();

        return ['message' => 'Room removed from hotel successfully'];
    }

    /**
     * Get all rooms for a hotel
     *
     * @param Hotel $hotel
     * @return array
     */
    public function getRoomsByHotel(Hotel $hotel): array
    {
        $rooms = $this->roomRepository->findBy(['hotel' => $hotel]);

        return array_map(fn($room) => [
            'roomCode' => $room->getRoomCode(),
            'floor' => $room->getFloor(),
            'type' => $room->getType(),
            'numberOfBeds' => $room->getNumberOfBeds(),
        ], $rooms);
    }

    /**
     * Get unique room types for a hotel
     *
     * @param Hotel $hotel
     * @return array
     */
    public function getRoomsTypeByHotel(Hotel $hotel): array
    {
        $rooms = $this->roomRepository->findBy(['hotel' => $hotel]);
        $types = array_map(fn($room) => $room->getType(), $rooms);
        return array_values(array_unique($types));
    }
}
