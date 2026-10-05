<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Licence;

use Magento\Framework\HTTP\ClientInterfaceFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use UpturnStudio\GoogleFeed\Api\LicenceValidatorInterface;
use UpturnStudio\GoogleFeed\Model\Config;

/**
 * Validates the key by POSTing {"key", "domain"} as JSON to the configured endpoint.
 *
 * The endpoint answers 200 with {"valid": true|false}; 401, 403 and 404 also mean the key is not valid. Anything
 * else, including no endpoint being configured, is reported as unknown, so an outage never disconnects a store.
 */
class HttpValidator implements LicenceValidatorInterface
{
    private const TIMEOUT_SECONDS = 10;

    /**
     * @param Config $config
     * @param ClientInterfaceFactory $clientFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly ClientInterfaceFactory $clientFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function validate(string $key): string
    {
        $url = $this->config->getValidationUrl();
        if ($url === '' || $key === '') {
            return self::RESULT_UNKNOWN;
        }

        try {
            $client = $this->clientFactory->create();
            $client->setTimeout(self::TIMEOUT_SECONDS);
            $client->addHeader('Content-Type', 'application/json');
            $client->addHeader('Accept', 'application/json');
            $client->post($url, (string) json_encode([
                'key' => $key,
                'domain' => $this->storeManager->getDefaultStoreView()?->getBaseUrl(UrlInterface::URL_TYPE_WEB),
            ]));

            $status = (int) $client->getStatus();
            if (in_array($status, [401, 403, 404], true)) {
                return self::RESULT_INVALID;
            }
            $body = json_decode((string) $client->getBody(), true);
            if ($status === 200 && is_array($body) && array_key_exists('valid', $body)) {
                return $body['valid'] ? self::RESULT_VALID : self::RESULT_INVALID;
            }
        } catch (\Throwable $e) {
            $this->logger->warning('UpturnStudio_GoogleFeed: subscription check failed - ' . $e->getMessage());
        }

        return self::RESULT_UNKNOWN;
    }
}
