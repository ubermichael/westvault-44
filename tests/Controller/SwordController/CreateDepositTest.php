<?php

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\ProviderFixtures;
use App\DataFixtures\WhitelistFixtures;
use App\Entity\Deposit;
use App\Entity\Provider;
use App\Entity\Whitelist;
use Symfony\Component\HttpFoundation\Response;

class CreateDepositTest extends AbstractSwordTestCase {

	public function testCreateDepositWhitelisted() {
	    $provider = new Provider();
	    $provider->setName("Provider Name");
	    $provider->setUuid(WhitelistFixtures::UUIDS[0]);
	    $this->entityManager->persist($provider);
	    $this->entityManager->flush();
		$this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . WhitelistFixtures::UUIDS[0],
            array(),
            array(),
            ['HTTP_On-Behalf-Of' => WhitelistFixtures::UUIDS[0]],
            $this->getDepositXml()
		);
		$response = $this->client->getResponse();
        $xml = $this->getXml($this->client);
		$this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
		$this->assertEquals('http://localhost/api/sword/2.0/cont-iri/' . WhitelistFixtures::UUIDS[0] . '/6222557F-88A7-478D-9BE5-6AEB373E1ACC/state', $response->headers->get('Location'));
		$this->entityManager->refresh($provider);
		$this->assertEquals(1, count($provider->getDeposits()));
		$this->assertEquals('depositedByProvider', $this->getXmlValue($xml, '//atom:category[@label="Processing State"]/@term'));
	}

	public function testCreateDepositNotWhitelisted() {
		$depositCount = count($this->entityManager->getRepository(Deposit::class)->findAll());
		$this->client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . ProviderFixtures::UUIDS[0],
            array(),
            array(),
            array(),
            $this->getDepositXml()
		);
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertStringContainsString('Not authorized to create deposits.', $this->client->getResponse()->getContent());
		$this->assertEquals($depositCount, count($this->entityManager->getRepository(Deposit::class)->findAll()));
	}

	private function getDepositXml() {
		$str = <<<"ENDXML"
<entry 
    xmlns="http://www.w3.org/2005/Atom" 
    xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:pkp="http://pkp.sfu.ca/SWORD">
    <email>foo@example.com</email>
    <title>Test Data Provider of Testing</title>
    <pkp:publisherName>Publisher of Stuff</pkp:publisherName>
    <pkp:publisherUrl>http://publisher.example.com</pkp:publisherUrl>
    <pkp:issn>1234-1234</pkp:issn>
    <id>urn:uuid:6222557F-88A7-478D-9BE5-6AEB373E1ACC</id>
    <updated>2016-04-22T12:35:48Z</updated>
    <pkp:content size="123" volume="2" issue="4" pubdate="2016-04-22" 
		checksumType="SHA-1" institution='Test Inst'
        checksumValue="d46c034ef54c36237b89d456968965432830a603">http://example.com/deposit/foo.zip</pkp:content>
    <pkp:license>
        <pkp:publishingMode>Open</pkp:publishingMode>
        <pkp:openAccessPolicy>OA GOOD</pkp:openAccessPolicy>
        <pkp:licenseUrl>http://example.com/license</pkp:licenseUrl>
        <pkp:copyrightNotice>Copyright ME</pkp:copyrightNotice>
        <pkp:copyrightBasis>ME</pkp:copyrightBasis>
        <pkp:copyrightHolder>MYSELF</pkp:copyrightHolder>
    </pkp:license>
</entry>
ENDXML;
		return $str;
	}
}

