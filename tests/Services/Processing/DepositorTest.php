<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services\Processing;

use App\DataFixtures\DepositFixtures;
use App\Services\Processing\Depositor;
use App\Services\SwordClient;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of HarvesterTest.
 */
class DepositorTest extends ControllerBaseCase {
    private $depositor;

    protected function fixtures() : array {
        return [
            DepositFixtures::class,
        ];
    }

    public function testInstance() : void {
        $this->assertInstanceOf(Depositor::class, $this->depositor);
    }

    public function testProcessDeposit() : void {
        $client = $this->createMock(SwordClient::class);
        $client->method('createDeposit')->willReturn(true);
        $this->depositor->setClient($client);
        $this->assertTrue($this->depositor->processDeposit($this->getReference('deposit.1')));
    }

    protected function setup() : void {
        parent::setUp();
        $this->depositor = self::$container->get(Depositor::class);
    }
}
