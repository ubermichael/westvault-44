<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\TermOfUseHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|TermOfUseHistory find($id, $lockMode = null, $lockVersion = null)
 * @method null|TermOfUseHistory findOneBy(array $criteria, array $orderBy = null)
 * @method TermOfUseHistory[] findAll()
 * @method TermOfUseHistory[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermOfUseHistoryRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, TermOfUseHistory::class);
    }

    /**
     * @return Query
     */
    public function indexQuery() {
        return $this->createQueryBuilder('termOfUseHistory')
            ->orderBy('termOfUseHistory.id')
            ->getQuery()
        ;
    }
}
