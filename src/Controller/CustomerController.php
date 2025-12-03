<?php

namespace App\Controller;

use App\Document\Customer;
use App\Document\Reservation;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Type\CustomerType;
use App\Form\Type\ReservationType;

class CustomerController extends AbstractController
{
    #[Route('/customer', name: 'customer_dashboard')]
    public function dashboard(DocumentManager $dm): Response
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        $reservations = $dm->getRepository(Reservation::class)->findBy([
            'customer' => $customer
        ]);

        return $this->render('customer/dashboard.html.twig', [
            'customer' => $customer,
            'reservations' => $reservations,
        ]);
    }

    #[Route('/customer/profile', name: 'customer_profile')]
    public function profile(Request $request, DocumentManager $dm): Response
    {
        /** @var Customer $customer */
        $customer = $this->getUser();

        $form = $this->createForm(CustomerType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->flush();
            $this->addFlash('success', 'Profile successfully updated!');
            return $this->redirectToRoute('customer_profile');
        }

        return $this->render('customer/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/customer/reservation/new', name: 'customer_reservation_new')]
    public function newReservation(Request $request, DocumentManager $dm): Response
    {
        $reservation = new Reservation();
        $reservation->setCustomer($this->getUser());

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dm->persist($reservation);
            $dm->flush();
            $this->addFlash('success', 'Reservation recorded!');
            return $this->redirectToRoute('customer_dashboard');
        }

        return $this->render('customer/new_reservation.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/customer/reservation/{id}/cancel', name: 'customer_reservation_cancel')]
    public function cancelReservation(string $id, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if ($reservation && $reservation->getCustomer() === $this->getUser()) {
            $dm->remove($reservation);
            $dm->flush();
            $this->addFlash('success', 'Reservation cancelled.');
        }

        return $this->redirectToRoute('customer_dashboard');
    }
    #[Route('/customer/reservation/{id}/confirm_cancel', name: 'customer_reservation_confirm_cancel')]
    public function confirmCancel(string $id, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation || $reservation->getCustomer() !== $this->getUser()) {
            throw $this->createNotFoundException('Reservation not found or access denied');
        }

        return $this->render('customer/confirm_cancel.html.twig', [
            'reservation' => $reservation,
        ]);
    }
    #[Route('/customer/reservation/{id}', name: 'customer_reservation_show')]
    public function showReservation(string $id, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation || $reservation->getCustomer() !== $this->getUser()) {
            throw $this->createNotFoundException('Reservation not found or access denied');
        }

        return $this->render('customer/show_reservation.html.twig', [
            'reservation' => $reservation,
        ]);
    }



}
