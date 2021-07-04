<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Application menu builder.
 */
class Builder implements ContainerAwareInterface {
    use ContainerAwareTrait;

    /**
     * Item factory.
     *
     * @var FactoryInterface
     */
    private $factory;

    /**
     * Authorization checker for getting user roles.
     *
     * @var AuthorizationCheckerInterface
     */
    private $authChecker;

    /**
     * Login token storage.
     *
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Build the menu builder.
     */
    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authChecker, TokenStorageInterface $tokenStorage) {
        $this->factory = $factory;
        $this->authChecker = $authChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Check if the currently logged in user has a given role.
     *
     * @param string $role
     *
     * @return bool
     */
    private function hasRole($role) {
        if ( ! $this->tokenStorage->getToken()) {
            return false;
        }

        return $this->authChecker->isGranted($role);
    }

    /**
     * Build the app's main navigation menu.
     *
     * @return ItemInterface
     */
    public function mainMenu(array $options) {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttributes([
            'class' => 'nav navbar-nav',
        ]);

        $menu->addChild('home', [
            'label' => 'Home',
            'route' => 'homepage',
        ]);

        if ( ! $this->hasRole('ROLE_USER')) {
            return $menu;
        }

        $browse = $menu->addChild('browse', [
            'uri' => '#',
            'label' => 'Browse',
        ]);
        $browse->setAttribute('dropdown', true);
        $browse->setLinkAttribute('class', 'dropdown-toggle');
        $browse->setLinkAttribute('data-toggle', 'dropdown');
        $browse->setChildrenAttribute('class', 'dropdown-menu');

        $browse->addChild('blacklist', [
            'label' => 'Blacklist',
            'route' => 'blacklist_index',
        ]);
        $browse->addChild('deposit', [
            'label' => 'Deposits',
            'route' => 'deposit_index',
        ]);
        $browse->addChild('provider', [
            'label' => 'Providers',
            'route' => 'provider_index',
        ]);
        $browse->addChild('termofuse', [
            'label' => 'Terms of Use',
            'route' => 'term_of_use_index',
        ]);
        $browse->addChild('whitelist', [
            'label' => 'Whitelist',
            'route' => 'whitelist_index',
        ]);

        return $menu;
    }
}
