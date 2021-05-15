<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Deposit;
use App\Entity\Provider;
use App\Entity\TermOfUse;
use App\Services\BlackWhiteList;
use App\Services\DepositBuilder;
use App\Services\ProviderBuilder;
use App\Utilities\Namespaces;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Psr\Log\LoggerAwareTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * An implementation of the SWORD v2 protocol.
 *
 * @Route("/api/sword/2.0")
 */
class SwordController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;
    use LoggerAwareTrait;

    /**
     * Black and white list service.
     *
     * @var BlackWhiteList
     */
    private $blackwhitelist;

    /**
     * Doctrine entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Build the controller.
     */
    public function __construct(BlackWhiteList $blackwhitelist, EntityManagerInterface $em) {
        $this->blackwhitelist = $blackwhitelist;
        $this->em = $em;
    }

    /**
     * Fetch an HTTP header.
     *
     * Checks the HTTP headers for $key and X-$key variant. If the app
     * is in the dev environment, will also check the query parameters for
     * $key.
     *
     * If $required is true and the header is not present BadRequestException
     * will be thrown.
     *
     * @param string $key
     * @param string $required
     *
     * @throws BadRequestHttpException
     *
     * @return null|string
     *                     The value of the header or null if that's OK.
     */
    private function fetchHeader(Request $request, $key, $required = false) {
        if ($request->headers->has($key)) {
            return $request->headers->get($key);
        }
        if ('dev' === $this->getParameter('kernel.environment') && $request->query->has($key)) {
            return $request->query->get($key);
        }
        if ($required) {
            throw new BadRequestHttpException("HTTP header {$key} is required.", null, Response::HTTP_BAD_REQUEST);
        }

        return '';
    }

    /**
     * Check if a provider's uuid is whitelised or blacklisted.
     *
     * The rules are:
     *
     * If the provider uuid is whitelisted, return true
     * If the provider uuid is blacklisted, return false
     * Return the pln_accepting parameter from parameters.yml
     *
     * @param string $uuid
     *
     * @return bool
     */
    private function checkAccess($uuid) {
        if ( ! $uuid) {
            return false;
        }
        if ($this->blackwhitelist->isWhitelisted($uuid)) {
            return true;
        }
        if ($this->blackwhitelist->isBlacklisted($uuid)) {
            return false;
        }

        return $this->getParameter('pln.accepting');
    }

    /**
     * Figure out which message to return for the network status widget in OJS.
     *
     * @return string
     */
    private function getNetworkMessage(Provider $provider) {
        if ($this->blackwhitelist->isWhitelisted($provider->getUuid())) {
            return $this->getParameter('pln.network_accepting');
        }

        return $this->getParameter('pln.network_default');
    }

    /**
     * Get the XML from an HTTP request.
     *
     * @throws BadRequestHttpException
     *
     * @return SimpleXMLElement
     */
    private function getXml(Request $request) {
        $content = $request->getContent();
        if ( ! $content || ! is_string($content)) {
            throw new BadRequestHttpException('Expected request body. Found none.', null, Response::HTTP_BAD_REQUEST);
        }

        try {
            $xml = simplexml_load_string($content);
            Namespaces::registerNamespaces($xml);

            return $xml;
        } catch (Exception $e) {
            throw new BadRequestHttpException('Cannot parse request XML.', $e, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Return a SWORD service document for a provider.
     *
     * Requires On-Behalf-Of and Provider-Url HTTP headers.
     *
     * @return array
     *
     * @Template
     * @Route("/sd-iri.{_format}", methods={"GET"},
     *     name="sword_service_document",
     *     defaults={"_format": "xml"},
     *     requirements={"_format": "xml"}
     * )
     */
    public function serviceDocumentAction(Request $request, ProviderBuilder $builder) {
        $obh = mb_strtoupper($this->fetchHeader($request, 'On-Behalf-Of'));
        $providerName = $this->fetchHeader($request, 'Provider-Name');

        $accepting = $this->checkAccess($obh);
        $this->logger->notice("{$request->getClientIp()} - service document - {$obh} - {$providerName} - accepting: " . ($accepting ? 'yes' : 'no'));
        if ( ! $obh) {
            throw new BadRequestHttpException('Missing On-Behalf-Of header.', null, 400);
        }
        if ( ! $providerName) {
            throw new BadRequestHttpException('Missing Provider-Url header.', null, 400);
        }

        $provider = $builder->fromRequest($obh, $providerName);
        $this->em->flush();
        $termsRepo = $this->getDoctrine()->getRepository(TermOfUse::class);

        return [
            'onBehalfOf' => $obh,
            'accepting' => $accepting ? 'Yes' : 'No',
            'maxUpload' => $this->getParameter('pln.max_upload'),
            'checksumType' => $this->getParameter('pln.checksum_type'),
            'message' => $this->getNetworkMessage($provider),
            'provider' => $provider,
            'terms' => $termsRepo->getTerms(),
            'termsUpdated' => $termsRepo->getLastUpdated(),
        ];
    }

    /**
     * Create a deposit.
     *
     * @return Response
     *
     * @Route("/col-iri/{uuid}", methods={"POST"}, name="sword_create_deposit", requirements={
     *     "uuid": ".{36}",
     * })
     * @ParamConverter("provider", options={"mapping": {"uuid": "uuid"}})
     */
    public function createDepositAction(Request $request, Provider $provider, ProviderBuilder $providerBuilder, DepositBuilder $depositBuilder) {
        $accepting = $this->checkAccess($provider->getUuid());
        $this->logger->notice("{$request->getClientIp()} - create deposit - {$provider->getUuid()} - accepting: " . ($accepting ? 'yes' : 'no'));

        if ( ! $accepting) {
            throw new BadRequestHttpException('Not authorized to create deposits.', null, 400);
        }

        $xml = $this->getXml($request);
        // Update the provider metadata.
        $providerBuilder->fromXml($xml, $provider->getUuid());
        $deposit = $depositBuilder->fromXml($provider, $xml);
        $this->em->flush();

        // @var Response
        $response = $this->statementAction($request, $provider, $deposit);
        $response->headers->set('Location', $this->generateUrl('sword_statement', [
            'provider_uuid' => $provider->getUuid(),
            'deposit_uuid' => $deposit->getDepositUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    /**
     * Check that status of a deposit by fetching the sword statemt.
     *
     * @return Response
     *
     * @Route("/cont-iri/{provider_uuid}/{deposit_uuid}/state", methods={"GET"}, name="sword_statement", requirements={
     *     "provider_uuid": ".{36}",
     *     "deposit_uuid": ".{36}"
     * })
     * @ParamConverter("provider", options={"mapping": {"provider_uuid": "uuid"}})
     * @ParamConverter("deposit", options={"mapping": {"deposit_uuid": "depositUuid"}})
     */
    public function statementAction(Request $request, Provider $provider, Deposit $deposit) {
        $accepting = $this->checkAccess($provider->getUuid());
        $this->logger->notice("{$request->getClientIp()} - statement - {$provider->getUuid()} - {$deposit->getDepositUuid()} - accepting: " . ($accepting ? 'yes' : 'no'));
        if ( ! $accepting && ! $this->isGranted('ROLE_USER')) {
            throw new BadRequestHttpException('Not authorized to request statements.', null, 400);
        }
        if ($provider !== $deposit->getProvider()) {
            throw new BadRequestHttpException('Deposit does not belong to provider.', null, 400);
        }
        $this->em->flush();
        $response = $this->render('sword/statement.xml.twig', [
            'deposit' => $deposit,
        ]);
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    /**
     * Edit a deposit with an HTTP PUT.
     *
     * @return Response
     *
     * @Route("/cont-iri/{provider_uuid}/{deposit_uuid}/edit", methods={"PUT"}, name="sword_edit", requirements={
     *     "provider_uuid": ".{36}",
     *     "deposit_uuid": ".{36}"
     * })
     * @ParamConverter("provider", options={"mapping": {"provider_uuid": "uuid"}})
     * @ParamConverter("deposit", options={"mapping": {"deposit_uuid": "depositUuid"}})
     */
    public function editAction(Request $request, Provider $provider, Deposit $deposit, DepositBuilder $builder) {
        $accepting = $this->checkAccess($provider->getUuid());
        $this->logger->notice("{$request->getClientIp()} - edit deposit - {$provider->getUuid()} - {$deposit->getDepositUuid()} - accepting: " . ($accepting ? 'yes' : 'no'));
        if ( ! $accepting) {
            throw new BadRequestHttpException('Not authorized to create deposits.', null, 400);
        }
        if ($provider !== $deposit->getProvider()) {
            throw new BadRequestHttpException('Deposit does not belong to provider.', null, 400);
        }
        $xml = $this->getXml($request);
        $newDeposit = $builder->fromXml($provider, $xml);
        $newDeposit->setAction('edit');
        $this->em->flush();

        // @var Response
        $response = $this->statementAction($request, $provider, $deposit);
        $response->headers->set('Location', $this->generateUrl('sword_statement', [
            'provider_uuid' => $provider->getUuid(),
            'deposit_uuid' => $deposit->getDepositUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
