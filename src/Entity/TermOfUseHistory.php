<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * TermOfUseHistory.
 *
 * A new TermOfUseHistory object is created every time a Term of Use is created,
 * updated, or deleted. The history object is created by an event listener.
 *
 * @see App\EventListener\TermsOfUseListener
 *
 * @ORM\Entity(repositoryClass="App\Repository\TermOfUseHistoryRepository")
 */
class TermOfUseHistory extends AbstractEntity {
    /**
     * A term ID, similar to the OJS translation keys.
     *
     * @var int
     * @ORM\Column(type="integer")
     */
    private $termId;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $action;

    /**
     * The user who added/edited/deleted the term of use.
     *
     * @var string
     * @ORM\Column(type="string")
     */
    private $user;

    /**
     * The change set, as computed by Doctrine.
     *
     * @var array
     *
     * @ORM\Column(type="array")
     */
    private $changeSet;

    /**
     * Build a new, empty whitelist entry.
     */
    public function __construct() {
        parent::__construct();
    }

    public function __toString() : string {
        return 'Term ' . $this->termId;
    }
}
