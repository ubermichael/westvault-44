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
use Doctrine\Common\Collections\Collection;
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

    /**
     * Get the terms, ordered by weight.
     *
     * @return Collection|TermOfUse[]
     */
    public function getTerms() {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.weight', 'ASC')
            ->getQuery()
        ;

        return $qb->getResult();
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     *
     * @return int|mixed|string
     */
    public function getLastUpdated() {
        return $this->_em->createQueryBuilder()
            ->select('MAX(t.updated)')
            ->from(TermOfUse::class, 't')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
