<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Test\Unit\Model\Mcp;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use UpturnStudio\GoogleFeed\Model\Config;
use UpturnStudio\GoogleFeed\Model\Mcp\ToolGuard;
use UpturnStudio\Mcp\Api\ToolExecutionException;
use UpturnStudio\Mcp\Model\Oauth\AdminAclChecker;

/**
 * @covers \UpturnStudio\GoogleFeed\Model\Mcp\ToolGuard
 */
class ToolGuardTest extends TestCase
{
    private const RESOURCE = 'UpturnStudio_GoogleFeed::api_feeds_manage';

    /** @var AdminAclChecker&MockObject */
    private AdminAclChecker $aclChecker;

    /** @var Config&MockObject */
    private Config $config;

    private ToolGuard $guard;

    protected function setUp(): void
    {
        $this->aclChecker = $this->createMock(AdminAclChecker::class);
        $this->config = $this->createMock(Config::class);
        $this->guard = new ToolGuard($this->aclChecker, $this->config);
    }

    public function testToolsThatChangeDataAreRefusedUntilAnAdminSwitchesThemOn(): void
    {
        $this->config->method('isMcpWriteAllowed')->willReturn(false);
        // Not even asked: the switch comes first.
        $this->aclChecker->expects($this->never())->method('isAllowed');

        $this->expectException(ToolExecutionException::class);
        $this->expectExceptionMessage('switched off');

        $this->guard->run(1, self::RESOURCE, true, static fn (): array => ['ran' => true]);
    }

    public function testReadToolsDoNotNeedTheSwitch(): void
    {
        $this->config->method('isMcpWriteAllowed')->willReturn(false);
        $this->aclChecker->method('isAllowed')->with(1, self::RESOURCE)->willReturn(true);

        $this->assertSame(['ran' => true], $this->guard->run(1, self::RESOURCE, false, static fn (): array => ['ran' => true]));
    }

    public function testTheAdminsOwnRoleMustHoldThePermission(): void
    {
        $this->config->method('isMcpWriteAllowed')->willReturn(true);
        $this->aclChecker->method('isAllowed')->with(7, self::RESOURCE)->willReturn(false);

        $ran = false;
        try {
            $this->guard->run(7, self::RESOURCE, true, function () use (&$ran): array {
                $ran = true;

                return [];
            });
            $this->fail('The call should have been refused.');
        } catch (ToolExecutionException $e) {
            $this->assertStringContainsString(self::RESOURCE, $e->getMessage());
        }
        $this->assertFalse($ran, 'The tool body must not run without permission.');
    }

    public function testChangingToolsRunWhenSwitchedOnAndPermitted(): void
    {
        $this->config->method('isMcpWriteAllowed')->willReturn(true);
        $this->aclChecker->method('isAllowed')->willReturn(true);

        $this->assertSame(['ok' => 1], $this->guard->run(1, self::RESOURCE, true, static fn (): array => ['ok' => 1]));
    }

    public function testMagentoExceptionsBecomeToolErrors(): void
    {
        $this->config->method('isMcpWriteAllowed')->willReturn(true);
        $this->aclChecker->method('isAllowed')->willReturn(true);

        $this->expectException(ToolExecutionException::class);
        $this->expectExceptionMessage('The feed with ID "9" does not exist.');

        $this->guard->run(1, self::RESOURCE, true, static function (): array {
            throw new NoSuchEntityException(new Phrase('The feed with ID "9" does not exist.'));
        });
    }
}
