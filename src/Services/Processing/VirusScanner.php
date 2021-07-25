<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Processing;

use App\Entity\Deposit;
use App\Services\FilePaths;
use Socket\Raw\Factory;
use Symfony\Component\Filesystem\Filesystem;
use Xenolope\Quahog\Client;

/**
 * Virus scanning service, via ClamAV.
 */
class VirusScanner extends AbstractProcessingService {
    /**
     * Buffer size for extracting embedded files.
     */
    public const BUFFER_SIZE = 64 * 1024;

    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $filePaths;

    /**
     * Path to the ClamAV socket.
     *
     * @var string
     */
    private $socketPath;

    /**
     * Socket factory, for use with the Quahog ClamAV interface.
     *
     * @var Factory
     */
    private $factory;

    /**
     * Filesystem client.
     *
     * @var Filesystem
     */
    private $fs;

    /**
     * Construct the virus scanner.
     *
     * @param string $socketPath
     */
    public function __construct($socketPath, FilePaths $filePaths) {
        $this->filePaths = $filePaths;
        $this->socketPath = $socketPath;
        $this->bufferSize = self::BUFFER_SIZE;
        $this->factory = new Factory();
        $this->fs = new Filesystem();
    }

    /**
     * Set the socket factory.
     */
    public function setFactory(Factory $factory) : void {
        $this->factory = $factory;
    }

    /**
     * Get the Quahog client.
     *
     * The client can't be instantiated in the constructor. If the socket path
     * isn't configured or if the socket isn't set up yet the entire app will
     * fail. Symfony tries it instantiate all services for each request, and if
     * one constructor throws an exception everything gets cranky.
     *
     * @return Client
     */
    public function getClient() {
        $socket = $this->factory->createClient('unix://' . $this->socketPath);
        $client = new Client($socket, 30, PHP_NORMAL_READ);
        $client->startSession();

        return $client;
    }

    /**
     * Process one deposit.
     *
     * @param Client $client
     *
     * @return bool
     */
    public function processDeposit(Deposit $deposit, ?Client $client = null) {
        if (null === $client) {
            $client = $this->getClient();
        }
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        $basename = basename($harvestedPath);

        $r = $client->scanFile($harvestedPath);
        if ('OK' === $r['status']) {
            $deposit->addToProcessingLog("Clam Scan Result {$basename} OK");

            return true;
        }
        $deposit->addToProcessingLog("Clam Scan Result {$basename} NOT OK {$r['status']}: {$r['reason']}");

        return false;
    }
}
