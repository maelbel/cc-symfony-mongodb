<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(Request $request): Response
    {
        $start = $request->query->get('start_date');
        $end = $request->query->get('end_date');

        $startDate = null;
        $endDate = null;
        try {
            if ($start) {
                $startDate = new \DateTimeImmutable($start);
            }
        } catch (\Exception $e) {
            $startDate = null;
        }

        try {
            if ($end) {
                $endDate = new \DateTimeImmutable($end);
            }
        } catch (\Exception $e) {
            $endDate = null;
        }

        return $this->render('search/index.html.twig', [
            'controller_name' => 'SearchController',
            'start_date' => $start,
            'end_date' => $end,
            'start_date_obj' => $startDate,
            'end_date_obj' => $endDate,
        ]);
    }
}
