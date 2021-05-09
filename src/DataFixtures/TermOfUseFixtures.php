<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\TermOfUse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * LoadTermOfUse form.
 */
class TermOfUseFixtures extends Fixture {
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 0; $i < 4; $i++) {
            $fixture = new TermOfUse();
            $fixture->setWeight(4 - $i);
            $fixture->setKeyCode('term-' . $i);
            $fixture->setContent('Content ' . $i);

            $em->persist($fixture);
            $this->setReference('termofuse.' . $i, $fixture);
        }

        $em->flush();
    }
}
