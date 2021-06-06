<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Blacklist;
use App\Entity\Whitelist;
use App\Form\BlacklistType;
use App\Repository\BlacklistRepository;

use App\Repository\ProviderRepository;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/blacklist")
 */
class BlacklistController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * @Route("/", name="blacklist_index", methods={"GET"})
     *
     * @Template
     */
    public function index(Request $request, BlacklistRepository $blacklistRepository) : array {
        $query = $blacklistRepository->indexQuery();
        $pageSize = (int) $this->getParameter('page_size');
        $page = $request->query->getint('page', 1);

        return [
            'blacklists' => $this->paginator->paginate($query, $page, $pageSize),
        ];
    }

    /**
     * @Route("/search", name="blacklist_search", methods={"GET"})
     *
     * @Template
     *
     * @return array
     */
    public function search(Request $request, BlacklistRepository $blacklistRepository) {
        $q = $request->query->get('q');
        if ($q) {
            $query = $blacklistRepository->searchQuery($q);
            $blacklists = $this->paginator->paginate($query, $request->query->getInt('page', 1), $this->getParameter('page_size'), ['wrap-queries' => true]);
        } else {
            $blacklists = [];
        }

        return [
            'blacklists' => $blacklists,
            'q' => $q,
        ];
    }

    /**
     * @Route("/new", name="blacklist_new", methods={"GET", "POST"})
     * @Template
     * @IsGranted("ROLE_CONTENT_ADMIN")
     *
     * @return array|RedirectResponse
     */
    public function new(Request $request) {
        $blacklist = new Blacklist();
        $form = $this->createForm(BlacklistType::class, $blacklist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($blacklist);
            $entityManager->flush();
            $this->addFlash('success', 'The new blacklist has been saved.');

            return $this->redirectToRoute('blacklist_show', ['id' => $blacklist->getId()]);
        }

        return [
            'blacklist' => $blacklist,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="blacklist_show", methods={"GET"})
     * @Template
     *
     * @return array
     */
    public function show(Blacklist $blacklist, ProviderRepository $repo) {
        $provider = $repo->findOneBy(['uuid' => $blacklist->getUuid()]);
        return [
            'blacklist' => $blacklist,
            'provider' => $provider,
        ];
    }

    /**
     * @IsGranted("ROLE_CONTENT_ADMIN")
     * @Route("/{id}/edit", name="blacklist_edit", methods={"GET", "POST"})
     *
     * @Template
     *
     * @return array|RedirectResponse
     */
    public function edit(Request $request, Blacklist $blacklist) {
        $form = $this->createForm(BlacklistType::class, $blacklist);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The updated blacklist has been saved.');

            return $this->redirectToRoute('blacklist_show', ['id' => $blacklist->getId()]);
        }

        return [
            'blacklist' => $blacklist,
            'form' => $form->createView(),
        ];
    }

    /**
     * @IsGranted("ROLE_CONTENT_ADMIN")
     * @Route("/{id}", name="blacklist_delete", methods={"DELETE"})
     *
     * @return RedirectResponse
     */
    public function delete(Request $request, Blacklist $blacklist) {
        if ($this->isCsrfTokenValid('delete' . $blacklist->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($blacklist);
            $entityManager->flush();
            $this->addFlash('success', 'The blacklist has been deleted.');
        }

        return $this->redirectToRoute('blacklist_index');
    }
}
