<?php

namespace App\Controller\Frontend;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressForm;
use App\Service\AddressService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/address')]
final class AddressController extends AbstractController
{
    #[Route(name: 'app_frontend_address_index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        return $this->render('frontend/address/index.html.twig', [
            'addresses' => $user->getAddresses(),
        ]);
    }

    #[Route('/new', name: 'app_frontend_address_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AddressService $addressService): Response
    {
        // Ensure to return to the previous page after creating an address when user come from checkout page
        $returnUrl = $request->query->get('returnUrl', $this->generateUrl('app_frontend_address_index'));

        $address = new Address();
        $form = $this->createForm(AddressForm::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $addressService->handleDefaultAddress($address, $user);
            $addressService->saveAddress($address, $user);

            $this->addFlash('success', 'Address created successfully.');
            return $this->redirect($returnUrl);
        }

        return $this->render('frontend/address/new.html.twig', [
            'address' => $address,
            'form' => $form,
            'returnUrl' => $returnUrl
        ]);
    }

    #[Route('/{id}', name: 'app_frontend_address_show', methods: ['GET'])]
    public function show(Address $address): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($address->getUser() !== $user) {
            throw $this->createAccessDeniedException('Invalid address selected.');
        }

        return $this->render('frontend/address/show.html.twig', [
            'address' => $address,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_frontend_address_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Address $address, AddressService $addressService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Ensure users can only edit their own address
        if (!$address || $address->getUser() !== $user) {
            throw $this->createAccessDeniedException('Invalid address selected.');
        }

        $form = $this->createForm(AddressForm::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $addressService->handleDefaultAddress($address, $user);
            $addressService->saveAddress($address, $user);

            $this->addFlash('success', 'Address updated successfully.');
            return $this->redirectToRoute('app_frontend_address_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('frontend/address/edit.html.twig', [
            'address' => $address,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_frontend_address_delete', methods: ['POST'])]
    public function delete(Request $request, AddressService $addressService, Address $address): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Ensure users can only edit their own address
        if (!$address || $address->getUser() !== $user) {
            throw $this->createAccessDeniedException('Invalid address selected.');
        }
        if ($this->isCsrfTokenValid('delete' . $address->getId(), $request->request->get('_token'))) {
            $addressService->removeAddress($address);

            $this->addFlash('success', 'Address deleted successfully.');
        }

        return $this->redirectToRoute('app_frontend_address_index', [], Response::HTTP_SEE_OTHER);
    }
}
