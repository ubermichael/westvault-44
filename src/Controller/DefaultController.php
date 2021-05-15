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
use App\Services\FilePaths;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Default controller.
 */
class DefaultController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Home page action.
     *
     * @return Response
     *
     * @Route("/", name="homepage", methods={"GET"})
     */
    public function indexAction(EntityManagerInterface $em) {
        $user = $this->getUser();

        if ( ! $user || ! $user->hasRole('ROLE_USER')) {
            return $this->render('default/index_anon.html.twig');
        }

        return $this->render('default/index_user.html.twig');
    }

    /**
     * View one document.
     *
     * @param string $path
     * @Route("/docs/{path}", name="doc_view")
     * @Template
     *
     * @return array
     */
    public function docsViewAction($path) {
        $em = $this->container->get('doctrine');
        $user = $this->getUser();
        $doc = $em->getRepository('AppBundle:Document')->findOneBy([
            'path' => $path,
        ]);
        if ( ! $doc) {
            throw new NotFoundHttpException("The requested page {$path} could not be found.");
        }

        return ['doc' => $doc];
    }

    /**
     * @Route("/docs", name="doc_list")
     * @Template
     *
     * @return array
     */
    // Must be after docsViewAction()
    public function docsListAction() {
        $em = $this->container->get('doctrine');
        $docs = $em->getRepository('AppBundle:Document')->findAll();

        return ['docs' => $docs];
    }

    /**
     * Return the permission statement for LOCKSS.
     *
     * @Route("/permission", name="lockss_permission")
     *
     * @return Response
     */
    public function permissionAction(Request $request) {
        $this->get('monolog.logger.lockss')->notice("permission - {$request->getClientIp()}");

        return new Response(self::PERMISSION_STMT, Response::HTTP_OK, [
            'content-type' => 'text/plain',
        ]);
    }

    /**
     * Fetch a processed and packaged deposit.
     *
     * @Route("/fetch/{providerUuid}/{depositUuid}", name="fetch")
     *
     * @param string $providerUuid
     * @param string $depositUuid
     *
     * @return BinaryFileResponse
     */
    public function fetchAction(Request $request, $providerUuid, $depositUuid, FilePaths $filePaths) {
        $providerUuid = mb_strtoupper($providerUuid);
        $depositUuid = mb_strtoupper($depositUuid);
        $logger = $this->get('monolog.logger.lockss');
        $logger->notice("fetch - {$request->getClientIp()} - {$providerUuid} - {$depositUuid}");
        $em = $this->container->get('doctrine');
        $provider = $em->getRepository(Provider::class)->findOneBy(['uuid' => $providerUuid]);
        $deposit = $em->getRepository(Deposit::class)->findOneBy(['depositUuid' => $depositUuid]);
        if ( ! $deposit) {
            $logger->error("fetch - 404 DEPOSIT NOT FOUND - {$request->getClientIp()} - {$providerUuid} - {$depositUuid}");

            throw new NotFoundHttpException("Deposit {$providerUuid}/{$depositUuid} does not exist.");
        }
        if ($deposit->getProvider()->getId() !== $provider->getId()) {
            $logger->error("fetch - 400 JOURNAL MISMATCH - {$request->getClientIp()} - {$providerUuid} - {$depositUuid}");

            throw new BadRequestHttpException("The requested Provider ID does not match the deposit's provider ID.");
        }
        $path = $filePaths->getHarvestFile($deposit);
        $fs = new Filesystem();
        if ( ! $fs->exists($path)) {
            $logger->error("fetch - 404 PACKAGE NOT FOUND - {$request->getClientIp()} - {$providerUuid} - {$depositUuid}");

            throw new NotFoundHttpException("File {$providerUuid}/{$depositUuid} does not exist.");
        }

        return new BinaryFileResponse($path);
    }

    /**
     * Someone requested an RSS feed of the Terms of Use. This route generates
     * the feed in a RSS, Atom, and a custom JSON format as requested. It might
     * not be used anywhere.
     *
     * This URI must be public in security.yml
     *
     * @Route("/feeds/terms.{_format}",
     *     defaults={"_format": "atom"},
     *     name="feed_terms",
     *     requirements={"_format": "json|rss|atom"}
     * )
     * @Template
     *
     * @return array
     */
    public function termsFeedAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository(TermOfUse::class);
        $terms = $repo->getTerms();

        return ['terms' => $terms];
    }
}
