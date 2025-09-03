<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminRoomsController extends AbstractController
{
    #[Route('/admin/rooms', name: 'admin_rooms')]
    public function index(): Response
    {
        return $this->render('admin/rooms/index.html.twig', [
            'title' => 'Admin Rooms',
        ]);
    }
}
