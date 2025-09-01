<?php
namespace App\Controller;

use App\Service\MongoDB;
use MongoDB\Driver\Exception\Exception as MongoDriverException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MongoController extends AbstractController
{
    #[Route('/mongo', name: 'mongo')]
    public function index(MongoDB $mongo): Response
    {
        try {
            $mongo->selectDatabase('admin')->command(['ping' => 1]);

            $databases = $mongo->listDatabases();
            $dbNames = [];
            foreach ($databases as $db) {
                $dbNames[] = $db->getName();
            }

            return $this->json([
                'status' => 'ok',
                'databases' => $dbNames,
            ], Response::HTTP_OK);
        } catch (MongoDriverException $e) {
            return $this->json([
                'status' => 'error',
                'error' => 'MongoDB connection failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
