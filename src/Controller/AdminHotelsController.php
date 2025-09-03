<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminHotelsController extends AbstractController
{
    #[Route('/admin/hotels', name: 'admin_hotels')]
    public function index(): Response
    {
        return $this->render('admin/hotels/index.html.twig', [
            'title' => 'Admin Hotels',
        ]);
    }
}
