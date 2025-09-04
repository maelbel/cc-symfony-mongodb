<?php

namespace App\Service;

use App\Document\Room;
use App\Document\Hotel;
use Doctrine\ODM\MongoDB\DocumentManager;
use Knp\Component\Pager\PaginatorInterface;
use MongoDB\BSON\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class RoomService
{
	private DocumentManager $dm;
	private $roomRepository;
	private PaginatorInterface $paginator;

	public function __construct(DocumentManager $dm, PaginatorInterface $paginator)
	{
		$this->dm = $dm;
		$this->roomRepository = $this->dm->getRepository(Room::class);
		$this->paginator = $paginator;
	}

	/**
	 * Get all rooms with pagination
	 *
	 * @param Request $request
	 * @return array
	 */
	public function getAll(Request $request): array
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

        return [
            'data' => $data,
            'pagination' => [
                'currentPage' => $pagination->getCurrentPageNumber(),
                'totalItems' => $pagination->getTotalItemCount(),
                'itemsPerPage' => $pagination->getItemNumberPerPage(),
			],
			'paginationObject'=> $pagination,
        ];
	}

	/**
	 * Browse rooms with filters and pagination
	 *
	 * @param Request $request
	 * @return array
	 */
	public function browse(Request $request): array
	{
		$queryBuilder = $this->roomRepository->createQueryBuilder();

		$type = $request->query->get('type');
		$floor = $request->query->get('floor');
		$hotelCode = $request->query->get('hotelCode');

		if ($type) {
			$queryBuilder->field('type')->equals(new Regex($type, 'i'));
		}
		if ($floor) {
			$queryBuilder->field('floor')->equals((int)$floor);
		}
		if ($hotelCode) {
			$hotel = $this->dm->getRepository(Hotel::class)->findOneBy(['hotelCode' => $hotelCode]);
			if ($hotel) {
				$queryBuilder->field('hotel')->references($hotel);
			} else {
				// No hotel found return empty results
				return [
					'data' => [],
					'pagination' => [
						'currentPage' => 1,
						'totalItems' => 0,
						'itemsPerPage' => 10,
					],
					'paginationObject' => null,
				];
			}
		}
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
		$responseData['paginationObject'] = $pagination;

        return $responseData;
	}

		/**
	 * Add a new room
	 *
	 * @param array $data
	 * @param Hotel $hotel
	 * @return array
	 */
	public function add(array $data, $hotel): array
	{
		$room = new Room();
		$room->setFloor((int)$data['floor']);
		$room->setType($data['type']);
		$room->setNumberOfBeds((int)$data['numberOfBeds']);
		$room->setHotel($hotel);

		$this->dm->persist($room);
		$this->dm->flush();

		return [
			'message' => 'Room created successfully',
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
	 * Get a room by its code
	 *
	 * @param string $roomCode
	 * @return Room|null
	 */
	public function getByCode(string $roomCode): ?Room
	{
		return $this->roomRepository->find($roomCode);
	}

	/**
	 * Update a room
	 *
	 * @param Room $room
	 * @param array $data
	 * @param Hotel $hotel
	 * @return array
	 */
	public function update(Room $room, array $data, $hotel): array
	{
		$room->setFloor((int)$data['floor']);
		$room->setType($data['type']);
		$room->setNumberOfBeds((int)$data['numberOfBeds']);
		$room->setHotel($hotel);

		$this->dm->flush();

		return [
			'message' => 'Room updated successfully',
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
	 * Delete a room
	 *
	 * @param Room $room
	 * @return array
	 */
	public function delete(Room $room): array
	{
		$this->dm->remove($room);
		$this->dm->flush();

		return ['message' => 'Room deleted successfully'];
	}
}
