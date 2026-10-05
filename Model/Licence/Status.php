<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Licence;

use Magento\Framework\FlagManager;
use UpturnStudio\GoogleFeed\Api\LicenceValidatorInterface;
use UpturnStudio\GoogleFeed\Model\Config;

/**
 * Whether the store's subscription key connects it to the reporting service.
 *
 * Nothing in this module depends on the state: feeds, click capture and the API work with or without a key. The
 * key only unlocks the extras that live in the reporting service, such as content suggestions and spend vs ROI.
 */
class Status
{
    public const STATE_MISSING = 'missing';
    public const STATE_ACTIVE = 'active';
    public const STATE_UNVERIFIED = 'unverified';
    public const STATE_INVALID = 'invalid';

    private const FLAG = 'upturnstudio_googlefeed_subscription';

    /**
     * @param Config $config
     * @param FlagManager $flagManager
     * @param LicenceValidatorInterface $validator
     */
    public function __construct(
        private readonly Config $config,
        private readonly FlagManager $flagManager,
        private readonly LicenceValidatorInterface $validator
    ) {
    }

    /**
     * Current state: missing, active, unverified or invalid.
     *
     * A key the service has not ruled on yet is "unverified".
     *
     * @return string
     */
    public function getState(): string
    {
        $key = $this->config->getSubscriptionKey();
        if ($key === '') {
            return self::STATE_MISSING;
        }
        $stored = $this->flagManager->getFlagData(self::FLAG);
        if (!is_array($stored) || ($stored['key_hash'] ?? '') !== $this->hash($key)) {
            return self::STATE_UNVERIFIED;
        }

        return (string) ($stored['state'] ?? self::STATE_UNVERIFIED);
    }

    /**
     * Ask the subscription service about the current key and remember its answer.
     *
     * When the service gives no answer, the last answer for the same key stands.
     *
     * @return string The resulting state
     */
    public function refresh(): string
    {
        $key = $this->config->getSubscriptionKey();
        if ($key === '') {
            $this->flagManager->deleteFlag(self::FLAG);

            return self::STATE_MISSING;
        }

        $state = match ($this->validator->validate($key)) {
            LicenceValidatorInterface::RESULT_VALID => self::STATE_ACTIVE,
            LicenceValidatorInterface::RESULT_INVALID => self::STATE_INVALID,
            default => $this->getState(),
        };
        $this->flagManager->saveFlag(self::FLAG, ['key_hash' => $this->hash($key), 'state' => $state]);

        return $state;
    }

    /**
     * Hash a key so the flag never stores it in clear text.
     *
     * @param string $key
     * @return string
     */
    private function hash(string $key): string
    {
        return hash('sha256', $key);
    }
}
