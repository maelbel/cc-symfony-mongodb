<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminBookingsController extends AbstractController
{
    #[Route('/admin/bookings', name: 'admin_bookings')]
    public function index(): Response
    {
        return $this->render('admin/bookings/index.html.twig', [
            'title' => 'Admin Bookings',
        ]);
    }
}
