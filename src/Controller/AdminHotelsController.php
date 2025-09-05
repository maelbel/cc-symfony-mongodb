<?php
namespace App\Controller;

use App\Service\HotelService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class AdminHotelsController extends AbstractController
{
    private HotelService $hotelService;

    public function __construct(HotelService $hotelService)
    {
        $this->hotelService = $hotelService;
    }
    #[Route('/admin/hotels', name: 'admin_hotels')]
    public function index(Request $request): Response
    {
        // Use browse if filters are present, otherwise get all hotels
        $data = $request->query->has('hotelCategory') || $request->query->has('hotelName') || $request->query->has('hotelAddress')
            ? $this->hotelService->browse($request)
            : $this->hotelService->getAll($request);

        return $this->render('admin/hotels/index.html.twig', [
            'title' => 'Admin Hotels',
            'hotels' => $data['data'],
            'pagination' => $data['paginationObject'],
            'filters' => [
                'hotelCategory' => $request->query->get('hotelCategory', ''),
                'hotelName' => $request->query->get('hotelName', ''),
                'hotelAddress' => $request->query->get('hotelAddress', ''),
            ],
        ]);
    }
    /**
     * Show form to add a new hotel
     */
    #[Route('/admin/hotels/new', name: 'admin_hotels_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('admin/hotels/new.html.twig', [
            'title' => 'Add New Hotel',
        ]);
    }
    /**
     * Create a new hotel
     */
    #[Route('/admin/hotels', name: 'admin_hotels_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = $request->request->all();

        try {
            $result = $this->hotelService->add($data);
            $this->addFlash('success', $result['message']);
            return $this->redirectToRoute('admin_hotels');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('admin_hotels_new');
        }
    }

    /**
     * View a single hotel by its code
     */
    #[Route('/admin/hotels/{codeHotel}', name: 'admin_hotels_view', methods: ['GET'])]
    public function view(string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            $this->addFlash('error', 'Hotel not found.');
            return $this->redirectToRoute('admin_hotels');
        }

        $rooms = $this->hotelService->getRoomsByHotel($hotel);
        $roomTypes = $this->hotelService->getRoomsTypeByHotel($hotel);

        return $this->render('admin/hotels/view.html.twig', [
            'title' => 'View Hotel',
            'hotel' => [
                'hotelCode' => $hotel->getHotelCode(),
                'hotelName' => $hotel->getHotelName(),
                'hotelAddress' => $hotel->getHotelAddress(),
                'hotelCategory' => $hotel->getHotelCategory(),
            ],
            'rooms' => $rooms,
            'roomTypes' => $roomTypes,
        ]);
    }

    /**
     * Show form to edit a hotel
     */
    #[Route('/admin/hotels/{codeHotel}/edit', name: 'admin_hotels_edit', methods: ['GET'])]
    public function edit(string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            $this->addFlash('error', 'Hotel not found.');
            return $this->redirectToRoute('admin_hotels');
        }

        return $this->render('admin/hotels/edit.html.twig', [
            'title' => 'Edit Hotel',
            'hotel' => [
                'hotelCode' => $hotel->getHotelCode(),
                'hotelName' => $hotel->getHotelName(),
                'hotelAddress' => $hotel->getHotelAddress(),
                'hotelCategory' => $hotel->getHotelCategory(),
            ],
        ]);
    }

    /**
     * Update a hotel
     */
    #[Route('/admin/hotels/{codeHotel}', name: 'admin_hotels_update', methods: ['POST'])]
    public function update(Request $request, string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            $this->addFlash('error', 'Hotel not found.');
            return $this->redirectToRoute('admin_hotels');
        }

        $data = $request->request->all();

        try {
            $result = $this->hotelService->update($hotel, $data);
            $this->addFlash('success', $result['message']);
            return $this->redirectToRoute('admin_hotels');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('admin_hotels_edit', ['codeHotel' => $codeHotel]);
        }
    }

    /**
     * Delete a hotel
     */
    #[Route('/admin/hotels/{codeHotel}/delete', name: 'admin_hotels_delete', methods: ['POST'])]
    public function delete(string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            $this->addFlash('error', 'Hotel not found.');
            return $this->redirectToRoute('admin_hotels');
        }

        try {
            $result = $this->hotelService->delete($hotel);
            $this->addFlash('success', $result['message']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete hotel: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_hotels');
    }

    /**
     * Show form to add a room to a hotel
     */
    #[Route('/admin/hotels/{codeHotel}/rooms/new', name: 'admin_hotels_room_new', methods: ['GET'])]
    public function newRoom(string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            $this->addFlash('error', 'Hotel not found.');
            return $this->redirectToRoute('admin_hotels');
        }

        return $this->render('admin/hotels/room_new.html.twig', [
            'title' => 'Add Room to Hotel',
            'hotelCode' => $codeHotel,
        ]);
    }

    /**
     * Add a room to a hotel
     */
    #[Route('/admin/hotels/{codeHotel}/rooms', name: 'admin_hotels_room_create', methods: ['POST'])]
    public function createRoom(Request $request, string $codeHotel): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            $this->addFlash('error', 'Hotel not found.');
            return $this->redirectToRoute('admin_hotels');
        }

        $data = $request->request->all();

        try {
            $result = $this->hotelService->addRoomToHotel($data, $hotel);
            $this->addFlash('success', $result['message']);
            return $this->redirectToRoute('admin_hotels_view', ['codeHotel' => $codeHotel]);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('admin_hotels_room_new', ['codeHotel' => $codeHotel]);
        }
    }
    /**
     * Delete a room from a hotel
     */
    #[Route('/admin/hotels/{codeHotel}/rooms/{roomCode}/delete', name: 'admin_hotels_room_delete', methods: ['POST'])]
    public function deleteRoom(string $codeHotel, string $roomCode): Response
    {
        $hotel = $this->hotelService->getByCode($codeHotel);

        if (!$hotel) {
            $this->addFlash('error', 'Hotel not found.');
            return $this->redirectToRoute('admin_hotels');
        }

        try {
            $result = $this->hotelService->removeRoomFromHotel($roomCode, $hotel);
            $this->addFlash('success', $result['message']);
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('admin_hotels_view', ['codeHotel' => $codeHotel]);
    }
    
}
