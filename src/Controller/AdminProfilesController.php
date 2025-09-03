<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminProfilesController extends AbstractController
{
    #[Route('/admin/profiles', name: 'admin_profiles')]
    public function index(): Response
    {
        return $this->render('admin/profiles/index.html.twig', [
            'title' => 'Admin Profiles',
        ]);
    }
}
