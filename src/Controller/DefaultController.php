<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Default controller.
 */
class DefaultController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * The LOCKSS permision statement.
     */
    public const PERMISSION_STMT = 'LOCKSS system has permission to collect, preserve, and serve this Archival Unit.';

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
}
