<?php

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\ProviderFixtures;
use App\DataFixtures\WhitelistFixtures;
use App\Entity\Provider;

class ServiceDocumentTest extends AbstractSwordTestCase {

	public function testServiceDocument() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => ProviderFixtures::UUIDS[0],
			'HTTP_Provider-Name' => 'Provider Name'
		));
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
	}

	public function testServiceDocumentNoOBH() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_Provider-Name' => 'http://example.com'
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}

	public function testServiceDocumentNoProviderName() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
            'HTTP_On-Behalf-Of' => ProviderFixtures::UUIDS[0],
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}

	public function testServiceDocumentBadObh() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '',
            'HTTP_Provider-Name' => 'Provider Name'
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}

	public function testServiceDocumentBadProviderName() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
            'HTTP_On-Behalf-Of' => ProviderFixtures::UUIDS[0],
			'HTTP_Provider-Name' => ''
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}

	public function testServiceDocumentContentNewProvider() {
		$this->assertCount(4, $this->entityManager->getRepository(Provider::class)->findAll());

		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
            'HTTP_Provider-Name' => 'New Provider Name'
		));
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
		$xml = $this->getXml();
		$this->assertEquals('service', $xml->getName());
		$this->assertEquals(2.0, $this->getXmlValue($xml, '//sword:version'));
		$this->assertEquals('No', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
		$this->assertEquals('The WestVault PLN does not know about this provider yet.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
		$this->assertCount(4, $xml->xpath('//pkp:terms_of_use/*'));
		$this->assertEquals('PKP PLN deposit for 7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//atom:title'));
		$this->assertEquals('http://localhost/api/sword/2.0/col-iri/7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//app:collection/@href'));

		$this->assertCount(5, $this->entityManager->getRepository(Provider::class)->findAll());

		$provider = $this->entityManager->getRepository(Provider::class)->findOneBy(array('uuid' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F'));
		$this->assertNotNull($provider);
	}

	public function testServiceDocumentContentWhitelistedProvider() {
		$this->assertCount(4, $this->entityManager->getRepository(Provider::class)->findAll());

		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => WhitelistFixtures::UUIDS[0],
			'HTTP_Provider-Name' => 'Provider Name'
		));
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
		$xml = $this->getXml();
		$this->assertEquals('service', $xml->getName());
		$this->assertEquals(2.0, $this->getXmlValue($xml, '//sword:version'));
		$this->assertEquals('Yes', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
		$this->assertEquals('The WestVault PLN can accept deposits from this provider.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
		$this->assertCount(4, $xml->xpath('//pkp:terms_of_use/*'));
		$this->assertEquals('PKP PLN deposit for ' . WhitelistFixtures::UUIDS[0], $this->getXmlValue($xml, '//atom:title'));
		$this->assertEquals('http://localhost/api/sword/2.0/col-iri/' . WhitelistFixtures::UUIDS[0], $this->getXmlValue($xml, '//app:collection/@href'));

		$this->entityManager->clear();
		$this->assertCount(5, $this->entityManager->getRepository(Provider::class)->findAll());
		$provider = $this->entityManager->getRepository(Provider::class)->findOneBy(array('uuid' => WhitelistFixtures::UUIDS[0]));
		$this->assertNotNull($provider);
	}
}
