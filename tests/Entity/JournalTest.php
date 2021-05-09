<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\Provider;
use DateTime;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of ProviderTest.
 */
class ProviderTest extends ControllerBaseCase {
    private $provider;

    public function testInstance() : void {
        $this->assertInstanceOf(Provider::class, $this->provider);
    }

    public function testSetUuid() : void {
        $this->provider->setUuid('abc123');
        $this->assertSame('ABC123', $this->provider->getUuid());
    }

    public function testSetNotified() : void {
        $this->provider->setNotified(new DateTime());
        $this->assertInstanceOf(DateTime::class, $this->provider->getNotified());
    }

    protected function setup() : void {
        parent::setUp();
        $this->provider = new Provider();
    }
}
