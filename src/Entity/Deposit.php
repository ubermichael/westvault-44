<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A single Deposit from a provider.
 *
 * @ORM\Entity(repositoryClass="App\Repository\DepositRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(columns={"deposit_uuid", "url"}, flags={"fulltext"}),
 *     @ORM\Index(columns={"deposit_uuid"})
 * })
 */
class Deposit extends AbstractEntity {
    /**
     * The provider that sent this deposit.
     *
     * @var Provider
     *
     * @ORM\ManyToOne(targetEntity="Provider", inversedBy="deposits")
     * @ORM\JoinColumn(nullable=false)
     */
    private $provider;

    /**
     * Name of the institution making the deposit.
     *
     * @var string
     * @ORM\Column(type="string", length=64, nullable=false);
     */
    private $institution;

    /**
     * Bagit doesn't understand compressed files that don't have a file
     * extension. So set the file type, and build file names from that.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false);
     */
    private $fileType;

    /**
     * Deposit UUID, as generated by the PLN plugin.
     *
     * @var string
     *
     * @Assert\Uuid
     * @ORM\Column(type="string", length=36, nullable=false, unique=true)
     */
    private $depositUuid;

    /**
     * When the deposit was received.
     *
     * @var string
     *
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $received;

    /**
     * The deposit action (add, edit).
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $action;

    /**
     * The checksum type for the deposit (SHA1, MD5).
     *
     * @var string
     * @ORM\Column(type="string")
     */
    private $checksumType;

    /**
     * The checksum value, in hex.
     *
     * @var string
     * @Assert\Regex("/^[0-9a-f]+$/");
     * @ORM\Column(type="string")
     */
    private $checksumValue;

    /**
     * The source URL for the deposit. This may be a very large string.
     *
     * @var string
     *
     * @Assert\Url
     * @ORM\Column(type="string", length=2048)
     */
    private $url;

    /**
     * Size of the deposit, in bytes.
     *
     * @var string
     *
     * @ORM\Column(type="bigint")
     */
    private $size;

    /**
     * Current processing state.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $state;

    /**
     * List of errors that occured while processing.
     *
     * @var array
     * @ORM\Column(type="array", nullable=false)
     */
    private $errorLog;

    /**
     * The number of errors that occured during processing.
     *
     * @todo can this be gotten from count($this->errorLog)?
     *
     * @var int
     * @ORM\Column(type="integer", nullable=false)
     */
    private $errorCount;

    /**
     * Stae of the deposit in LOCKSSOMatic or the PLN.
     *
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    private $plnState;

    /**
     * Date the deposit was sent to LOCKSSOmatic or the PLN.
     *
     * @var date
     * @ORM\Column(type="date", nullable=true)
     */
    private $depositDate;

    /**
     * URL for the deposit receipt.
     *
     * @var string
     * @Assert\Url
     * @ORM\Column(type="string", length=2048)
     */
    private $depositReceipt;

    /**
     * Processing log for this deposit.
     *
     * @var string
     * @ORM\Column(type="text")
     */
    private $processingLog;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $harvestAttempts;

    /**
     * Construct an empty deposit.
     */
    public function __construct() {
        parent::__construct();
        $this->received = new DateTimeImmutable();
        $this->processingLog = '';
        $this->state = 'depositedByProvider';
        $this->errorLog = [];
        $this->errorCount = 0;
        $this->harvestAttempts = 0;
    }

    public function __toString() : string {
        return $this->depositUuid;
    }

    public function getInstitution() : ?string {
        return $this->institution;
    }

    public function setInstitution(string $institution) : self {
        $this->institution = $institution;

        return $this;
    }

    public function getFileType() : ?string {
        return $this->fileType;
    }

    public function setFileType(string $fileType) : self {
        $this->fileType = $fileType;

        return $this;
    }

    public function getDepositUuid() : ?string {
        return $this->depositUuid;
    }

    public function setDepositUuid(string $depositUuid) : self {
        $this->depositUuid = $depositUuid;

        return $this;
    }

    public function getReceived() : ?DateTimeInterface {
        return $this->received;
    }

    public function setReceived(DateTimeInterface $received) : self {
        $this->received = $received;

        return $this;
    }

    public function getAction() : ?string {
        return $this->action;
    }

    public function setAction(string $action) : self {
        $this->action = $action;

        return $this;
    }

    public function getChecksumType() : ?string {
        return $this->checksumType;
    }

    public function setChecksumType(string $checksumType) : self {
        $this->checksumType = $checksumType;

        return $this;
    }

    public function getChecksumValue() : ?string {
        return $this->checksumValue;
    }

    public function setChecksumValue(string $checksumValue) : self {
        $this->checksumValue = $checksumValue;

        return $this;
    }

    public function getUrl() : ?string {
        return $this->url;
    }

    public function setUrl(string $url) : self {
        $this->url = $url;

        return $this;
    }

    public function getSize() : ?string {
        return $this->size;
    }

    public function setSize(string $size) : self {
        $this->size = $size;

        return $this;
    }

    public function getState() : ?string {
        return $this->state;
    }

    public function setState(string $state) : self {
        $this->state = $state;

        return $this;
    }

    public function getErrorLog() : ?array {
        return $this->errorLog;
    }

    public function setErrorLog(array $errorLog) : self {
        $this->errorLog = $errorLog;

        return $this;
    }

    public function getErrorCount() : ?int {
        return $this->errorCount;
    }

    public function setErrorCount(int $errorCount) : self {
        $this->errorCount = $errorCount;

        return $this;
    }

    public function getPlnState() : ?string {
        return $this->plnState;
    }

    public function setPlnState(?string $plnState) : self {
        $this->plnState = $plnState;

        return $this;
    }

    public function getDepositDate() : ?DateTimeInterface {
        return $this->depositDate;
    }

    public function setDepositDate(?DateTimeInterface $depositDate) : self {
        $this->depositDate = $depositDate;

        return $this;
    }

    public function getDepositReceipt() : ?string {
        return $this->depositReceipt;
    }

    public function setDepositReceipt(string $depositReceipt) : self {
        $this->depositReceipt = $depositReceipt;

        return $this;
    }

    public function getProcessingLog() : ?string {
        return $this->processingLog;
    }

    public function setProcessingLog(string $processingLog) : self {
        $this->processingLog = $processingLog;

        return $this;
    }

    public function getHarvestAttempts() : ?int {
        return $this->harvestAttempts;
    }

    public function setHarvestAttempts(int $harvestAttempts) : self {
        $this->harvestAttempts = $harvestAttempts;

        return $this;
    }

    public function getProvider() : ?Provider {
        return $this->provider;
    }

    public function setProvider(?Provider $provider) : self {
        $this->provider = $provider;

        return $this;
    }
}
