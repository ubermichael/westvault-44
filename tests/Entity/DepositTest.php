<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use PHPUnit\Framework\TestCase;

class DepositTest extends TestCase {
    protected $deposit;

    public function testDefaults() : void {
        $this->assertSame('depositedByProvider', $this->deposit->getState());
    }

    public function testSetDepositUuidLowercase() : void {
        $uuid = 'abc123';
        $this->deposit->setDepositUuid($uuid);
        $this->assertSame(mb_strtoupper($uuid), $this->deposit->getDepositUuid());
    }

    public function testSetDepositUuidUppercase() : void {
        $uuid = 'ABC123';
        $this->deposit->setDepositUuid($uuid);
        $this->assertSame($uuid, $this->deposit->getDepositUuid());
    }

    public function testSetDepositChecksumValueLowercase() : void {
        $value = 'abc123';
        $this->deposit->setChecksumValue($value);
        $this->assertSame(mb_strtoupper($value), $this->deposit->getChecksumValue());
    }

    public function testSetDepositChecksumValueUppercase() : void {
        $value = 'ABC123';
        $this->deposit->setChecksumValue($value);
        $this->assertSame($value, $this->deposit->getChecksumValue());
    }

    public function testToString() : void {
        $uuid = 'abc123';
        $this->deposit->setDepositUuid($uuid);
        $this->assertSame(mb_strtoupper($uuid), (string) $this->deposit);
    }

    public function testAddToProcessingLog() : void {
        $this->deposit->addToProcessingLog('hello world.');
        $log = $this->deposit->getProcessingLog();
        $this->assertCount(4, explode("\n", $log));
        $this->assertStringEndsWith("hello world.\n\n", $log);
    }

    public function testSetChecksumValueLowercase() : void {
        $value = 'abc123';
        $this->deposit->setChecksumValue($value);
        $this->assertSame(mb_strtoupper($value), $this->deposit->getChecksumValue());
    }

    public function testSetChecksumValueUppercase() : void {
        $value = 'ABC123';
        $this->deposit->setChecksumValue($value);
        $this->assertSame(mb_strtoupper($value), $this->deposit->getChecksumValue());
    }

    public function setUp() : void {
        $this->deposit = new Deposit();
    }
}
