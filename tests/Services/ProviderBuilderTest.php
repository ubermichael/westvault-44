<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\ProviderFixtures;
use App\Entity\Provider;
use App\Services\ProviderBuilder;
use App\Utilities\Namespaces;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of ProviderBuilderTest.
 */
class ProviderBuilderTest extends ControllerBaseCase {
    /**
     * @var ProviderBuilder
     */
    private $builder;

    private function getXml() {
        $data = <<<'ENDXML'
            <?xml version="1.0" encoding="utf-8"?>
            <entry xmlns="http://www.w3.org/2005/Atom"
                   xmlns:dcterms="http://purl.org/dc/terms/"
                   xmlns:pkp="http://pkp.sfu.ca/SWORD">
            	<email>user@example.com</email>
            	<title>Intl J Test</title>
            	<pkp:provider_url>http://example.com/ijt</pkp:provider_url>
            	<pkp:publisherName>Publisher institution</pkp:publisherName>
            	<pkp:publisherUrl>http://publisher.example.com</pkp:publisherUrl>
            	<pkp:issn>0000-0000</pkp:issn>
            	<id>urn:uuid:00FD6D96-0155-43A4-97F7-2C6EE8EBFF09</id>
            	<updated>1996-12-31T16:00:00Z</updated>
            	<pkp:content size="3613" volume="44" issue="4" pubdate="2015-07-14"
                        checksumType="SHA-1" checksumValue="25b0bd51bb05c145672617fced484c9e71ec553b">
                        http://ojs.dv/index.php/ijt/pln/deposits/00FD6D96-0155-43A4-97F7-2C6EE8EBFF09
                    </pkp:content>
            	<pkp:license>
                        <pkp:openAccessPolicy>Yes.</pkp:openAccessPolicy>
                        <pkp:licenseURL>http://creativecommons.org/licenses/by-nc-sa/4.0</pkp:licenseURL>
                        <pkp:publishingMode mode="0">Open</pkp:publishingMode>
                        <pkp:copyrightNotice>This is a copyright notice.</pkp:copyrightNotice>
                        <pkp:copyrightBasis>article</pkp:copyrightBasis>
                        <pkp:copyrightHolder>author</pkp:copyrightHolder>
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
        ];
    }

    public function testInstance() : void {
        $this->assertInstanceOf(ProviderBuilder::class, self::$container->get(ProviderBuilder::class));
    }

    public function testResultInstance() : void {
        $this->provider = $this->builder->fromXml($this->getXml(), 'B99FE131-48B5-440A-A552-4F1BF2BFDE82');
        $this->assertInstanceOf(Provider::class, $this->provider);
    }

    /**
     * @dataProvider providerXmlData
     *
     * @param mixed $expected
     * @param mixed $method
     */
    public function testFromXml($expected, $method) : void {
        $this->provider = $this->builder->fromXml($this->getXml(), 'B99FE131-48B5-440A-A552-4F1BF2BFDE82');
        $this->assertSame($expected, $this->provider->{$method}());
    }

    public function providerXmlData() {
        return [
            ['B99FE131-48B5-440A-A552-4F1BF2BFDE82', 'getUuid'],
            ['Intl J Test', 'getName'],
            ['user@example.com', 'getEmail'],
        ];
    }

    /**
     * @dataProvider providerRequestData
     *
     * @param mixed $expected
     * @param mixed $method
     */
    public function testFromRequest($expected, $method) : void {
        $this->provider = $this->builder->fromRequest('B99FE131-48B5-440A-A552-4F1BF2BFDE82', 'Provider Name');
        $this->assertSame($expected, $this->provider->{$method}());
    }

    public function providerRequestData() {
        return [
            ['B99FE131-48B5-440A-A552-4F1BF2BFDE82', 'getUuid'],
            ['Provider Name', 'getName'],
            ['unknown@unknown.com', 'getEmail'],
        ];
    }

    public function testFromRequestExisting() : void {
        $provider = $this->builder->fromRequest(ProviderFixtures::UUIDS[1], 'http://example.com/provider/0');
        $this->assertNotNull($provider);
    }

    protected function setup() : void {
        parent::setUp();
        $this->builder = self::$container->get(ProviderBuilder::class);
    }
}
