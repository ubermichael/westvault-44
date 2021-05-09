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
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"title", "summary", "content"}, flags={"fulltext"}),
 *     @ORM\Index(columns={"path"})
 * })
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

    public function getTitle() : ?string {
        return $this->title;
    }

    public function setTitle(string $title) : self {
        $this->title = $title;

        return $this;
    }

    public function getPath() : ?string {
        return $this->path;
    }

    public function setPath(string $path) : self {
        $this->path = $path;

        return $this;
    }

    public function getSummary() : ?string {
        return $this->summary;
    }

    public function setSummary(string $summary) : self {
        $this->summary = $summary;

        return $this;
    }

    public function getContent() : ?string {
        return $this->content;
    }

    public function setContent(string $content) : self {
        $this->content = $content;

        return $this;
    }
}
