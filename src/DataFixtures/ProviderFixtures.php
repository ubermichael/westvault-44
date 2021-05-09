<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Provider;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProviderFixtures extends Fixture {
    public const UUIDS = [
        'CF566EC4-0469-42B4-9C0C-8EC3154DDE87',
        '3CE7D6DE-537B-4ED9-8D39-A7F97A62C93E',
        'E03367D5-CD86-4842-B00A-DBF21A99F409',
        '9106E456-CD9F-4640-BEF3-C5B9B8651C15',
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 1; $i <= 4; $i++) {
            $fixture = new Provider();
            $fixture->setUuid(self::UUIDS[$i - 1]);
            $fixture->setName('Name ' . $i);
            $fixture->setEmail('provider_' . $i . '@example.com');

            $em->persist($fixture);
            $this->setReference('provider.' . $i, $fixture);
        }
        $em->flush();
    }
}
