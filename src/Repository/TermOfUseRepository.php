<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\TermOfUse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|TermOfUse find($id, $lockMode = null, $lockVersion = null)
 * @method null|TermOfUse findOneBy(array $criteria, array $orderBy = null)
 * @method TermOfUse[] findAll()
 * @method TermOfUse[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermOfUseRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, TermOfUse::class);
    }

    /**
     * @return Query
     */
    public function indexQuery() {
        return $this->createQueryBuilder('termOfUse')
            ->orderBy('termOfUse.id')
            ->getQuery()
        ;
    }

    /**
     * @param string $q
     *
     * @return Query
     */
    public function searchQuery($q) {
        $qb = $this->createQueryBuilder('termOfUse');
        $qb->addSelect('MATCH (termOfUse.content) AGAINST(:q BOOLEAN) as HIDDEN score');
        $qb->andHaving('score > 0');
        $qb->orderBy('score', 'DESC');
        $qb->setParameter('q', $q);

        return $qb->getQuery();
    }
}
