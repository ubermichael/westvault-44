<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller\SwordController;

use App\DataFixtures\BlacklistFixtures;
use App\DataFixtures\DepositFixtures;
use App\DataFixtures\ProviderFixtures;
use App\DataFixtures\TermOfUseFixtures;
use App\DataFixtures\WhitelistFixtures;
use App\Utilities\Namespaces;
use Exception;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use SimpleXMLElement;

abstract class AbstractSwordTestCase extends ControllerBaseCase {
    /**
     * @throws Exception
     */
    protected function getXml() : SimpleXMLElement {
        try {
            $xml = new SimpleXMLElement($this->client->getResponse()->getContent());
        } catch (Exception $e) {
            fwrite(STDERR, $e->getMessage() . "\n");
            fwrite(STDERR, $this->client->getResponse()->getContent());

            throw $e;
        }
        Namespaces::registerNamespaces($xml);

        return $xml;
    }

    public function fixtures() : array {
        return [
            ProviderFixtures::class,
            DepositFixtures::class,
            TermOfUseFixtures::class,
            WhitelistFixtures::class,
            BlacklistFixtures::class,
        ];
    }

    /**
     * Get a single XML value as a string.
     *
     * @param string $xpath
     *
     * @throws Exception
     *
     * @return string
     */
    public function getXmlValue(SimpleXMLElement $xml, $xpath) : ?string {
        $data = $xml->xpath($xpath);
        if (1 === count($data)) {
            return trim((string) $data[0]);
        }
        if (0 === count($data)) {
            return null;
        }

        throw new Exception("Too many elements for '{$xpath}'");
    }
}
