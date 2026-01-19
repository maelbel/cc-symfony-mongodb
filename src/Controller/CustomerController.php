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
use App\Form\Type\NoteType;
use Psr\Log\LoggerInterface;

class CustomerController extends AbstractController
{
    #[Route('/customer', name: 'customer_dashboard')]
    public function dashboard(DocumentManager $dm): Response
    {
        /** @var Customer $customer */
        $currentUser = $this->getUser();

        // Ensure we use a managed Customer document instance when querying
        $customer = null;
        if ($currentUser) {
            $customer = $dm->getRepository(Customer::class)->find($currentUser->getCustomerCode());
        }

        $reservations = [];
        if ($customer) {
            $reservations = $dm->getRepository(Reservation::class)->findBy([
                'customer' => $customer
            ]);
        }

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

    #[Route('/customer/reservation/{id}/cancel', priority: -1, name: 'customer_reservation_cancel')]
    public function cancelReservation(string $id, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if ($reservation && $reservation->getCustomer() && $this->getUser() && $reservation->getCustomer()->getUserIdentifier() === $this->getUser()->getUserIdentifier()) {
            $dm->remove($reservation);
            $dm->flush();
            $this->addFlash('success', 'Reservation cancelled.');
        }

        return $this->redirectToRoute('customer_dashboard');
    }
    #[Route('/customer/reservation/{id}/confirm_cancel', priority: -1, name: 'customer_reservation_confirm_cancel')]
    public function confirmCancel(string $id, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation || !$reservation->getCustomer() || !$this->getUser() || $reservation->getCustomer()->getUserIdentifier() !== $this->getUser()->getUserIdentifier()) {
            $owner = $reservation && $reservation->getCustomer() ? $reservation->getCustomer()->getUserIdentifier() : 'n/a';
            $current = $this->getUser() ? $this->getUser()->getUserIdentifier() : 'n/a';
            $this->addFlash('error', sprintf('Reservation not found or access denied (owner=%s current=%s)', $owner, $current));
            return $this->redirectToRoute('customer_dashboard');
        }

        return $this->render('customer/confirm_cancel.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/customer/reservation/quick', name: 'customer_reservation_quick', methods: ['POST'])]
    public function quickBook(Request $request, DocumentManager $dm, LoggerInterface $logger): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'You must be logged in to book a room.');
            return $this->redirectToRoute('app_login');
        }

        $token = $request->request->get('_token');
        $hotelId = $request->request->get('hotel');
        $roomId = $request->request->get('room');
        $start = $request->request->get('start_date');
        $end = $request->request->get('end_date');

        // Debug summary to help diagnose missing reservation creation
        $currentUserId = $this->getUser() ? $this->getUser()->getUserIdentifier() : 'n/a';
        $tokenPresent = $token ? 'yes' : 'no';
        $tokenValid = $this->isCsrfTokenValid('quick_book', $token) ? 'yes' : 'no';
        $debugMsg = sprintf('quickBook debug: user=%s token_present=%s token_valid=%s hotel=%s room=%s start=%s end=%s', $currentUserId, $tokenPresent, $tokenValid, $hotelId ?: 'n/a', $roomId ?: 'n/a', $start ?: 'n/a', $end ?: 'n/a');
        $this->addFlash('info', $debugMsg);
        $logger->info($debugMsg);

        if ($tokenValid !== 'yes') {
            $this->addFlash('error', 'Invalid CSRF token.');
            $logger->warning('quickBook: invalid CSRF token', ['token_present' => $tokenPresent]);
            return $this->redirectToRoute('home');
        }

        $hotel = $hotelId ? $dm->getRepository(\App\Document\Hotel::class)->find($hotelId) : null;
        $room = $roomId ? $dm->getRepository(\App\Document\Room::class)->find($roomId) : null;

        if (!$hotel || !$room) {
            $this->addFlash('error', 'Hotel or room not found.');
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('home'));
        }

        try {
            $startDate = $start ? new \DateTimeImmutable($start) : new \DateTimeImmutable();
            $endDate = $end ? new \DateTimeImmutable($end) : (new \DateTimeImmutable())->modify('+1 day');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Invalid dates.');
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('home'));
        }

        $reservation = new Reservation();
        // Ensure we set a managed Customer document (avoid detached or proxy issues)
        $currentUser = $this->getUser();
        $managedCustomer = null;
        if ($currentUser) {
            $managedCustomer = $dm->getRepository(\App\Document\Customer::class)->find($currentUser->getUserIdentifier());
        }
        if ($managedCustomer) {
            // set the managed Customer instance directly
            $reservation->setCustomer($managedCustomer);
        } else {
            // fallback: try to use a reference from current user identifier
            try {
                $reservation->setCustomer($dm->getReference(\App\Document\Customer::class, $currentUser ? $currentUser->getUserIdentifier() : null));
            } catch (\Throwable $e) {
                $reservation->setCustomer($currentUser);
            }
        }

        // Debug before persist: inspect customer object
        $cust = $reservation->getCustomer();
        $custClass = is_object($cust) ? get_class($cust) : 'none';
        $custId = 'n/a';
        if (is_object($cust)) {
            try {
                $custId = method_exists($cust, 'getUserIdentifier') ? $cust->getUserIdentifier() : (method_exists($cust, 'getCustomerCode') ? $cust->getCustomerCode() : 'n/a');
            } catch (\Throwable $e) {
                $custId = 'error';
            }
        }
        $this->addFlash('info', sprintf('Before persist: customer_class=%s customer_id=%s', $custClass, $custId));
        $logger->info('quickBook before persist', ['customer_class' => $custClass, 'customer_id' => $custId]);
        $reservation->setHotel($hotel);
        $reservation->setRoom($room);
        $reservation->setStartDate($startDate);
        $reservation->setEndDate($endDate);

        try {
            $dm->persist($reservation);
            $dm->flush();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Unable to save reservation.');
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('home'));
        }

        // Re-load the reservation to inspect stored customer reference
        $resCode = $reservation->getReservationCode();
        $saved = $dm->getRepository(Reservation::class)->find($resCode);
        $storedOwner = 'n/a';
        if ($saved && $saved->getCustomer()) {
            try {
                $storedOwner = $saved->getCustomer()->getUserIdentifier();
            } catch (\Throwable $e) {
                $storedOwner = 'error';
            }
        }

        $this->addFlash('success', sprintf('Réservation enregistrée ! id=%s owner=%s', $resCode, $storedOwner));
        $logger->info('quickBook saved reservation', ['reservation_id' => $resCode, 'stored_owner' => $storedOwner]);
        return $this->redirectToRoute('customer_dashboard');
    }

    #[Route('/customer/reservation/{id}', priority: -1, name: 'customer_reservation_show')]
    public function showReservation(string $id, DocumentManager $dm): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            $owner = $reservation && $reservation->getCustomer() ? $reservation->getCustomer()->getUserIdentifier() : 'n/a';
            $current = $this->getUser() ? $this->getUser()->getUserIdentifier() : 'n/a';
            $this->addFlash('error', sprintf('Reservation not found or access denied (owner=%s current=%s)', $owner, $current));
            return $this->redirectToRoute('customer_dashboard');
        }

        return $this->render('customer/show_reservation.html.twig', [
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{id}/addNote', name: 'customer_reservation_add_note', methods: ['POST'])]
    public function addNote(Request $request, DocumentManager $dm, int $id): Response
    {
        $reservation = $dm->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return $this->json(['error' => 'Reservation not found'], 404);
        }

        $note = $request->request->get('note'); 

        $reservation->setNote($note);
        $dm->flush();

        return $this->redirectToRoute('customer_dashboard'); 
    }
}
