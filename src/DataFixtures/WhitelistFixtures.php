<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Whitelist;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WhitelistFixtures extends Fixture {
    public const UUIDS = [
        '67E2121A-525D-4666-A29A-E4CD1BBFE35A',
        '82DDF000-0F71-4229-9DC6-2199D70E8B52',
        'FCD434BA-6728-4703-9EAF-627167C492B4',
        'ADC5F3EC-431D-42D0-8E98-0E2BFEFF1414',
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 1; $i <= 4; $i++) {
            $fixture = new Whitelist();
            $fixture->setUuid(self::UUIDS[$i - 1]);
            $fixture->setComment("<p>This is paragraph {$i}</p>");
            $em->persist($fixture);
            $this->setReference('whitelist.' . $i, $fixture);
        }
        $em->flush();
    }
}
