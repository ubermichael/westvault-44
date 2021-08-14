<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\ProviderFixtures;
use App\DataFixtures\WhitelistFixtures;
use App\Entity\Provider;

class ServiceDocumentTest extends AbstractSwordTestCase {
    public function testServiceDocument() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => ProviderFixtures::UUIDS[0],
            'HTTP_Provider-Name' => 'Provider Name',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentNoOBH() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_Provider-Name' => 'http://example.com',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentNoProviderName() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => ProviderFixtures::UUIDS[0],
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentBadObh() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '',
            'HTTP_Provider-Name' => 'Provider Name',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentBadProviderName() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => ProviderFixtures::UUIDS[0],
            'HTTP_Provider-Name' => '',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentContentNewProvider() : void {
        $this->assertCount(4, $this->entityManager->getRepository(Provider::class)->findAll());

        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
            'HTTP_Provider-Name' => 'New Provider Name',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml();
        $this->assertSame('service', $xml->getName());
        $this->assertSame('2.0', $this->getXmlValue($xml, '//sword:version'));
        $this->assertSame('No', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
        $this->assertSame('The WestVault PLN does not know about this provider yet.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
        $this->assertCount(4, $xml->xpath('//pkp:terms_of_use/*'));
        $this->assertSame('PKP PLN deposit for 7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//atom:title'));
        $this->assertSame('http://localhost/api/sword/2.0/col-iri/7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//app:collection/@href'));

        $this->assertCount(5, $this->entityManager->getRepository(Provider::class)->findAll());

        $provider = $this->entityManager->getRepository(Provider::class)->findOneBy(['uuid' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F']);
        $this->assertNotNull($provider);
    }

    public function testServiceDocumentContentWhitelistedProvider() : void {
        $this->assertCount(4, $this->entityManager->getRepository(Provider::class)->findAll());

        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => WhitelistFixtures::UUIDS[0],
            'HTTP_Provider-Name' => 'Provider Name',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml();
        $this->assertSame('service', $xml->getName());
        $this->assertSame('2.0', $this->getXmlValue($xml, '//sword:version'));
        $this->assertSame('Yes', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
        $this->assertSame('The WestVault PLN can accept deposits from this provider.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
        $this->assertCount(4, $xml->xpath('//pkp:terms_of_use/*'));
        $this->assertSame('PKP PLN deposit for ' . WhitelistFixtures::UUIDS[0], $this->getXmlValue($xml, '//atom:title'));
        $this->assertSame('http://localhost/api/sword/2.0/col-iri/' . WhitelistFixtures::UUIDS[0], $this->getXmlValue($xml, '//app:collection/@href'));

        $this->entityManager->clear();
        $this->assertCount(5, $this->entityManager->getRepository(Provider::class)->findAll());
        $provider = $this->entityManager->getRepository(Provider::class)->findOneBy(['uuid' => WhitelistFixtures::UUIDS[0]]);
        $this->assertNotNull($provider);
    }
}
