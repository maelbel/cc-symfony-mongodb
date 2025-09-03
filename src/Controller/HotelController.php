<?php

namespace App\Controller;

use App\Document\Hotel;
use App\Document\Room;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class HotelController extends AbstractController
{
    private DocumentManager $dm;
    private LoggerInterface $logger;
    private $hotelRepository;
    private $roomRepository;
    private $paginator;

    public function __construct(DocumentManager $dm, LoggerInterface $logger, PaginatorInterface $paginator)
    {
        $this->dm = $dm;
        $this->logger = $logger;
        $this->hotelRepository = $this->dm->getRepository(Hotel::class);
        $this->roomRepository = $this->dm->getRepository(Room::class);
        $this->paginator = $paginator;
    }

    #[Route('/hotel', name: 'hotel_index', methods: ['GET'])]
    public function getAll(Request $request): Response
    {
        $query = $this->hotelRepository->createQueryBuilder()->getQuery();
        $pagination = $this->paginator->paginate($query, $request->query->getInt('page', 1), 10);

        $data = array_map(fn($hotel) => [
                'hotelCode' => $hotel->getHotelCode(),
                'hotelName' => $hotel->getHotelName(),
                'hotelCategory' => $hotel->getHotelCategory(),
                'hotelAddress' => $hotel->getHotelAddress(),
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

    #[Route('/hotel/browse', name: 'hotel_browse', methods: ['GET'])]
    public function browse(Request $request): Response
    {
        $hotelRepository = $this->hotelRepository;
        $queryBuilder = $hotelRepository->createQueryBuilder();
        $responseData = ['data' => [], 'pagination' => []];

        //params from URL
        $category = $request->query->get('hotelCategory');
        $searchTerm = $request->query->get('hotelName');
        $searchByAddress = $request->query->get('hotelAddress');

        // add criteria based on parameters
        if ($category) {
            // Validate that category is a string of stars (e.g., "*****")
            if (preg_match('/^\*+$/', $category)) {
                $queryBuilder->field('hotelCategory')->equals($category);
            } else {
                return $this->json(['error' => 'Category Hotel must contain only asterisks ("*", "**", "***"...)'], 400);
            }
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

        $responseData['data'] = array_map(fn($hotel) => [
            'hotelCode'     => $hotel->getHotelCode(),
            'hotelName'     => $hotel->getHotelName(),
            'hotelAddress'  => $hotel->getHotelAddress(),
            'hotelCategory' => $hotel->getHotelCategory(),
        ], $pagination->getItems());

        $responseData['pagination'] = [
            'currentPage' => $pagination->getCurrentPageNumber(),
            'totalItems' => $pagination->getTotalItemCount(),
            'itemsPerPage' => $pagination->getItemNumberPerPage(),
        ];

        return new JsonResponse($responseData);
    }
    #[Route('/hotel/add', name: 'hotel_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['hotelName'], $data['hotelAddress'], $data['hotelCategory'])) {
            return $this->json(['error' => 'Required Field : hotelName, hotelAddress, hotelCategory'], 400);
        }

        $hotel = new Hotel();
        $hotel->setHotelName($data['hotelName']);
        $hotel->setHotelAddress($data['hotelAddress']);
        $hotel->setHotelCategory($data['hotelCategory']);

        $this->dm->persist($hotel);
        $this->dm->flush();

        return $this->json([
            'message' => 'Hotel created successfully',
            'hotel'   => [
                'hotelCode'     => $hotel->getHotelCode(),
                'hotelName'      => $hotel->getHotelName(),
                'hotelAddress'  => $hotel->getHotelAddress(),
                'hotelCategory'=> $hotel->getHotelCategory(),
            ]
        ], 201);
    }
    #[Route('/hotel/getByCode/{codeHotel}', name: 'hotel_read', methods: ['GET'])]
    public function getByCode(string $codeHotel): Response
    {
        $hotel = $this->hotelRepository->find($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        return $this->json([
            'hotelCode'     => $hotel->getHotelCode(),
            'hotelName'      => $hotel->getHotelName(),
            'hotelAddress'  => $hotel->getHotelAddress(),
            'hotelCategory'=> $hotel->getHotelCategory(),
        ]);
    }

    #[Route('/hotel/update/{codeHotel}', name: 'hotel_edit', methods: ['PUT'])]
    public function update(string $codeHotel, Request $request): Response
    {
        $hotel = $this->hotelRepository->find($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['hotelName'], $data['hotelAddress'], $data['hotelCategory'])) {
            return $this->json(['error' => 'Required Field : nomHotel, adresseHotel, categorieHotel'], 400);
        }

        $hotel->setHotelName($data['hotelName']);
        $hotel->setHotelAddress($data['hotelAddress']);
        $hotel->setHotelCategory($data['hotelCategory']);

        $this->dm->flush();

        return $this->json([
            'message' => 'hotel updated successfully',
            'hotel'   => [
                'hotelCode'     => $hotel->getHotelCode(),
                'hotelName'      => $hotel->getHotelName(),
                'hotelAddress'  => $hotel->getHotelAddress(),
                'hotelCategory'=> $hotel->getHotelCategory(),
            ]
        ]);
    }

    #[Route('/hotel/delete/{codeHotel}', name: 'hotel_delete', methods: ['DELETE'])]
    public function delete(string $codeHotel): Response
    {
        $hotel = $this->hotelRepository->find($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $this->dm->remove($hotel);
        $this->dm->flush();

        return $this->json(['message' => 'hotel deleted successfully']);
    }
    #[Route('/hotel/addRoomToHotel', name: 'hotel_add_room_to_hotel', methods: ['POST'])]
    public function addRoomToHotel(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['hotelCode'], $data['floor'], $data['type'], $data['numberOfBeds'])) {
            return $this->json(['error' => 'Required Field : hotelCode, floor, type, numberOfBeds'], 400);
        }

        $hotel = $this->hotelRepository->find($data['hotelCode']);
        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $room = new Room();
        $room->setFloor($data['floor']);
        $room->setType($data['type']);
        $room->setNumberOfBeds($data['numberOfBeds']);
        $room->setHotel($hotel);

        $this->dm->persist($room);
        $this->dm->flush();

        return $this->json([
            'message' => 'Room added to hotel successfully',
            'room'    => [
                'roomCode'     => $room->getRoomCode(),
                'floor'         => $room->getFloor(),
                'type'          => $room->getType(),
                'numberOfBeds' => $room->getNumberOfBeds(),
                'hotelCode'   => $hotel->getHotelCode(),
            ]
        ], 201);
    }

    #[Route('/hotel/{codeHotel}/rooms', name: 'hotel_get_rooms', methods: ['GET'])]
    public function getRoomsByHotel(string $codeHotel): Response
    {
        $hotel = $this->hotelRepository->find($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $rooms =$this->roomRepository->findBy(['hotel' => $hotel]);

        $data = [];
        foreach ($rooms as $room) {
            $data[] = [
                'roomCode'     => $room->getRoomCode(),
                'floor'         => $room->getFloor(),
                'type'          => $room->getType(),
                'numberOfBeds' => $room->getNumberOfBeds(),
            ];
        }

        return new JsonResponse($data);
    }
    
    /**
     * Retrieves all unique room types for a given hotel
     *
     * @param string $codeHotel The hotel's code
     * @return Response List of unique room types available in the hotel
     *
     * This method first checks if the hotel exists
     * Then, it fetches all rooms associated with the hotel,
     * extracts their types, removes duplicates, and returns the list of unique types
     */
    #[Route('/hotel/{codeHotel}/roomsType', name:'hotel_get_hotel_rooms_type', methods: ['GET'])]
    public function getRoomsTypeByHotel(string $codeHotel): Response
    {
        $hotel = $this->hotelRepository->find($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $rooms =$this->roomRepository->findBy(['hotel' => $hotel]);

        $types = [];
        foreach ($rooms as $room) {
            $types[] = $room->getType();
        }
        $uniqueCategories = array_values(array_unique($types));

        return new JsonResponse($uniqueCategories);
    }
}
