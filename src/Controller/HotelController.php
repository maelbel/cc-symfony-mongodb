<?php

namespace App\Controller;

use App\Service\HotelService;
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
    private $hotelService;

    public function __construct(HotelService $hotelService)
    {
        $this->hotelService = $hotelService;
    }

    #[Route('/hotel', name: 'hotel_index', methods: ['GET'])]
    public function getAll(Request $request): Response
    {
        $data = $this->hotelService->getAll($request);
        return new JsonResponse([
            'data' => $data['data'],
            'pagination' => $data['pagination'],
        ]);
    }

    #[Route('/hotel/browse', name: 'hotel_browse', methods: ['GET'])]
    public function browse(Request $request): Response
    {
        try {
            $data = $this->hotelService->browse($request);
            return new JsonResponse([
                'data' => $data['data'],
                'pagination' => $data['pagination'],
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
    #[Route('/hotel/add', name: 'hotel_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->hotelService->add($data);
            return $this->json($result, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
    #[Route('/hotel/getByCode/{codeHotel}', name: 'hotel_read', methods: ['GET'])]
    public function getByCode(string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        return $this->json([
            'hotelCode' => $hotel->getHotelCode(),
            'hotelName' => $hotel->getHotelName(),
            'hotelAddress' => $hotel->getHotelAddress(),
            'hotelCategory' => $hotel->getHotelCategory(),
        ]);
    }

    #[Route('/hotel/update/{codeHotel}', name: 'hotel_edit', methods: ['PUT'])]
    public function update(string $codeHotel, Request $request): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->hotelService->update($hotel, $data);
            return $this->json($result);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/hotel/delete/{codeHotel}', name: 'hotel_delete', methods: ['DELETE'])]
    public function delete(string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $result = $this->hotelService->delete($hotel);
        return $this->json($result);
    }
    #[Route('/hotel/addRoomToHotel', name: 'hotel_add_room_to_hotel', methods: ['POST'])]
    public function addRoomToHotel(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $hotelCode = $data['hotelCode'] ?? null;
        $hotel = $this->hotelService->getByCode($hotelCode);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        try {
            $result = $this->hotelService->addRoomToHotel($data, $hotel);
            return $this->json($result, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/hotel/{codeHotel}/rooms', name: 'hotel_get_rooms', methods: ['GET'])]
    public function getRoomsByHotel(string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $data = $this->hotelService->getRoomsByHotel($hotel);
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
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            return $this->json(['error' => 'Hotel Not Found'], 404);
        }

        $data = $this->hotelService->getRoomsTypeByHotel($hotel);
        return new JsonResponse($data);
    }
}
