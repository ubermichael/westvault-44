<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Controller;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\DocumentFixtures;
use App\DataFixtures\ProviderFixtures;
use App\DataFixtures\TermOfUseFixtures;
use Nines\UserBundle\DataFixtures\UserFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;
use Symfony\Component\HttpFoundation\Response;

class DefaultTest extends ControllerBaseCase {
    protected function fixtures() : array {
        return [
            UserFixtures::class,
            ProviderFixtures::class,
            DepositFixtures::class,
            TermOfUseFixtures::class,
        ];
    }

    public function defaultUrls() : array {
        return [
            ['/'],
            ['/permission'],
            ['/fetch/' . ProviderFixtures::UUIDS[0] . '/' . DepositFixtures::UUIDS[0], Response::HTTP_NOT_FOUND],
            ['/fetch/' . ProviderFixtures::UUIDS[0] . '/' . 'abc123', Response::HTTP_NOT_FOUND],
            ['/fetch/' . ProviderFixtures::UUIDS[0] . '/' . DepositFixtures::UUIDS[1], Response::HTTP_BAD_REQUEST],
            ['/feeds/terms.json'],
            ['/feeds/terms.rss'],
            ['/feeds/terms.atom'],
        ];
    }

    /**
     * @dataProvider defaultUrls
     */
    public function testAnonIndex($url, $code = Response::HTTP_OK) : void {
        $crawler = $this->client->request('GET', $url);
        $this->assertSame($code, $this->client->getResponse()->getStatusCode());
    }
}
