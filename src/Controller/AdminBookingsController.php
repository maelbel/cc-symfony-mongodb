<?php
namespace App\Controller;

use App\Document\Reservation;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Form\Type\ReservationType;
use Symfony\Component\Routing\Annotation\Route;

class AdminBookingsController extends AbstractController
{
    #[Route('/admin/bookings', name: 'admin_bookings')]
    public function index(DocumentManager $dm): Response
    {
        $bookings = $dm->getRepository(Reservation::class)->findBy([], ['startDate' => 'DESC']);

        return $this->render('admin/bookings/index.html.twig', [
            'title' => 'Bookings',
            'bookings' => $bookings,
        ]);
    }

    #[Route('/admin/bookings/{id}', name: 'admin_bookings_show', methods: ['GET'])]
    public function show(string $id, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('admin_bookings');
        }

        return $this->render('admin/bookings/show.html.twig', [
            'title' => 'Booking Details',
            'booking' => $reservation,
        ]);
    }

    #[Route('/admin/bookings/{id}/edit', name: 'admin_bookings_edit', methods: ['GET','POST'])]
    public function edit(string $id, Request $request, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('admin_bookings');
        }

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->flush();
            $this->addFlash('success', 'Reservation updated.');
            return $this->redirectToRoute('admin_bookings_show', ['id' => $reservation->getReservationCode()]);
        }

        return $this->render('admin/bookings/edit.html.twig', [
            'title' => 'Edit Booking',
            'form' => $form->createView(),
            'booking' => $reservation,
        ]);
    }

    #[Route('/admin/bookings/{id}/delete', name: 'admin_bookings_delete', methods: ['POST'])]
    public function delete(string $id, Request $request, DocumentManager $dm): RedirectResponse
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found.');
            return $this->redirectToRoute('admin_bookings');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_booking_' . $reservation->getReservationCode(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_bookings');
        }

        $dm->remove($reservation);
        $dm->flush();

        $this->addFlash('success', 'Reservation deleted.');
        return $this->redirectToRoute('admin_bookings');
    }
}
