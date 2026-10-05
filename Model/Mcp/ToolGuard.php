<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Mcp;

use Magento\Framework\Exception\LocalizedException;
use UpturnStudio\GoogleFeed\Model\Config;
use UpturnStudio\GoogleFeed\Model\Management\ExceptionMessages;
use UpturnStudio\Mcp\Api\ToolExecutionException;
use UpturnStudio\Mcp\Model\Oauth\AdminAclChecker;

/**
 * Runs an MCP tool call only when it is allowed, and turns failures into tool errors.
 *
 * The connector itself does not check what an admin may do per tool, so every tool does it here: the admin's own
 * role must include the tool's API permission, and tools that change data must have been switched on by an admin.
 */
class ToolGuard
{
    /**
     * @param AdminAclChecker $aclChecker
     * @param Config $config
     */
    public function __construct(
        private readonly AdminAclChecker $aclChecker,
        private readonly Config $config
    ) {
    }

    /**
     * Run the tool body.
     *
     * @param int $adminUserId
     * @param string $aclResource
     * @param bool $changesData
     * @param callable $run
     * @return array
     * @throws ToolExecutionException
     */
    public function run(int $adminUserId, string $aclResource, bool $changesData, callable $run): array
    {
        if ($changesData && !$this->config->isMcpWriteAllowed()) {
            throw new ToolExecutionException(
                'Tools that change Google Feeds data are switched off. A store admin can turn them on under '
                . 'Stores > Configuration > UpturnStudio > Google Feeds > AI Connector (MCP).'
            );
        }
        if (!$this->aclChecker->isAllowed($adminUserId, $aclResource)) {
            throw new ToolExecutionException(
                'Your admin role does not include the permission this tool needs (' . $aclResource . ').'
            );
        }

        try {
            return $run();
        } catch (ToolExecutionException $e) {
            throw $e;
        } catch (LocalizedException $e) {
            throw new ToolExecutionException(ExceptionMessages::collect($e), 0, $e);
        }
    }
}
