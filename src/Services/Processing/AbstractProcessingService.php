<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Processing;

use App\Entity\Deposit;
use Psr\Log\LoggerInterface;

abstract class AbstractProcessingService {
    protected LoggerInterface $logger;

    /**
     * @return null|bool|string
     */
    abstract public function processDeposit(Deposit $deposit);

    public function setLogger(LoggerInterface $processingLogger) : void {
        $this->logger = $processingLogger;
    }
}
