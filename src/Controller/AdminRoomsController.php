<?php
namespace App\Controller;

use App\Service\RoomService;
use App\Document\Hotel;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class AdminRoomsController extends AbstractController
{
    private RoomService $roomService;
    private $hotelRepository;
    private DocumentManager $dm;

    public function __construct(RoomService $roomService, DocumentManager $dm)
    {
        $this->roomService = $roomService;
        $this->dm = $dm;
        $this->hotelRepository = $this->dm->getRepository(Hotel::class);
    }
    #[Route('/admin/rooms', name: 'admin_rooms')]
    public function index(Request $request): Response
    {
        // Use browse method if filters are present otherwise use getAll
        $data = $request->query->has('type') || $request->query->has('floor') || $request->query->has('hotelCode')
            ? $this->roomService->browse($request)
            : $this->roomService->getAll($request);
        dump($data['data']);
        $hotels = $this->hotelRepository->findAll();
        return $this->render('admin/rooms/index.html.twig', [
            'title' => 'Admin Rooms',
            'rooms' => $data['data'],
            'pagination' => $data['paginationObject'],
            'hotels' => $hotels,
            'filters' => [
                'type' => $request->query->get('type', ''),
                'floor' => $request->query->get('floor', ''),
                'hotelCode' => $request->query->get('hotelCode', ''),
            ],
        ]);
    }
    /**
     * Show form to add a new room
     */
    #[Route('/admin/rooms/new', name: 'admin_rooms_new', methods: ['GET'])]
    public function new(): Response
    {
        $hotels = $this->hotelRepository->findAll();
        return $this->render('admin/rooms/new.html.twig', [
            'title' => 'Add New Room',
            'hotels' => $hotels,
        ]);
    }

    /**
     * Create a new room
     */
    #[Route('/admin/rooms/create', name: 'admin_rooms_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = $request->request->all();
        $hotelCode = $data['hotelCode'] ?? null;
        $hotel = $this->hotelRepository->find($hotelCode);

        if (!$hotel) {
            $this->addFlash('error', 'Invalid hotel selected.');
            return $this->redirectToRoute('admin_rooms_new');
        }

        try {
            $result = $this->roomService->add($data, $hotel);
            $this->addFlash('success', $result['message']);
            return $this->redirectToRoute('admin_rooms');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to create room: ' . $e->getMessage());
            return $this->redirectToRoute('admin_rooms_new');
        }
    }

    /**
     * View a single room by its code
     */
    #[Route('/admin/rooms/{roomCode}', name: 'admin_rooms_view', methods: ['GET'])]
    public function view(string $roomCode): Response
    {
        $room = $this->roomService->getByCode($roomCode);

        if (!$room) {
            $this->addFlash('error', 'Room not found.');
            return $this->redirectToRoute('admin_rooms');
        }

        return $this->render('admin/rooms/view.html.twig', [
            'title' => 'View Room',
            'room' => [
                'roomCode' => $room->getRoomCode(),
                'floor' => $room->getFloor(),
                'type' => $room->getType(),
                'numberOfBeds' => $room->getNumberOfBeds(),
                'hotelCode' => $room->getHotel() ? $room->getHotel()->getHotelCode() : null,
            ],
        ]);
    }

    /**
     * Show form to edit a room
     */
    #[Route('/admin/rooms/{roomCode}/edit', name: 'admin_rooms_edit', methods: ['GET'])]
    public function edit(string $roomCode): Response
    {
        $room = $this->roomService->getByCode($roomCode);
        $hotels = $this->hotelRepository->findAll();

        if (!$room) {
            $this->addFlash('error', 'Room not found.');
            return $this->redirectToRoute('admin_rooms');
        }

        return $this->render('admin/rooms/edit.html.twig', [
            'title' => 'Edit Room',
            'room' => [
                'roomCode' => $room->getRoomCode(),
                'floor' => $room->getFloor(),
                'type' => $room->getType(),
                'numberOfBeds' => $room->getNumberOfBeds(),
                'hotelCode' => $room->getHotel() ? $room->getHotel()->getHotelCode() : null,
            ],
            'hotels' => $hotels,
        ]);
    }

    /**
     * Update a room
     */
    #[Route('/admin/rooms/{roomCode}', name: 'admin_rooms_update', methods: ['POST'])]
    public function update(Request $request, string $roomCode): Response
    {
        $redirect = 'admin_rooms'; // default redirect

        $room = $this->roomService->getByCode($roomCode);
        if (!$room) {
            $this->addFlash('error', 'Room not found.');
        } else {
            $data = $request->request->all();
            $hotelCode = $data['hotelCode'] ?? null;
            $hotel = $hotelCode ? $this->hotelRepository->find($hotelCode) : null;

            if (!$hotel) {
                $this->addFlash('error', 'Invalid hotel selected.');
                $redirect = 'admin_rooms_edit';
            } else {
                try {
                    $result = $this->roomService->update($room, $data, $hotel);
                    $this->addFlash('success', $result['message']);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to update room: '.$e->getMessage());
                    $redirect = 'admin_rooms_edit';
                }
            }
        }

        return $this->redirectToRoute($redirect, ['roomCode' => $roomCode]);
    }

    /**
     * Delete a room
     */
    #[Route('/admin/rooms/{roomCode}/delete', name: 'admin_rooms_delete', methods: ['POST'])]
    public function delete(string $roomCode): Response
    {
        $room = $this->roomService->getByCode($roomCode);

        if (!$room) {
            $this->addFlash('error', 'Room not found.');
            return $this->redirectToRoute('admin_rooms');
        }

        try {
            $result = $this->roomService->delete($room);
            $this->addFlash('success', $result['message']);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete room: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_rooms');
    }
}
