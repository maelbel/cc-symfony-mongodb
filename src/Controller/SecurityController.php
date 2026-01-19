<?php

namespace App\Controller;

use App\Document\Customer;
use App\Form\Type\CustomerType;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Form\FormError;

class SecurityController extends AbstractController
{

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            }
            return $this->redirectToRoute('home');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/register', name: 'app_register')]
    public function register(
        Request $request,
        DocumentManager $dm,
        UserPasswordHasherInterface $hasher
    ){
        $error = null;

        $customer = new Customer();

        $form = $this->createForm(CustomerType::class, $customer);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {

            $repo = $dm->getRepository(Customer::class);
            
            if ($repo->findOneBy(['username' => $customer->getUsername()])) {
                 $form->get('username')->addError(
                new FormError("Username already used.")
            );
            }
            elseif($repo->findOneBy(['mail' => $customer->getEMail()])) {
                $form->get('email')->addError(
                new FormError("Email already used.")
            );
            }

            if($form->isValid()){
                $customer->setUsername($form->get('username')->getData());
                $customer->setAddress($form->get('address')->getData());
                $customer->setPhone($form->get('phone')->getData());
                $customer->setEmail($form->get('email')->getData());
                $customer->setPassword($hasher->hashPassword($customer,$form->get('password')->getData()));

                // default role
                $customer->setRoles(['ROLE_USER']);

                $dm->persist($customer);
                $dm->flush();

                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('security/register.html.twig', [
                    'form' => $form->createView(),
                ]);
        }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
