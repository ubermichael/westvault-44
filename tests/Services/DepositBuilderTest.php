<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\ProviderFixtures;
use App\Entity\Deposit;
use App\Services\DepositBuilder;
use App\Utilities\Namespaces;
use DateTimeImmutable;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of DepositBuilderTest.
 */
class DepositBuilderTest extends ControllerBaseCase {
    private $deposit;

    private function getXml() {
        $data = <<<'ENDXML'
            <entry xmlns='http://www.w3.org/2005/Atom' 
                   xmlns:dcterms='http://purl.org/dc/terms/' 
                   xmlns:pkp='http://pkp.sfu.ca/SWORD'>
            	<email>user@example.com</email>
            	<title>Intl J Test</title>
            	<pkp:provider_url>http://example.com/ijt</pkp:provider_url>
            	<pkp:publisherName>Publisher institution</pkp:publisherName>
            	<pkp:publisherUrl>http://publisher.example.com</pkp:publisherUrl>
            	<pkp:issn>0000-0000</pkp:issn>
            	<id>urn:uuid:00FD6D96-0155-43A4-97F7-2C6EE8EBFF09</id>
            	<updated>1996-12-31T16:00:00Z</updated>
            	<pkp:content size='3613' volume='44' issue='4' pubdate='2015-07-14' 
                        checksumType='SHA-1' checksumValue='25b0bd51bb05c145672617fced484c9e71ec553b'>
                        http://example.com//00FD6D96-0155-43A4-97F7-2C6EE8EBFF09
                    </pkp:content>
            	<pkp:license>
                        <pkp:publishingMode mode='0'>Open</pkp:publishingMode>
            	</pkp:license>
            </entry>   
            ENDXML;
        $xml = simplexml_load_string($data);
        Namespaces::registerNamespaces($xml);

        return $xml;
    }

    public function fixtures() : array {
        return [
            ProviderFixtures::class,
            DepositFixtures::class,
        ];
    }

    public function testInstance() : void {
        $this->assertInstanceOf(DepositBuilder::class, self::$container->get(DepositBuilder::class));
    }

    public function testBuildInstance() : void {
        $this->assertInstanceOf(Deposit::class, $this->deposit);
    }

    public function testReceived() : void {
        $this->assertInstanceOf(DateTimeImmutable::class, $this->deposit->getReceived());
    }

    public function testProcessingLog() : void {
        $this->assertStringEndsWith("Deposit received.\n\n", $this->deposit->getProcessingLog());
    }

    /**
     * @dataProvider depositData
     *
     * @param mixed $expected
     * @param mixed $method
     */
    public function testNewDeposit($expected, $method) : void {
        $this->assertSame($expected, $this->deposit->{$method}());
    }

    public function depositData() {
        return [
            ['', 'getFileType'],
            ['00FD6D96-0155-43A4-97F7-2C6EE8EBFF09', 'getDepositUuid'],
            ['add', 'getAction'],
            ['sha-1', 'getChecksumType'],
            ['25B0BD51BB05C145672617FCED484C9E71EC553B', 'getChecksumValue'],
            ['http://example.com//00FD6D96-0155-43A4-97F7-2C6EE8EBFF09', 'getUrl'],
            ['3613', 'getSize'],
            ['depositedByProvider', 'getState'],
            [[], 'getErrorLog'],
            [null, 'getPlnState'],
            [null, 'getDepositDate'],
            ['http://localhost/pkppln-44/public/api/sword/2.0/cont-iri/CF566EC4-0469-42B4-9C0C-8EC3154DDE87/00FD6D96-0155-43A4-97F7-2C6EE8EBFF09/state', 'getDepositReceipt'],
            [0, 'getHarvestAttempts'],
        ];
    }

    protected function setup() : void {
        parent::setUp();
        $builder = self::$container->get('test.' . DepositBuilder::class);
        if ( ! $this->deposit) {
            $this->deposit = $builder->fromXml($this->getReference('provider.1'), $this->getXml());
        }
    }
}
