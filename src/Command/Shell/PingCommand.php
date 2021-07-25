<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Shell;

use App\Services\SwordClient;
use Exception;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Fetch all the content of one or more providers from LOCKSS via LOCKSSOMatic.
 */
class PingCommand extends Command {
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
    public function __construct(SwordClient $swordClient) {
        parent::__construct();
        $this->swordClient = $swordClient;
    }

    /**
     * Configure the command.
     */
    public function configure() : void {
        $this->setName('pln:ping');
        $this->setDescription('Ping the LOCKSSOMatic server by fetching a service document.');
    }

    /**
     * Execute the command.
     */
    public function execute(InputInterface $input, OutputInterface $output) : int {
        try {
            $sd = $this->swordClient->serviceDocument();
            $output->writeln($sd->getXpathValue('//app:workspace/atom:title'));
            $output->writeln('URI: ' . $sd->getCollectionUri());
            $output->writeln('Max Upload: ' . $sd->getMaxUpload());
            $output->writeln('Checksum Type: ' . $sd->getUploadChecksum());
        } catch (Exception $e) {
            $output->writeln("Error: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }
}
