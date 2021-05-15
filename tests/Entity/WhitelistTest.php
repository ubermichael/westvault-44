<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use PHPUnit\Framework\TestCase;

class WhitelistTest extends TestCase {
    protected $whitelist;

    public function testSetUuidLowercase() : void {
        $uuid = 'abc123';
        $this->whitelist->setUuid($uuid);
        $this->assertSame(mb_strtoupper($uuid), $this->whitelist->getUuid());
    }

    public function testSetUuidUppercase() : void {
        $uuid = 'ABC123';
        $this->whitelist->setUuid($uuid);
        $this->assertSame(mb_strtoupper($uuid), $this->whitelist->getUuid());
    }

    public function testToString() : void {
        $uuid = 'abc123';
        $this->whitelist->setUuid($uuid);
        $this->assertSame(mb_strtoupper($uuid), (string) $this->whitelist);
    }

    public function setUp() : void {
        $this->whitelist = new Whitelist();
    }
}
