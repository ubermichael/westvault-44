<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\TermOfUseHistory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TermOfUseHistoryFixtures extends Fixture {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 1; $i <= 4; $i++) {
            $fixture = new TermOfUseHistory();
            $fixture->setTermId($i);
            $fixture->setAction('Action ' . $i);
            $fixture->setUser('User ' . $i);
            $fixture->setChangeSet(['ChangeSet ' . $i]);
            $em->persist($fixture);
            $this->setReference('termofusehistory.' . $i, $fixture);
        }
        $em->flush();
    }
}
