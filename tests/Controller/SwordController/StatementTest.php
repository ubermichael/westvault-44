<?php

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\ProviderFixtures;
use App\DataFixtures\WhitelistFixtures;
use App\Entity\Deposit;
use App\Entity\Provider;
use App\Entity\Whitelist;

class StatementTest extends AbstractSwordTestCase {

	// provider not whitelisted
	public function testStatementNotWhitelisted() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/' . ProviderFixtures::UUIDS[0] . '/' . DepositFixtures::UUIDS[0] . '/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertStringContainsString('Not authorized to request statements.', $this->client->getResponse()->getContent());
	}

	// requested provider uuid does not match deposit uuid.
	public function testStatementMismatch() {
        $this->client->request('GET', '/api/sword/2.0/cont-iri/' . ProviderFixtures::UUIDS[1] . '/' . DepositFixtures::UUIDS[0] . '/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertStringContainsString('Not authorized to request statements.', $this->client->getResponse()->getContent());
	}

	// provider uuid unknown.
	public function testStatementProviderNonFound() {
        $this->client->request('GET', '/api/sword/2.0/cont-iri/' . WhitelistFixtures::UUIDS[0] . '/' . DepositFixtures::UUIDS[0] . '/state');
		$this->assertEquals(404, $this->client->getResponse()->getStatusCode());
		$this->assertStringContainsString('Provider object not found', $this->client->getResponse()->getContent());
	}

	// deposit uuid unknown.
	public function testStatementDepositNonFound() {
        $this->client->request('GET', '/api/sword/2.0/cont-iri/' . ProviderFixtures::UUIDS[0] . '/9CB16316-9900-4494-8372-3814B6C5A492/state');
		$this->assertEquals(404, $this->client->getResponse()->getStatusCode());
		$this->assertStringContainsString('Deposit object not found', $this->client->getResponse()->getContent());
	}

	public function testStatement(){
	    $whitelist = new Whitelist();
	    $whitelist->setUuid(ProviderFixtures::UUIDS[0]);
	    $whitelist->setComment('');
	    $this->entityManager->persist($whitelist);
	    $this->entityManager->flush();

        $this->client->request('GET', '/api/sword/2.0/cont-iri/' . ProviderFixtures::UUIDS[0] . '/' . DepositFixtures::UUIDS[0] . '/state');
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
		$xml = $this->getXml($this->client);
		$this->assertEquals('http://example.com/deposit/1', $this->getXmlValue($xml, '//atom:content/text()'));
	}
}
