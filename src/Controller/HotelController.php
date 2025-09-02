<?php

namespace App\Controller;

use App\Document\Hotel;
use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\Regex;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HotelController extends AbstractController
{
    private DocumentManager $dm;
    private LoggerInterface $logger;

    public function __construct(DocumentManager $dm, LoggerInterface $logger)
    {
        $this->dm = $dm;
        $this->logger = $logger;
    }

    #[Route('/', name: 'hotel_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // return $this->render('hotel/index.html.twig');
        return $this->json([
            'message' => 'Bienvenue sur l’API Hotel',
        ]);
    }

    #[Route('/hotel/browse', name: 'hotel_browse', methods: ['GET'])]
    public function browse(Request $request): Response
    {
        $hotelRepository = $this->dm->getRepository(Hotel::class);
        $queryBuilder = $hotelRepository->createQueryBuilder();

        // Exemple : chercher les hôtels de catégorie "5 étoiles"
        // dont le nom contient "Palace" (insensible à la casse)
        $hotels = $queryBuilder
                ->field('categorieHotel')->equals('5 étoiles')
                ->field('nomHotel')->equals(new Regex('Palace', 'i'))
                ->getQuery()
                ->execute();

        // return $this->render('hotel/browse.html.twig', ['hotels' => $hotels]);
        $result = [];
        foreach ($hotels as $hotel) {
            $result[] = [
                'codeHotel'     => $hotel->getHotelCode(),
                'nomHotel'      => $hotel->getHotelName(),
                'hotelAddress'  => $hotel->getHotelAddress(),
                'categorieHotel'=> $hotel->getHotelCategory(),
            ];
        }

        return $this->json($result);
    }
    #[Route('/hotel/add', name: 'hotel_add', methods: ['POST'])]
    public function add(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['hotelName'], $data['hotelAddress'], $data['hotelCategory'])) {
            return $this->json(['error' => 'Champs requis : nomHotel, adresseHotel, categorieHotel'], 400);
        }

        $hotel = new Hotel();
        $hotel->setHotelName($data['hotelName']);
        $hotel->setHotelAddress($data['hotelAddress']);
        $hotel->setHotelCategory($data['hotelCategory']);

        $this->dm->persist($hotel);
        $this->dm->flush();

        return $this->json([
            'message' => 'Hôtel ajouté avec succès ✅',
            'hotel'   => [
                'codeHotel'      => $hotel->getHotelCode(),
                'nomHotel'       => $hotel->getHotelName(),
                'hotelAddress'   => $hotel->getHotelAddress(),
                'hotelCategory' => $hotel->getHotelCategory(),
            ]
        ], 201);
    }
}
