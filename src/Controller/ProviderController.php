<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Provider;
use App\Form\ProviderType;
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
 * @Route("/provider")
 */
class ProviderController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * @Route("/", name="provider_index", methods={"GET"})
     *
     * @Template
     */
    public function index(Request $request, ProviderRepository $providerRepository) : array {
        $query = $providerRepository->indexQuery();
        $pageSize = (int) $this->getParameter('page_size');
        $page = $request->query->getint('page', 1);

        return [
            'providers' => $this->paginator->paginate($query, $page, $pageSize),
        ];
    }

    /**
     * @Route("/search", name="provider_search", methods={"GET"})
     *
     * @Template
     *
     * @return array
     */
    public function search(Request $request, ProviderRepository $providerRepository) {
        $q = $request->query->get('q');
        if ($q) {
            $query = $providerRepository->searchQuery($q);
            $providers = $this->paginator->paginate($query, $request->query->getInt('page', 1), $this->getParameter('page_size'), ['wrap-queries' => true]);
        } else {
            $providers = [];
        }

        return [
            'providers' => $providers,
            'q' => $q,
        ];
    }

    /**
     * @Route("/new", name="provider_new", methods={"GET", "POST"})
     * @Template
     * @IsGranted("ROLE_CONTENT_ADMIN")
     *
     * @return array|RedirectResponse
     */
    public function new(Request $request) {
        $provider = new Provider();
        $form = $this->createForm(ProviderType::class, $provider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($provider);
            $entityManager->flush();
            $this->addFlash('success', 'The new provider has been saved.');

            return $this->redirectToRoute('provider_show', ['id' => $provider->getId()]);
        }

        return [
            'provider' => $provider,
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/{id}", name="provider_show", methods={"GET"})
     * @Template
     *
     * @return array
     */
    public function show(Provider $provider) {
        return [
            'provider' => $provider,
        ];
    }

    /**
     * @IsGranted("ROLE_CONTENT_ADMIN")
     * @Route("/{id}/edit", name="provider_edit", methods={"GET", "POST"})
     *
     * @Template
     *
     * @return array|RedirectResponse
     */
    public function edit(Request $request, Provider $provider) {
        $form = $this->createForm(ProviderType::class, $provider);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('success', 'The updated provider has been saved.');

            return $this->redirectToRoute('provider_show', ['id' => $provider->getId()]);
        }

        return [
            'provider' => $provider,
            'form' => $form->createView(),
        ];
    }

    /**
     * @IsGranted("ROLE_CONTENT_ADMIN")
     * @Route("/{id}", name="provider_delete", methods={"DELETE"})
     *
     * @return RedirectResponse
     */
    public function delete(Request $request, Provider $provider) {
        if ($this->isCsrfTokenValid('delete' . $provider->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($provider);
            $entityManager->flush();
            $this->addFlash('success', 'The provider has been deleted.');
        }

        return $this->redirectToRoute('provider_index');
    }
}
