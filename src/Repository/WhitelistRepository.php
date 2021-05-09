<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\Whitelist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|Whitelist find($id, $lockMode = null, $lockVersion = null)
 * @method null|Whitelist findOneBy(array $criteria, array $orderBy = null)
 * @method Whitelist[] findAll()
 * @method Whitelist[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WhitelistRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Whitelist::class);
    }

    /**
     * @return Query
     */
    public function indexQuery() {
        return $this->createQueryBuilder('whitelist')
            ->orderBy('whitelist.id')
            ->getQuery()
        ;
    }

    /**
     * @param string $q
     *
     * @return Query
     */
    public function searchQuery($q) {
        $qb = $this->createQueryBuilder('whitelist');
        $qb->addSelect('MATCH (whitelist.uuid, whitelist.comment) AGAINST(:q BOOLEAN) as HIDDEN score');
        $qb->andHaving('score > 0');
        $qb->orderBy('score', 'DESC');
        $qb->setParameter('q', $q);

        return $qb->getQuery();
    }
}
