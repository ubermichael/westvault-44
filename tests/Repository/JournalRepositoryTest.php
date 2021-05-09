<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Repository;

use App\DataFixtures\ProviderFixtures;
use App\Entity\Blacklist;
use App\Entity\Provider;
use App\Entity\Whitelist;
use App\Repository\ProviderRepository;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of ProviderRepositoryTest.
 */
class ProviderRepositoryTest extends ControllerBaseCase {
    /**
     * @return ProviderRepository
     */
    private $repo;

    protected function fixtures() : array {
        return [
            ProviderFixtures::class,
        ];
    }

    public function testGetProvidersToPingNoListed() : void {
        $this->assertSame(4, count($this->repo->getProvidersToPing()));
    }

    public function testGetProvidersToPingListed() : void {
        $whitelist = new Whitelist();
        $whitelist->setUuid(ProviderFixtures::UUIDS[0]);
        $whitelist->setComment('Test');
        $this->entityManager->persist($whitelist);

        $blacklist = new Blacklist();
        $blacklist->setUuid(ProviderFixtures::UUIDS[1]);
        $blacklist->setComment('Test');
        $this->entityManager->persist($blacklist);

        $this->entityManager->flush();

        $this->assertSame(2, count($this->repo->getProvidersToPing()));
    }

    public function testGetProvidersToPingPingErrors() : void {
        $provider = $this->entityManager->find(Provider::class, 1);
        $provider->setStatus('ping-error');
        $this->entityManager->flush();

        $this->assertSame(3, count($this->repo->getProvidersToPing()));
    }

    /**
     * @dataProvider searchQueryData
     */
    public function testSearchQuery() : void {
        $query = $this->repo->searchQuery('CDC4');
        $result = $query->execute();
        $this->assertSame(1, count($result));
    }

    public function searchQueryData() {
        return [
            [1, 'CDC4'],
            [1, 'Title 1'],
            [1, '1234-1234'],
            [4, 'example.com'],
            [4, 'email@'],
            [4, 'PublisherName'],
            [1, 'publisher/1'],
        ];
    }

    protected function setup() : void {
        parent::setUp();
        $this->repo = $this->entityManager->getRepository(Provider::class);
    }
}
