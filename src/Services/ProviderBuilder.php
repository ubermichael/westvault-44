<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Provider;
use App\Utilities\Xpath;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;

/**
 * Provider builder service.
 */
class ProviderBuilder {
    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Construct the builder.
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * Build and persist a provider from XML.
     *
     * Does not flush the provider to the database.
     *
     * @param string $uuid
     *
     * @return Provider
     */
    public function fromXml(SimpleXMLElement $xml, $uuid) {
        $provider = $this->em->getRepository(Provider::class)->findOneBy([
            'uuid' => mb_strtoupper($uuid),
        ]);
        if (null === $provider) {
            $provider = new Provider();
        }
        $provider->setUuid($uuid);
        $provider->setName(Xpath::getXmlValue($xml, '//atom:title'));
        $provider->setEmail(Xpath::getXmlValue($xml, '//atom:email'));
        $this->em->persist($provider);

        return $provider;
    }

    /**
     * The provider with UUID $uuid has contacted the PLN.
     *
     * @param string $uuid
     * @param string $name
     *
     * @return Provider
     */
    public function fromRequest($uuid, $name) {
        $provider = $this->em->getRepository('App:Provider')->findOneBy([
            'uuid' => mb_strtoupper($uuid),
        ]);
        if (null === $provider) {
            $provider = new Provider();
            $provider->setUuid($uuid);
            $provider->setName($name);
            $provider->setEmail('unknown@unknown.com');
            $this->em->persist($provider);
        }

        return $provider;
    }
}
