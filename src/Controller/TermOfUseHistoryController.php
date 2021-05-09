<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\TermOfUseHistory;
use App\Repository\TermOfUseHistoryRepository;

use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/term_of_use_history")
 */
class TermOfUseHistoryController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * @Route("/", name="term_of_use_history_index", methods={"GET"})
     *
     * @Template
     */
    public function index(Request $request, TermOfUseHistoryRepository $termOfUseHistoryRepository) : array {
        $query = $termOfUseHistoryRepository->indexQuery();
        $pageSize = (int) $this->getParameter('page_size');
        $page = $request->query->getint('page', 1);

        return [
            'term_of_use_histories' => $this->paginator->paginate($query, $page, $pageSize),
        ];
    }

    /**
     * @Route("/{id}", name="term_of_use_history_show", methods={"GET"})
     * @Template
     *
     * @return array
     */
    public function show(TermOfUseHistory $termOfUseHistory) {
        return [
            'term_of_use_history' => $termOfUseHistory,
        ];
    }
}
