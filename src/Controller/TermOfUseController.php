<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\TermOfUse;
use App\Form\TermOfUseType;
use App\Repository\TermOfUseHistoryRepository;
use App\Repository\TermOfUseRepository;

use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/term_of_use")
 */
class TermOfUseController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * @Route("/", name="term_of_use_index", methods={"GET"})
     *
     * @Template
     */
    public function index(Request $request, TermOfUseRepository $termOfUseRepository) : array {
        $query = $termOfUseRepository->indexQuery();
        $pageSize = (int) $this->getParameter('page_size');
        $page = $request->query->getint('page', 1);

        return [
            'term_of_uses' => $this->paginator->paginate($query, $page, $pageSize),
        ];
    }

    /**
     * @Route("/search", name="term_of_use_search", methods={"GET"})
     *
     * @Template
     *
     * @return array
     */
    public function search(Request $request, TermOfUseRepository $termOfUseRepository) {
        $q = $request->query->get('q');
        if ($q) {
            $query = $termOfUseRepository->searchQuery($q);
            $termOfUses = $this->paginator->paginate($query, $request->query->getInt('page', 1), $this->getParameter('page_size'), ['wrap-queries' => true]);
        } else {
            $termOfUses = [];
        }

        return [
            'term_of_uses' => $termOfUses,
            'q' => $q,
        ];
    }

    /**
     * @Route("/new", name="term_of_use_new", methods={"GET", "POST"})
     * @Template
     * @IsGranted("ROLE_CONTENT_ADMIN")
     *
     * @return array|RedirectResponse
     */
    public function new(Request $request) {
        $termOfUse = new TermOfUse();
        $form = $this->createForm(TermOfUseType::class, $termOfUse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($termOfUse);
            $entityManager->flush();
            $this->addFlash('success', 'The new termOfUse has been saved.');

            return $this->redirectToRoute('term_of_use_show', ['id' => $termOfUse->getId()]);
        }

        return [
            'term_of_use' => $termOfUse,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="term_of_use_show", methods={"GET"})
     * @Template
     *
     * @return array
     */
    public function show(TermOfUse $termOfUse, TermOfUseHistoryRepository $repo) {
        $history = $repo->findBy(['termId' => $termOfUse->getId()], ['id' => 'ASC']);

        return [
            'term_of_use' => $termOfUse,
            'history' => $history,
        ];
    }

    /**
     * @IsGranted("ROLE_CONTENT_ADMIN")
     * @Route("/{id}/edit", name="term_of_use_edit", methods={"GET", "POST"})
     *
     * @Template
     *
     * @return array|RedirectResponse
     */
    public function edit(Request $request, TermOfUse $termOfUse) {
        $form = $this->createForm(TermOfUseType::class, $termOfUse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The updated termOfUse has been saved.');

            return $this->redirectToRoute('term_of_use_show', ['id' => $termOfUse->getId()]);
        }

        return [
            'term_of_use' => $termOfUse,
            'form' => $form->createView(),
        ];
    }

    /**
     * @IsGranted("ROLE_CONTENT_ADMIN")
     * @Route("/{id}", name="term_of_use_delete", methods={"DELETE"})
     *
     * @return RedirectResponse
     */
    public function delete(Request $request, TermOfUse $termOfUse) {
        if ($this->isCsrfTokenValid('delete' . $termOfUse->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($termOfUse);
            $entityManager->flush();
            $this->addFlash('success', 'The termOfUse has been deleted.');
        }

        return $this->redirectToRoute('term_of_use_index');
    }
}
