<?php

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
use Symfony\Component\BrowserKit\Client;

abstract class AbstractSwordTestCase extends ControllerBaseCase {

    public function fixtures() : array {
        return array(
            ProviderFixtures::class,
            DepositFixtures::class,
            TermOfUseFixtures::class,
            WhitelistFixtures::class,
            BlacklistFixtures::class,
        );
    }

	/**
	 * @return SimpleXMLElement
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

    /**
     * Get a single XML value as a string.
     *
     * @param SimpleXMLElement $xml
     * @param string $xpath
     * @return string
     * @throws Exception
     */
    public function getXmlValue(SimpleXMLElement $xml, $xpath) : ?string {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return trim((string) $data[0]);
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }
}
