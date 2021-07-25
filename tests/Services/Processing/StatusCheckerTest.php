<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services\Processing;

use App\DataFixtures\DepositFixtures;
use App\Services\Processing\StatusChecker;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of HarvesterTest.
 */
class StatusCheckerTest extends ControllerBaseCase {
    private $checker;

    protected function fixtures() : array {
        return [
            DepositFixtures::class,
        ];
    }

    public function testInstance() : void {
        $this->assertInstanceOf(StatusChecker::class, $this->checker);
    }

    public function testProcessDeposit() : void {
    }

    protected function setup() : void {
        parent::setUp();
        $this->checker = self::$container->get('test.' . StatusChecker::class);
    }
}
