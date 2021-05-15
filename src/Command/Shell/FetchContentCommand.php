<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Shell;

use App\Entity\Deposit;
use App\Entity\Provider;
use App\Services\FilePaths;
use App\Services\SwordClient;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Fetch all the content of one or more providers from LOCKSS via LOCKSSOMatic.
 */
class FetchContentCommand extends Command {
    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var FilePaths
     */
    protected $filePaths;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SwordClient
     */
    private $swordClient;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * Initialize the command.
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, FilePaths $filePaths, SwordClient $swordClient) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->filePaths = $filePaths;
        $this->swordClient = $swordClient;
        $this->fs = new Filesystem();
    }

    /**
     * Configure the command.
     */
    public function configure() : void {
        $this->setName('pln:fetch');
        $this->setDescription('Download the archived content for one or more providers.');
        $this->addArgument('providers', InputArgument::IS_ARRAY, 'The database ID of one or more providers.');
    }

    /**
     * Set the HTTP client for contacting LOCKSSOMatic.
     */
    public function setHttpClient(Client $httpClient) : void {
        $this->httpClient = $httpClient;
    }

    /**
     * Build and configure and return an HTTP client. Uses the client set
     * from setHttpClient() if available.
     *
     * @return Client
     */
    public function getHttpClient() {
        if ( ! $this->httpClient) {
            $this->httpClient = new Client();
        }

        return $this->httpClient;
    }

    /**
     * Fetch one deposit from LOCKSSOMatic.
     *
     * @param string $href
     */
    public function fetch(Deposit $deposit, $href) : void {
        $client = $this->getHttpClient();
        $filepath = $this->filePaths->getRestoreDir($deposit->getProvider()) . '/' . basename($href);
        $this->logger->notice("Saving {$deposit->getProvider()->getName()} #{$deposit->getId()} to {$filepath}");

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
     * Download all the content from one provider.
     *
     * Requests a SWORD deposit statement from LOCKSSOMatic, and uses the
     * sword:originalDeposit element to fetch the content.
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
     * Get a list of providers to download.
     *
     * @param array $providerIds
     *
     * @return Collection|Provider[]
     */
    public function getProviders($providerIds) {
        return $this->em->getRepository(Provider::class)->findBy(['id' => $providerIds]);
    }

    /**
     * Execute the command.
     */
    public function execute(InputInterface $input, OutputInterface $output) : void {
        $providerIds = $input->getArgument('providers');
        $providers = $this->getProviders($providerIds);

        foreach ($providers as $provider) {
            $this->downloadProvider($provider);
        }
    }
}
