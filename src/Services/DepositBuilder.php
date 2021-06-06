<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Deposit;
use App\Entity\Provider;
use App\Utilities\Xpath;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Description of DepositBuilder.
 */
class DepositBuilder {
    /**
     * Entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * URL generator.
     *
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * Build the service.
     */
    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $generator) {
        $this->em = $em;
        $this->generator = $generator;
    }

    /**
     * Build the URL for the deposit receipt.
     *
     * @param Deposit $deposit
     *
     * @return string
     */
    public function buildDepositReceiptUrl(Deposit $deposit)
    {
        return $this->generator->generate(
            'sword_statement',
            array(
                'provider_uuid' => $deposit->getProvider()->getUuid(),
                'deposit_uuid' => $deposit->getDepositUuid(),
            ),
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * Find and return the deposit with $uuid or create a new deposit.
     *
     * @param string $uuid
     *
     * @return Deposit
     */
    protected function findDeposit($uuid) {
        $deposit = $this->em->getRepository(Deposit::class)->findOneBy([
            'depositUuid' => mb_strtoupper($uuid),
        ]);
        $action = 'edit';
        if ( ! $deposit) {
            $deposit = new Deposit();
            $deposit->setDepositUuid($uuid);
        }

        return $deposit;
    }

    /**
     * Build a deposit from XML.
     *
     * @return Deposit
     */
    public function fromXml(Provider $provider, SimpleXMLElement $xml) {
        $id = Xpath::getXmlValue($xml, '//atom:id');
        $uuid = mb_substr($id, 9, 36);
        $deposit = $this->findDeposit($uuid);
        if ( ! $deposit->getId()) {
            $deposit = new Deposit();
            $deposit->setAction('add');
            $deposit->addToProcessingLog('Deposit received.');
            $deposit->setDepositUuid($uuid);
        } else {
            $deposit->setAction('edit');
            $deposit->addToProcessingLog('Deposit edited or reset by provider.');
        }
        $deposit->setState('depositedByProvider');
        $deposit->setChecksumType(Xpath::getXmlValue($xml, 'pkp:content/@checksumType'));
        $deposit->setChecksumValue(Xpath::getXmlValue($xml, 'pkp:content/@checksumValue'));
        $deposit->setFileType('');
        $deposit->setInstitution(Xpath::getXmlValue($xml, 'pkp:content/@institution'));
        $deposit->setProvider($provider);
        $deposit->setSize(Xpath::getXmlValue($xml, 'pkp:content/@size'));
        $deposit->setUrl(html_entity_decode(Xpath::getXmlValue($xml, 'pkp:content')));
        $deposit->setDepositReceipt($this->buildDepositReceiptUrl($deposit));
        $this->em->persist($deposit);

        return $deposit;
    }
}
