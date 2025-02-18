<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Processing;

use App\Entity\Deposit;
use App\Services\SwordClient;

/**
 * Send a fully processed deposit to LOCKSSOMatic.
 *
 * @see SwordClient
 */
class Depositor extends AbstractProcessingService {
    /**
     * Sword client to talk to LOCKSSOMatic.
     *
     * @var SwordClient
     */
    private $client;

    /**
     * Build the service.
     *
     * @param string $heldVersions
     */
    public function __construct(SwordClient $client) {
        $this->client = $client;
    }

    /**
     * Process one deposit.
     *
     * @return null|bool|string
     */
    public function processDeposit(Deposit $deposit) {
        return $this->client->createDeposit($deposit);
    }

    public function setClient(SwordClient $client) : void {
        $this->client = $client;
    }
}
