<?php

declare(strict_types=1);

/*
 * (c) 2021 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\Entity\Deposit;
use App\Entity\Provider;
use App\Services\FilePaths;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class FilePathsTest extends TestCase {
    /**
     * @dataProvider rootDirData
     *
     * @param mixed $expected
     * @param mixed $rootDir
     * @param mixed $projectDir
     */
    public function testRootDir($expected, $rootDir, $projectDir) : void {
        $fp = new FilePaths($rootDir, $projectDir);
        $this->assertSame($expected, $fp->getRootPath());
    }

    public function rootDirData() {
        return [
            ['', '', ''],
            ['/path/to/data', '/path/to/data', '/path/to/project'],
            ['/path/to/project/data', 'data', '/path/to/project'],
        ];
    }

    public function testGetRestoreDir() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $this->assertSame('/data/restore/ABC123', $fp->getRestoreDir($provider));
    }

    public function testGetRestoreDirNew() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(false);
        $mock->method('mkdir')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $this->assertSame('/data/restore/ABC123', $fp->getRestoreDir($provider));
    }

    public function testGetRestoreFile() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $deposit = new Deposit();
        $deposit->setProvider($provider);
        $deposit->setDepositUuid('def456');
        $this->assertSame('/data/restore/ABC123/DEF456', $fp->getRestoreFile($deposit));
    }

    public function testGetHarvestDir() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $this->assertSame('/data/harvest/ABC123', $fp->getHarvestDir($provider));
    }

    public function testGetHarvestDirNew() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(false);
        $mock->method('mkdir')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $this->assertSame('/data/harvest/ABC123', $fp->getHarvestDir($provider));
    }

    public function testGetHarvestFile() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $deposit = new Deposit();
        $deposit->setProvider($provider);
        $deposit->setDepositUuid('def456');
        $this->assertSame('/data/harvest/ABC123/DEF456', $fp->getHarvestFile($deposit));
    }

    public function testGetProcessingDir() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $this->assertSame('/data/processing/ABC123', $fp->getProcessingDir($provider));
    }

    public function testGetProcessingDirNew() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(false);
        $mock->method('mkdir')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $this->assertSame('/data/processing/ABC123', $fp->getProcessingDir($provider));
    }

    public function testGetProcessingBagPath() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $deposit = new Deposit();
        $deposit->setProvider($provider);
        $deposit->setDepositUuid('def456');
        $this->assertSame('/data/processing/ABC123/DEF456', $fp->getProcessingBagPath($deposit));
    }

    public function testGetProcessingBagPathNew() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(false);
        $mock->method('mkdir')->willReturn(false);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $deposit = new Deposit();
        $deposit->setProvider($provider);
        $deposit->setDepositUuid('def456');
        $this->assertSame('/data/processing/ABC123/DEF456', $fp->getProcessingBagPath($deposit));
    }

    public function testGetStagingDir() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $this->assertSame('/data/staged/ABC123', $fp->getStagingDir($provider));
    }

    public function testGetStagingDirNew() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(false);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $this->assertSame('/data/staged/ABC123', $fp->getStagingDir($provider));
    }

    public function testGetStagingBagPath() : void {
        $mock = $this->createMock(Filesystem::class);
        $mock->method('exists')->willReturn(true);
        $fp = new FilePaths('/data', '/path/', $mock);
        $provider = new Provider();
        $provider->setUuid('abc123');
        $deposit = new Deposit();
        $deposit->setProvider($provider);
        $deposit->setDepositUuid('def456');
        $this->assertSame('/data/staged/ABC123/DEF456', $fp->getStagingBagPath($deposit));
    }

    public function testGetOnixPath() : void {
        $fp = new FilePaths('/data', '/path/');
        $this->assertSame('/data/onix.xml', $fp->getOnixPath());
    }
}
