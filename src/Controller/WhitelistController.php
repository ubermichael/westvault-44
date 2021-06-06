<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Whitelist;
use App\Form\WhitelistType;
use App\Repository\ProviderRepository;
use App\Repository\WhitelistRepository;

use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/whitelist")
 */
class WhitelistController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * @Route("/", name="whitelist_index", methods={"GET"})
     *
     * @Template
     */
    public function index(Request $request, WhitelistRepository $whitelistRepository) : array {
        $query = $whitelistRepository->indexQuery();
        $pageSize = (int) $this->getParameter('page_size');
        $page = $request->query->getint('page', 1);

        return [
            'whitelists' => $this->paginator->paginate($query, $page, $pageSize),
        ];
    }

    /**
     * @Route("/search", name="whitelist_search", methods={"GET"})
     *
     * @Template
     *
     * @return array
     */
    public function search(Request $request, WhitelistRepository $whitelistRepository) {
        $q = $request->query->get('q');
        if ($q) {
            $query = $whitelistRepository->searchQuery($q);
            $whitelists = $this->paginator->paginate($query, $request->query->getInt('page', 1), $this->getParameter('page_size'), ['wrap-queries' => true]);
        } else {
            $whitelists = [];
        }

        return [
            'whitelists' => $whitelists,
            'q' => $q,
        ];
    }

    /**
     * @Route("/new", name="whitelist_new", methods={"GET", "POST"})
     * @Template
     * @IsGranted("ROLE_CONTENT_ADMIN")
     *
     * @return array|RedirectResponse
     */
    public function new(Request $request) {
        $whitelist = new Whitelist();
        $form = $this->createForm(WhitelistType::class, $whitelist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($whitelist);
            $entityManager->flush();
            $this->addFlash('success', 'The new whitelist has been saved.');

            return $this->redirectToRoute('whitelist_show', ['id' => $whitelist->getId()]);
        }

        return [
            'whitelist' => $whitelist,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="whitelist_show", methods={"GET"})
     * @Template
     *
     * @return array
     */
    public function show(Whitelist $whitelist, ProviderRepository $repo) {
        $provider = $repo->findOneBy(['uuid' => $whitelist->getUuid()]);
        return [
            'whitelist' => $whitelist,
            'provider' => $provider,
        ];
    }

    /**
     * @IsGranted("ROLE_CONTENT_ADMIN")
     * @Route("/{id}/edit", name="whitelist_edit", methods={"GET", "POST"})
     *
     * @Template
     *
     * @return array|RedirectResponse
     */
    public function edit(Request $request, Whitelist $whitelist) {
        $form = $this->createForm(WhitelistType::class, $whitelist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The updated whitelist has been saved.');

            return $this->redirectToRoute('whitelist_show', ['id' => $whitelist->getId()]);
        }

        return [
            'whitelist' => $whitelist,
            'form' => $form->createView(),
        ];
    }

    /**
     * @IsGranted("ROLE_CONTENT_ADMIN")
     * @Route("/{id}", name="whitelist_delete", methods={"DELETE"})
     *
     * @return RedirectResponse
     */
    public function delete(Request $request, Whitelist $whitelist) {
        if ($this->isCsrfTokenValid('delete' . $whitelist->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($whitelist);
            $entityManager->flush();
            $this->addFlash('success', 'The whitelist has been deleted.');
        }

        return $this->redirectToRoute('whitelist_index');
    }
}
