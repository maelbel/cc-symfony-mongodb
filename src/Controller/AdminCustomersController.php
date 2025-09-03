<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminCustomersController extends AbstractController
{
    #[Route('/admin/customers', name: 'admin_customers')]
    public function index(): Response
    {
        return $this->render('admin/customers/index.html.twig', [
            'title' => 'Admin Customers',
        ]);
    }
}
