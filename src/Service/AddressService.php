<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class AddressService
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function handleDefaultAddress(Address $newAddress, User $user): void
    {
        // check if the address is set as default and remove default from other addresses
        if ($newAddress->isDefault()) {
            foreach ($user->getAddresses() as $existingAddress) {
                if ($existingAddress !== $newAddress && $existingAddress->isDefault()) {
                    $existingAddress->setIsDefault(false);
                    $this->entityManager->persist($existingAddress);
                }
            }
        }
    }

    public function saveAddress(Address $address, User $user): void
    {
        $address->setUser($user);
        $this->entityManager->persist($address);
        $this->entityManager->flush();
    }

    public function removeAddress(Address $address): void
    {
        $this->entityManager->remove($address);
        $this->entityManager->flush();
    }
}
