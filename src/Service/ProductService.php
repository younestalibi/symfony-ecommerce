<?php

namespace App\Service;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductService
{
    public function uploadImage(UploadedFile $imageFile, string $uploadDir): string
    {
        $fileName = uniqid() . '.' . $imageFile->guessExtension();

        try {
            $imageFile->move($uploadDir, $fileName);
        } catch (FileException $e) {
            throw new \RuntimeException('Failed to upload image: ' . $e->getMessage());
        }

        return $fileName;
    }

    public function removeImage(string $fileName, string $uploadDir): void
    {
        $filePath = $uploadDir . '/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    public function handleProductImage(Product $product, ?UploadedFile $imageFile, string $uploadDir): void
    {
        if ($imageFile) {
            if ($product->getImage()) {
                $this->removeImage($product->getImage(), $uploadDir);
            }

            $newFilename = $this->uploadImage($imageFile, $uploadDir);
            $product->setImage($newFilename);
        }
    }
}
