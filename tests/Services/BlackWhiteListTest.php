<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\BlacklistFixtures;
use App\DataFixtures\WhitelistFixtures;
use App\Services\BlackWhiteList;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class BlackWhiteListTest extends ControllerBaseCase {
    /**
     * @var BlackWhiteList
     */
    protected $list;

    public function fixtures() : array {
        return [
            BlacklistFixtures::class,
            WhitelistFixtures::class,
        ];
    }

    public function testInstance() : void {
        $this->assertInstanceOf(BlackWhiteList::class, $this->list);
    }

    public function testIsWhitelisted() : void {
        $this->assertNotNull($this->list->isWhitelisted(WhitelistFixtures::UUIDS[0]));
        $this->assertNotNull($this->list->isWhitelisted(mb_strtolower(WhitelistFixtures::UUIDS[0])));

        $this->assertNull($this->list->isWhitelisted(BlacklistFixtures::UUIDS[0]));
        $this->assertNull($this->list->isWhitelisted(mb_strtolower(BlacklistFixtures::UUIDS[0])));
    }

    public function testIsBlacklisted() : void {
        $this->assertNotNUll($this->list->isBlacklisted(BlacklistFixtures::UUIDS[0]));
        $this->assertNotNull($this->list->isBlacklisted(mb_strtolower(BlacklistFixtures::UUIDS[0])));

        $this->assertNull($this->list->isBlacklisted(WhitelistFixtures::UUIDS[0]));
        $this->assertNull($this->list->isBlacklisted(mb_strtolower(WhitelistFixtures::UUIDS[0])));
    }

    protected function setup() : void {
        parent::setUp();
        $this->list = self::$container->get(BlackWhiteList::class);
    }
}
