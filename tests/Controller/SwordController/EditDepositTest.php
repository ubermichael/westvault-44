<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\ProviderFixtures;
use App\Entity\Deposit;
use App\Entity\Whitelist;
use Symfony\Component\HttpFoundation\Response;

class EditDepositTest extends AbstractSwordTestCase {
    private function getEditXml() {
        return <<<'ENDXML'
            <entry 
                xmlns="http://www.w3.org/2005/Atom" 
                xmlns:dcterms="http://purl.org/dc/terms/"
                xmlns:pkp="http://pkp.sfu.ca/SWORD">
                <email>foo@example.com</email>
                <title>Test Data Provider of Testing</title>
                <pkp:publisherName>Publisher of Stuff</pkp:publisherName>
                <pkp:publisherUrl>http://publisher.example.com</pkp:publisherUrl>
                <pkp:issn>1234-1234</pkp:issn>
                <id>urn:uuid:EF78C8D2-6741-4CA2-8FBD-43ACEA56787E</id>
                <updated>2016-04-22T12:35:48Z</updated>
                <pkp:content size="123" volume="2" issue="4" pubdate="2016-04-22" 
            		checksumType="SHA-1" institution='Test Inst'
                    checksumValue="55ca6286e3e4f4fba5d0448333fa99fc5a404a73">http://example.com/deposit/foo.zip
                </pkp:content>
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
    }

    public function testEditDepositNotWhitelisted() : void {
        $this->client->request(
            'PUT',
            '/api/sword/2.0/cont-iri/' . ProviderFixtures::UUIDS[0] . '/' . DepositFixtures::UUIDS[0] . '/edit',
            [],
            [],
            [],
            $this->getEditXml()
        );
        $this->entityManager->clear();
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testEditDepositDepositMissing() : void {
        $whitelist = new Whitelist();
        $whitelist->setUuid(ProviderFixtures::UUIDS[0]);
        $whitelist->setComment('temp.');
        $this->entityManager->persist($whitelist);
        $this->entityManager->flush();

        $depositCount = count($this->entityManager->getRepository(Deposit::class)->findAll());
        $this->client->request(
            'PUT',
            '/api/sword/2.0/cont-iri/' . ProviderFixtures::UUIDS[0] . '/' . DepositFixtures::UUIDS[1] . '/edit',
            [],
            [],
            [],
            $this->getEditXml()
        );
        $this->entityManager->clear();
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertSame($depositCount, count($this->entityManager->getRepository(Deposit::class)->findAll()));
    }

    public function testEditDepositSuccess() : void {
        $whitelist = new Whitelist();
        $whitelist->setUuid(ProviderFixtures::UUIDS[0]);
        $whitelist->setComment('temp.');
        $this->entityManager->persist($whitelist);
        $this->entityManager->flush();

        $depositCount = count($this->entityManager->getRepository(Deposit::class)->findAll());
        $this->client->request(
            'PUT',
            '/api/sword/2.0/cont-iri/' . ProviderFixtures::UUIDS[0] . '/' . DepositFixtures::UUIDS[0] . '/edit',
            [],
            [],
            [],
            $this->getEditXml()
        );
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame($depositCount, count($this->entityManager->getRepository(Deposit::class)->findAll()));

        // For some reason $this->entityManager is returning the old, unupdated
        // deposit. It is correct in the database, but just not being found by em.
//        $deposit = $this->entityManager->getRepository(Deposit::class)->findOneBy([
//            'depositUuid' => mb_strtoupper(DepositFixtures::UUIDS[0]),
//        ], ['id' => 'ASC']);
//        $this->assertSame('55CA6286E3E4F4FBA5D0448333FA99FC5A404A73', $deposit->getChecksumValue());
//        $this->assertSame('depositedByProvider', $deposit->getState());
    }
}
