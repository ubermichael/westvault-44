<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\WhitelistFixtures;
use App\Entity\Journal;

class ServiceDocumentTest extends AbstractSwordTestCase {
    public function testServiceDocument() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
            'HTTP_Journal-Url' => 'http://example.com',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentNoOBH() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_Journal-Url' => 'http://example.com',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentNoJournalUrl() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentBadObh() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '',
            'HTTP_Journal-Url' => 'http://example.com',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentBadJournalUrl() : void {
        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
            'HTTP_Journal-Url' => '',
        ]);
        $this->assertSame(400, $this->client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentContentNewJournal() : void {
        $count = count($this->entityManager->getRepository(Journal::class)->findAll());

        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
            'HTTP_Journal-Url' => 'http://example.com',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml($this->client);
        $this->assertSame('service', $xml->getName());
        $this->assertSame('2.0', $this->getXmlValue($xml, '//sword:version'));
        $this->assertSame('No', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
        $this->assertSame('The PKP PLN does not know about this journal yet.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
        $this->assertCount(4, $xml->xpath('//pkp:terms_of_use/*'));
        $this->assertSame('PKP PLN deposit for 7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//atom:title'));
        $this->assertSame('http://localhost/api/sword/2.0/col-iri/7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//app:collection/@href'));

        $this->assertCount($count + 1, $this->entityManager->getRepository('App:Journal')->findAll());

        $journal = $this->entityManager->getRepository('App:Journal')->findOneBy(['uuid' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F']);
        $this->assertNotNull($journal);
        $this->assertNull($journal->getTitle());
        $this->assertSame('http://example.com', $journal->getUrl());
        $this->assertSame('new', $journal->getStatus());
    }

    public function testServiceDocumentContentWhitelistedJournal() : void {
        $count = count($this->entityManager->getRepository(Journal::class)->findAll());

        $this->client->request('GET', '/api/sword/2.0/sd-iri', [], [], [
            'HTTP_On-Behalf-Of' => WhitelistFixtures::UUIDS[0],
            'HTTP_Journal-Url' => 'http://example.com',
        ]);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $xml = $this->getXml($this->client);
        $this->assertSame('service', $xml->getName());
        $this->assertSame('2.0', $this->getXmlValue($xml, '//sword:version'));
        $this->assertSame('Yes', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
        $this->assertSame('The PKP PLN can accept deposits from this journal.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
        $this->assertCount(4, $xml->xpath('//pkp:terms_of_use/*'));
        $this->assertSame('PKP PLN deposit for ' . WhitelistFixtures::UUIDS[0], $this->getXmlValue($xml, '//atom:title'));
        $this->assertSame('http://localhost/api/sword/2.0/col-iri/' . WhitelistFixtures::UUIDS[0], $this->getXmlValue($xml, '//app:collection/@href'));

        $this->entityManager->clear();
        $this->assertCount($count, $this->entityManager->getRepository('App:Journal')->findAll());
        $journal = $this->entityManager->getRepository('App:Journal')->findOneBy(['uuid' => WhitelistFixtures::UUIDS[0]]);
        $this->assertSame('http://example.com/journal/0', $journal->getUrl());
    }
}
