<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services\Processing;

use App\Entity\Deposit;
use App\Entity\Provider;
use App\Services\Processing\Harvester;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Description of HarvesterTest.
 */
class HarvesterTest extends ControllerBaseCase {
    private $harvester;

    public function testInstance() : void {
        $this->assertInstanceOf(Harvester::class, $this->harvester);
    }

    public function testWriteDeposit() : void {
        $mock = new MockHandler([
            new Response(200, [], 'abcdef'),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $response = $client->get('/');

        $fs = $this->createMock(Filesystem::class);

        $output = '';
        $fs->method('appendToFile')->willReturnCallback(function ($path, $bytes) use (&$output) : void {
            $output .= $bytes;
        });
        $this->harvester->setFilesystem($fs);
        $this->harvester->writeDeposit('', $response);
        $this->assertSame('abcdef', $output);
    }

    public function testWriteDepositNoBody() : void {
        $this->expectException(Exception::class);

        $mock = new MockHandler([
            new Response(200, [], ''),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $response = $client->get('/');
        $this->harvester->writeDeposit('', $response);
    }

    public function testFetchDeposit() : void {
        $mock = new MockHandler([
            new Response(200),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);

        $response = $this->harvester->fetchDeposit('http://example.com');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    /**
     * @group abc
     */
    public function testDepositException() : void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Harvest download error ');
        $mock = new MockHandler([
            new Response(404),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);
        $this->harvester->fetchDeposit('http://example.com');
        $this->fail('No exception thrown.');
    }

    public function testDepositRedirect() : void {
        $mock = new MockHandler([
            new Response(302, ['Location' => 'http://example.com/path']),
            new Response(200),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);
        $response = $this->harvester->fetchDeposit('http://example.com');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCheckSize() : void {
        $deposit = new Deposit();
        $deposit->setSize('1000');
        $deposit->setUrl('http://example.com/deposit');

        $mock = new MockHandler([
            new Response(200, ['Content-Length' => 1024]),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);
        $this->harvester->checkSize($deposit);
        $this->assertNotContains('Expected file size', $deposit->getErrorLog());
    }

    public function testCheckSizeBadResponse() : void {
        $this->expectException(Exception::class);
        $deposit = new Deposit();
        $deposit->setSize('1');
        $deposit->setUrl('http://example.com/deposit');

        $mock = new MockHandler([
            new Response(500),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);
        $this->harvester->checkSize($deposit);
    }

    public function testCheckSizeContentLengthMissing() : void {
        $this->expectException(Exception::class);
        $deposit = new Deposit();
        $deposit->setSize('1');
        $deposit->setUrl('http://example.com/deposit');

        $mock = new MockHandler([
            new Response(200),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);
        $this->harvester->checkSize($deposit);
    }

    public function testCheckSizeContentLengthZero() : void {
        $this->expectException(Exception::class);
        $deposit = new Deposit();
        $deposit->setSize('100');
        $deposit->setUrl('http://example.com/deposit');

        $mock = new MockHandler([
            new Response(200, ['Content-Length' => 0]),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);
        $this->harvester->checkSize($deposit);
    }

    public function testCheckSizeContentLengthMismatch() : void {
        $this->expectException(Exception::class);
        $deposit = new Deposit();
        $deposit->setSize('100');
        $deposit->setUrl('http://example.com/deposit');

        $mock = new MockHandler([
            new Response(200, ['Content-Length' => 10240]),
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);
        $this->harvester->checkSize($deposit);
    }

    public function testProcessDeposit() : void {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('appendToFile')->willReturn(null);
        $this->harvester->setFilesystem($fs);

        $mock = new MockHandler([
            new Response(200, ['Content-Length' => 1000]), // head request
            new Response(200, ['Content-Length' => 1000], 'abcdef'), // get request
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);

        $deposit = new Deposit();
        $deposit->setUrl('http://example.com/path');
        $deposit->setSize('1024');
        $provider = new Provider();
        $provider->setUuid('abc123');
        $deposit->setProvider($provider);
        $result = $this->harvester->processDeposit($deposit);
        $this->assertSame('', $deposit->getProcessingLog());
        $this->assertTrue($result);
    }

    public function testProcessDepositFailue() : void {
        $fs = $this->createMock(Filesystem::class);
        $fs->method('appendToFile')->willReturn(null);
        $this->harvester->setFilesystem($fs);

        $mock = new MockHandler([
            new Response(200, ['Content-Length' => 1000]), // head request
            new Response(200, ['Content-Length' => 1000], 'abcdef'), // get request
        ]);
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);
        $this->harvester->setClient($client);

        $deposit = new Deposit();
        $deposit->setUrl('http://example.com/path');
        $deposit->setSize('10');
        $provider = new Provider();
        $provider->setUuid('abc123');
        $deposit->setProvider($provider);
        $result = $this->harvester->processDeposit($deposit);
        $this->assertStringContainsStringIgnoringCase('Expected file size', $deposit->getProcessingLog());
        $this->assertFalse($result);
    }

    public function testProcessDepositTooManyFails() : void {
        $deposit = new Deposit();
        $deposit->setHarvestAttempts(13);
        $result = $this->harvester->processDeposit($deposit);
        $this->assertSame('harvest-error', $deposit->getState());
        $this->assertFalse($result);
    }

    protected function setup() : void {
        parent::setUp();
        $this->harvester = self::$container->get(Harvester::class);
    }
}
