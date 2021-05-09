<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\WhitelistFixtures;
use App\Entity\Provider;

class ServiceDocumentTest extends AbstractSwordTestCase {
    public function testServiceDocument() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
            'HTTP_Provider-Url' => 'http://example.com',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentNoOBH() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_Provider-Url' => 'http://example.com',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentNoProviderUrl() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentBadObh() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '',
            'HTTP_Provider-Url' => 'http://example.com',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentBadProviderUrl() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
            'HTTP_Provider-Url' => '',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentContentNewProvider() : void {
        $count = count($this->entityManager->getRepository(Provider::class)->findAll());

        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
            'HTTP_Provider-Url' => 'http://example.com',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml($this->client);
        $this->assertSame('service', $xml->getName());
        $this->assertSame('2.0', $this->getXmlValue($xml, '//sword:version'));
        $this->assertSame('No', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
        $this->assertSame('The PKP PLN does not know about this provider yet.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
        $this->assertCount(4, $xml->xpath('//pkp:terms_of_use/*'));
        $this->assertSame('PKP PLN deposit for 7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//atom:title'));
        $this->assertSame('http://localhost/api/sword/2.0/col-iri/7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//app:collection/@href'));

        $this->assertCount($count + 1, $this->entityManager->getRepository('App:Provider')->findAll());

        $provider = $this->entityManager->getRepository('App:Provider')->findOneBy(['uuid' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F']);
        $this->assertNotNull($provider);
        $this->assertNull($provider->getTitle());
        $this->assertSame('http://example.com', $provider->getUrl());
        $this->assertSame('new', $provider->getStatus());
    }

    public function testServiceDocumentContentWhitelistedProvider() : void {
        $count = count($this->entityManager->getRepository(Provider::class)->findAll());

        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => WhitelistFixtures::UUIDS[0],
            'HTTP_Provider-Url' => 'http://example.com',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml($this->client);
        $this->assertSame('service', $xml->getName());
        $this->assertSame('2.0', $this->getXmlValue($xml, '//sword:version'));
        $this->assertSame('Yes', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
        $this->assertSame('The PKP PLN can accept deposits from this provider.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
        $this->assertCount(4, $xml->xpath('//pkp:terms_of_use/*'));
        $this->assertSame('PKP PLN deposit for ' . WhitelistFixtures::UUIDS[0], $this->getXmlValue($xml, '//atom:title'));
        $this->assertSame('http://localhost/api/sword/2.0/col-iri/' . WhitelistFixtures::UUIDS[0], $this->getXmlValue($xml, '//app:collection/@href'));

        $this->entityManager->clear();
        $this->assertCount($count, $this->entityManager->getRepository('App:Provider')->findAll());
        $provider = $this->entityManager->getRepository('App:Provider')->findOneBy(['uuid' => WhitelistFixtures::UUIDS[0]]);
        $this->assertSame('http://example.com/provider/0', $provider->getUrl());
    }
}
