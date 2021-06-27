<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Deposit;
use App\Entity\Provider;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Description of SwordClient.
 */
class Fetcher {
    /**
     * Configuration for the http client.
     */
    public const CONF = [
        'allow_redirects' => false,
        'headers' => [
            'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
        ],
        'decode_content' => false,
    ];

    private FilePaths $filePaths;

    private SwordClient $swordClient;

    private Filesystem $fs;

    private Client $client;

    private LoggerInterface $logger;

    public function __construct() {
        $this->fs = new Filesystem();
        $this->client = new Client(self::CONF);
    }

    /**
     * Download all the content from one provider.
     *
     * @throws Exception
     */
    public function downloadProvider(Provider $provider) : void {
        foreach ($provider->getDeposits() as $deposit) {
            $statement = $this->swordClient->statement($deposit);
            $originals = $statement->xpath('//sword:originalDeposit');

            foreach ($originals as $element) {
                $this->fetch($deposit, $element['href']);
            }
        }
    }

    /**
     * Fetch one deposit from LOCKSSOMatic.
     *
     * @param string $href
     */
    public function fetch(Deposit $deposit, $href) : void {
        $client = $this->getClient();
        $filepath = $this->filePaths->getRestoreDir($deposit->getProvider()) . '/' . basename($href);
        $this->logger->notice("Fetching {$deposit->getProvider()->getName()} #{$deposit->getId()} to {$filepath}");

        try {
            $client->get($href, [
                'allow_redirects' => false,
                'decode_content' => false,
                'save_to' => $filepath,
            ]);
            $hash = mb_strtoupper(hash_file($deposit->getChecksumType(), $filepath));
            if ($hash !== $deposit->getChecksumValue()) {
                $this->logger->warning("Package checksum failed. Expected {$deposit->getChecksumType()}:{$deposit->getChecksumValue()} but got {$hash}");
            }
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
        }
    }

    /**
     * @required
     */
    public function setLogger(LoggerInterface $logger) : void {
        $this->logger = $logger;
    }

    /**
     * @required
     */
    public function setFilePaths(FilePaths $filePaths) : void {
        $this->filePaths = $filePaths;
    }

    /**
     * @required
     */
    public function setSwordClient(SwordClient $swordClient) : void {
        $this->swordClient = $swordClient;
    }

    public function getClient() : Client {
        return $this->client;
    }

    public function setClient(Client $client) : void {
        $this->client = $client;
    }
}
