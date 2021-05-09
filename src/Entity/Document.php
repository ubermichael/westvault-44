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
 * Help Document.
 *
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 */
class Document extends AbstractEntity {
    /**
     * Document title.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $title;

    /**
     * The URL slug for the document.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $path;

    /**
     * A brief summary to display on the list of documents.
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $summary;

    /**
     * The content.
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $content;

    public function __construct() {
        parent::__construct();
    }

    public function __toString() : string {
        return $this->title;
    }
}
