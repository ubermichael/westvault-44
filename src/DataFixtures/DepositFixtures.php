<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Deposit;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DepositFixtures extends Fixture implements DependentFixtureInterface {
    public const UUIDS = [
        'EF78C8D2-6741-4CA2-8FBD-43ACEA56787E',
        '4373581B-7D0D-436E-B760-137B33C17980',
        '3D4C1814-A3E8-4693-8B66-B778390E1062',
        'D0808CC6-5E65-4512-B751-6AAD5283D2D1',
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 1; $i <= 4; $i++) {
            $fixture = new Deposit();
            $fixture->setInstitution('Institution ' . $i);
            $fixture->setFileType('FileType ' . $i);
            $fixture->setDepositUuid(self::UUIDS[$i - 1]);
            $fixture->setReceived(new DateTimeImmutable("2020-{$i}-{$i}"));
            $fixture->setAction('Action ' . $i);
            $fixture->setChecksumType('SHA1');
            $fixture->setChecksumValue('ChecksumValue ' . $i);
            $fixture->setUrl('http://example.com/deposit/' . $i);
            $fixture->setSize((string) ($i + 10000));
            $fixture->setState('State ' . $i);
            $fixture->setErrorLog(['ErrorLog ' . $i]);
            $fixture->setErrorCount($i);
            $fixture->setPlnState('PlnState ' . $i);
            $fixture->setDepositDate(new DateTimeImmutable("2020-{$i}-{$i}"));
            $fixture->setDepositReceipt('http://example.com/receipt/' . $i);
            $fixture->setProcessingLog("<p>This is paragraph {$i}</p>");
            $fixture->setHarvestAttempts($i);
            $fixture->setProvider($this->getReference('provider.' . $i));
            $em->persist($fixture);
            $this->setReference('deposit.' . $i, $fixture);
        }
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies() {
        return [
            ProviderFixtures::class,
        ];
    }
}
