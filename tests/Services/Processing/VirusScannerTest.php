<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services\Processing;

use App\Services\Processing\VirusScanner;
use App\Utilities\XmlParser;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use Socket\Raw\Factory;
use Socket\Raw\Socket;
use Xenolope\Quahog\Client;

/**
 * This test makes use of the EICAR test signature found here:
 * http://www.eicar.org/86-0-Intended-use.html.
 */
class VirusScannerTest extends ControllerBaseCase {
    /**
     * @var VirusScanner
     */
    private $scanner;

    public function testInstance() : void {
        $this->assertInstanceOf(VirusScanner::class, $this->scanner);
    }

    public function testGetClient() : void {
        $factory = $this->createMock(Factory::class);
        $factory->method('createClient')->willReturn(new Socket(null));
        $this->scanner->setFactory($factory);
        $client = $this->scanner->getClient();
        $this->assertInstanceOf(Client::class, $client);
    }

    protected function setup() : void {
        parent::setUp();
        $this->scanner = self::$container->get(VirusScanner::class);
    }
}
