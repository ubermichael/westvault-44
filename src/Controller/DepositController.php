<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Deposit;
use App\Repository\DepositRepository;

use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/deposit")
 */
class DepositController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * @Route("/", name="deposit_index", methods={"GET"})
     *
     * @Template
     */
    public function index(Request $request, DepositRepository $depositRepository) : array {
        $query = $depositRepository->indexQuery();
        $pageSize = (int) $this->getParameter('page_size');
        $page = $request->query->getint('page', 1);

        return [
            'deposits' => $this->paginator->paginate($query, $page, $pageSize),
        ];
    }

    /**
     * @Route("/search", name="deposit_search", methods={"GET"})
     *
     * @Template
     *
     * @return array
     */
    public function search(Request $request, DepositRepository $depositRepository) {
        $q = $request->query->get('q');
        if ($q) {
            $query = $depositRepository->searchQuery($q);
            $deposits = $this->paginator->paginate($query, $request->query->getInt('page', 1), $this->getParameter('page_size'), ['wrap-queries' => true]);
        } else {
            $deposits = [];
        }

        return [
            'deposits' => $deposits,
            'q' => $q,
        ];
    }

    /**
     * @Route("/{id}", name="deposit_show", methods={"GET"})
     * @Template
     *
     * @return array
     */
    public function show(Deposit $deposit) {
        return [
            'deposit' => $deposit,
        ];
    }
}
