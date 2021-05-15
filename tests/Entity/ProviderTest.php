<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase {
    protected $provider;

    public function testSetUuidLowercase() : void {
        $uuid = 'abcd1234';
        $this->provider->setUuid($uuid);
        $this->assertSame(mb_strtoupper($uuid), $this->provider->getUuid());
    }

    public function testSetUuidUppercase() : void {
        $uuid = 'ABCD1234';
        $this->provider->setUuid($uuid);
        $this->assertSame(mb_strtoupper($uuid), $this->provider->getUuid());
    }

    public function testGetCompletedDeposits() : void {
        $d1 = new Deposit();
        $d1->setState('completed');
        $this->provider->addDeposit($d1);
        $d2 = new Deposit();
        $this->provider->addDeposit($d2);
        $completed = $this->provider->getCompletedDeposits();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $completed);
        $this->assertCount(1, $completed);
    }

    public function testToStringEmptyTitle() : void {
        $this->assertSame('(unknown)', (string) $this->provider);
    }

    public function setUp() : void {
        $this->provider = new Provider();
    }
}
