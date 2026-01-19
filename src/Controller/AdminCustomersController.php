<?php
namespace App\Controller;

use App\Document\Customer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\Type\CustomerType;
use Doctrine\ODM\MongoDB\DocumentManager as DM;
use Symfony\Component\PasswordHasher\HashedPassword; 
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminCustomersController extends AbstractController
{
    #[Route('/admin/customers', name: 'admin_customers')]
    public function index(Request $request, DocumentManager $dm, PaginatorInterface $paginator): Response
    {
        $nameFilter = $request->query->get('name', '');
        $emailFilter = $request->query->get('email', '');

        $qb = $dm->getRepository(Customer::class)->createQueryBuilder();

        if ($nameFilter) {
            $qb->field('username')->equals(new \MongoDB\BSON\Regex($nameFilter, 'i'));
        }

        if ($emailFilter) {
            $qb->field('email')->equals(new \MongoDB\BSON\Regex($emailFilter, 'i'));
        }

        $query = $qb->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $customers = array_map(fn($customer) => [
            'customerCode' => $customer->getCustomerCode(),
            'username' => $customer->getUsername(),
            'email' => $customer->getEmail(),
            'roles' => $customer->getRoles(),
        ], $pagination->getItems());

        return $this->render('admin/customers/index.html.twig', [
            'title' => 'Customers',
            'customers' => $customers,
            'pagination' => $pagination,
            'filters' => [
                'name' => $nameFilter,
                'email' => $emailFilter,
            ],
        ]);
    }

    #[Route('/admin/customers/{codeCustomer}', name: 'admin_customers_view', methods: ['GET'])]
    public function view(string $codeCustomer, DocumentManager $dm): Response
    {
        $customer = $dm->getRepository(\App\Document\Customer::class)->find($codeCustomer);

        if (!$customer) {
            $this->addFlash('error', 'Customer not found.');
            return $this->redirectToRoute('admin_customers');
        }

        return $this->render('admin/customers/view.html.twig', [
            'title' => 'View Customer',
            'customer' => $customer,
        ]);
    }

    #[Route('/admin/customers/{codeCustomer}/edit', name: 'admin_customers_edit', methods: ['GET','POST'])]
    public function edit(Request $request, string $codeCustomer, DM $dm, UserPasswordHasherInterface $hasher): Response
    {
        $customer = $dm->getRepository(\App\Document\Customer::class)->find($codeCustomer);

        if (!$customer) {
            $this->addFlash('error', 'Customer not found.');
            return $this->redirectToRoute('admin_customers');
        }

        $currentPassword = $customer->getPassword();

        $form = $this->createForm(CustomerType::class, $customer, ['include_roles' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            if ($newPassword) {
                $hashed = $hasher->hashPassword($customer, $newPassword);
                $customer->setPassword($hashed);
            } else {
                // restore previous hashed password if password field left empty
                $customer->setPassword($currentPassword);
            }

            $dm->flush();
            $this->addFlash('success', 'Customer updated.');
            return $this->redirectToRoute('admin_customers');
        }

        return $this->render('admin/customers/edit.html.twig', [
            'title' => 'Edit Customer',
            'form' => $form->createView(),
            'customer' => $customer,
        ]);
    }

    #[Route('/admin/customers/{codeCustomer}/delete', name: 'admin_customers_delete', methods: ['POST'])]
    public function delete(string $codeCustomer, DM $dm): Response
    {
        $customer = $dm->getRepository(\App\Document\Customer::class)->find($codeCustomer);

        if (!$customer) {
            $this->addFlash('error', 'Customer not found.');
            return $this->redirectToRoute('admin_customers');
        }

        try {
            $dm->remove($customer);
            $dm->flush();
            $this->addFlash('success', 'Customer deleted.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete customer: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_customers');
    }
}
