<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Document;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DocumentFixtures extends Fixture {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 1; $i <= 4; $i++) {
            $fixture = new Document();
            $fixture->setTitle('Title ' . $i);
            $fixture->setPath('Path ' . $i);
            $fixture->setSummary("<p>This is paragraph {$i}</p>");
            $fixture->setContent("<p>This is paragraph {$i}</p>");
            $em->persist($fixture);
            $this->setReference('document.' . $i, $fixture);
        }
        $em->flush();
    }
}
