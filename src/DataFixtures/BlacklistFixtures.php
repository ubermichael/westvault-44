<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Blacklist;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BlacklistFixtures extends Fixture {
    public const UUIDS = [
        'A40E48EE-F66A-46D2-9DF2-8B0F558CC8DF',
        'DF9D5CED-8529-4804-8330-8E938479FD3B',
        '263EBF77-C381-4F21-ACCD-16AEB8A5BF51',
        '9106C47F-88E7-4404-988B-303D96AF6DDE',
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 1; $i <= 4; $i++) {
            $fixture = new Blacklist();
            $fixture->setUuid(self::UUIDS[$i - 1]);
            $fixture->setComment("<p>This is paragraph {$i}</p>");
            $em->persist($fixture);
            $this->setReference('blacklist.' . $i, $fixture);
        }
        $em->flush();
    }
}
