<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findByFiltersAndSort(array $filters = [], ?string $sort = null,?string $search = null)
    {
        $qb = $this->createQueryBuilder('p');

        // Filter by availability based on quantity
        if (!empty($filters['availability'])) {
            switch ($filters['availability']) {
                case 'in_stock':
                    $qb->andWhere('p.quantity > 5');
                    break;
                case 'pre_order':
                    $qb->andWhere('p.quantity BETWEEN 1 AND 3');
                    break;
                case 'out_of_stock':
                    $qb->andWhere('p.quantity = 0');
                    break;
            }
        }

        // Filter by price range
        if (!empty($filters['price_from'])) {
            $qb->andWhere('p.price >= :priceFrom')
                ->setParameter('priceFrom', $filters['price_from']);
        }
        if (!empty($filters['price_to'])) {
            $qb->andWhere('p.price <= :priceTo')
                ->setParameter('priceTo', $filters['price_to']);
        }

        // Search filter
        if (!empty($search)) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Sorting
        if ($sort) {
            switch ($sort) {
                case 'price_asc':
                    $qb->orderBy('p.price', 'ASC');
                    break;
                case 'price_desc':
                    $qb->orderBy('p.price', 'DESC');
                    break;
            }
        } else {
            $qb->orderBy('p.id', 'DESC'); // Default sorting
        }

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
