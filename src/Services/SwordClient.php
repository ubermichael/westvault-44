<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Deposit;
use App\Utilities\Namespaces;
use App\Utilities\ServiceDocument;
use DateTimeImmutable;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * Description of SwordClient.
 */
class SwordClient {
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

    /**
     * All providers are given the same title in LOCKSS/LOCKSSOMatic to
     * enable use of the LOCKSS subscription manager.
     *
     * @var string
     */
    private $plnProviderTitle;

    /**
     * File system utility.
     *
     * @var Filesystem
     */
    private $fs;

    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $fp;

    /**
     * Twig template engine service.
     *
     * @var Environment
     */
    private $templating;

    /**
     * Guzzle HTTP client,.
     *
     * @var Client
     */
    private $client;

    /**
     * URL for the service document.
     *
     * @var string
     */
    private $serviceUri;

    /**
     * If true, save the deposit XML at /path/to/deposit.zip.xml.
     *
     * @var bool
     */
    private $saveXml;

    /**
     * Staging server UUID.
     *
     * @var string
     */
    private $uuid;

    /**
     * Construct the sword client.
     *
     * @param string $serviceUri
     * @param string $uuid
     * @param bool $saveXml
     */
    public function __construct($serviceUri, $uuid, $saveXml, FilePaths $filePaths, Environment $templating) {
        $this->serviceUri = $serviceUri;
        $this->uuid = $uuid;
        $this->saveXml = $saveXml;
        $this->fp = $filePaths;
        $this->templating = $templating;
        $this->fs = new Filesystem();
        $this->client = new Client(self::CONF);
    }

    public function getPlnProviderTitle() : string {
        return $this->plnProviderTitle;
    }

    public function setPlnProviderTitle(string $plnProviderTitle) : void {
        $this->plnProviderTitle = $plnProviderTitle;
    }

    /**
     * Set or override the HTTP client, usually based on Guzzle.
     */
    public function setClient(Client $client) : void {
        $this->client = $client;
    }

    /**
     * Set or override  the file system client.
     */
    public function setFilesystem(Filesystem $fs) : void {
        $this->fs = $fs;
    }

    /**
     * Set or override the file path service.
     */
    public function setFilePaths(FilePaths $fp) : void {
        $this->fp = $fp;
    }

    /**
     * Set or override the service document URI.
     *
     * @param string $serviceUri
     */
    public function setServiceUri($serviceUri) : void {
        $this->serviceUri = $serviceUri;
    }

    /**
     * Set or override the UUID.
     *
     * @param string $uuid
     */
    public function setUuid($uuid) : void {
        $this->uuid = $uuid;
    }

    /**
     * Make a SWORD request.
     *
     * @param mixed $xml
     * @param ?Deposit $deposit
     *
     * @throws Exception|GuzzleException
     *
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $headers = [], $xml = null, ?Deposit $deposit = null, array $options = []) {
        try {
            $request = new Request($method, $url, $headers, $xml);

            return $this->client->send($request, $options);
        } catch (RequestException $e) {
            $message = $e->getRequest()->getBody()->getContents();
            if ($e->hasResponse()) {
                $message .= "\n\n" . $e->getResponse()->getBody()->getContents();
            }
            if ($deposit) {
                $deposit->addErrorLog($message);
            }

            throw new Exception($message);
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($deposit) {
                $deposit->addErrorLog($message);
            }

            throw new Exception($message);
        }
    }

    /**
     * Fetch the service document.
     *
     * @throws Exception
     *
     * @return ServiceDocument
     */
    public function serviceDocument() {
        $response = $this->request('GET', $this->serviceUri, [
            'On-Behalf-Of' => $this->uuid,
        ]);

        return new ServiceDocument($response->getBody()->getContents());
    }

    /**
     * Create a deposit in LOCKSSOMatic.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function createDeposit(Deposit $deposit) {
        $sd = $this->serviceDocument();
        $xml = $this->templating->render('sword/deposit.xml.twig', [
            'title' => 'Deposits from ' . $deposit->getInstitution(),
            'publisher' => 'WestVault Staging Server',
            'deposit' => $deposit,
            'plnProviderTitle' => $this->plnProviderTitle,
        ]);
        if ($this->saveXml) {
            $path = $this->fp->getStagingBagPath($deposit) . '.xml';
            $this->fs->dumpFile($path, $xml);
        }
        $response = $this->request('POST', $sd->getCollectionUri(), [], $xml, $deposit);
        $locationHeader = $response->getHeader('Location');
        if (count($locationHeader) > 0) {
            $deposit->setDepositReceipt($locationHeader[0]);
        }
        $deposit->setDepositDate(new DateTimeImmutable());

        return true;
    }

    /**
     * Fetch the deposit receipt for $deposit.
     *
     * @throws Exception
     *
     * @return SimpleXMLElement|void
     */
    public function receipt(Deposit $deposit) {
        if ( ! $deposit->getDepositReceipt()) {
            return;
        }
        $response = $this->request('GET', $deposit->getDepositReceipt(), [], null, $deposit);
        $xml = new SimpleXMLElement($response->getBody()->getContents());
        Namespaces::registerNamespaces($xml);

        return $xml;
    }

    /**
     * Fetch the sword statement for $deposit.
     *
     * @throws Exception
     *
     * @return SimpleXMLElement
     */
    public function statement(Deposit $deposit) {
        $receiptXml = $this->receipt($deposit);
        $statementUrl = (string) $receiptXml->xpath('atom:link[@rel="http://purl.org/net/sword/terms/statement"]/@href')[0];
        $response = $this->request('GET', $statementUrl, [], null, $deposit);
        $statementXml = new SimpleXMLElement($response->getBody()->getContents());
        Namespaces::registerNamespaces($statementXml);

        return $statementXml;
    }

    /**
     * Fetch the deposit back from LOCKSSOmatic.
     * Saves the file to disk and returns the full path to the file.
     *
     * @throws Exception
     *
     * @return string
     */
    public function fetch(Deposit $deposit) {
        $statement = $this->statement($deposit);
        $original = (string) $statement->xpath('//sword:originalDeposit/@href')[0];
        $filepath = $this->fp->getRestoreFile($deposit);

        $this->request('GET', $original, [], null, $deposit, [
            'allow_redirects' => false,
            'decode_content' => false,
            'save_to' => $filepath,
        ]);

        return $filepath;
    }
}
