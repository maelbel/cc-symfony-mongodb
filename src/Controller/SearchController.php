<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\RoomService;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('search/index.html.twig');
    }

    #[Route('/search/results', name: 'search_results', methods: ['GET'])]
    public function searchResults(Request $request, RoomService $roomService): Response
    {
        $city = $request->query->get('city');
        $start = $request->query->get('start_date');
        $end = $request->query->get('end_date');

        $results = [];

        if ($city && $start && $end) {
            try {
                $startDate = new \DateTimeImmutable($start);
                $endDate = new \DateTimeImmutable($end);

                $results = $roomService->searchAvailableRooms($city, $startDate, $endDate);

            } catch (\Exception $e) {
                $results = [];
            }
        }

        return $this->render('search/index.html.twig', [
            'results' => $results,
            'city' => $city,
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

}
