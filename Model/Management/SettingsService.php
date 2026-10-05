<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Management;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use UpturnStudio\GoogleFeed\Model\Config;

/**
 * Reads and changes the module's click capture and generation settings.
 *
 * The subscription key and the AI connector write permission are deliberately not here: the first belongs to the
 * merchant, and the second must never be switchable by the connector it guards.
 */
class SettingsService
{
    /**
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $writer
     * @param ReinitableConfigInterface $reinitableConfig
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly WriterInterface $writer,
        private readonly ReinitableConfigInterface $reinitableConfig,
        private readonly TypeListInterface $cacheTypeList,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Current settings, as they apply to a store view when one is given.
     *
     * @param int|null $storeId
     * @return array
     */
    public function get(?int $storeId = null): array
    {
        return [
            'tracking_enabled' => $this->config->isTrackingEnabled($storeId),
            'cookie_lifetime_days' => $this->config->getCookieLifetimeDays($storeId),
            'respect_cookie_restriction' => $this->config->isRespectCookieRestriction($storeId),
            'batch_size' => $this->config->getBatchSize(),
        ];
    }

    /**
     * Change settings. Without a store ID they change the default; with one they change just that store view.
     *
     * @param array $changes
     * @param int|null $storeId
     * @return array The settings after the change
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function update(array $changes, ?int $storeId = null): array
    {
        $errors = [];
        $writes = [];
        if ($storeId !== null) {
            $this->storeManager->getStore($storeId);
        }

        if (array_key_exists('tracking_enabled', $changes)) {
            $writes[Config::XML_PATH_TRACKING_ENABLED] = $this->toBool($changes['tracking_enabled']) ? '1' : '0';
        }
        if (array_key_exists('respect_cookie_restriction', $changes)) {
            $writes[Config::XML_PATH_RESPECT_RESTRICTION] =
                $this->toBool($changes['respect_cookie_restriction']) ? '1' : '0';
        }
        if (array_key_exists('cookie_lifetime_days', $changes)) {
            $days = (int) $changes['cookie_lifetime_days'];
            if ($days < 1 || $days > 3650) {
                $errors[] = 'cookie_lifetime_days must be between 1 and 3650.';
            }
            $writes[Config::XML_PATH_COOKIE_LIFETIME] = (string) $days;
        }
        if (array_key_exists('batch_size', $changes)) {
            $size = (int) $changes['batch_size'];
            if ($storeId !== null) {
                $errors[] = 'batch_size is a global setting; leave the store view out to change it.';
            }
            if ($size < 10 || $size > 5000) {
                $errors[] = 'batch_size must be between 10 and 5000.';
            }
            $writes[Config::XML_PATH_BATCH_SIZE] = (string) $size;
        }
        if (!$writes) {
            $errors[] = 'No settings were supplied.';
        }
        if ($errors) {
            $exception = new InputException(new Phrase('The settings are not valid.'));
            foreach ($errors as $error) {
                $exception->addError(new Phrase('%1', [$error]));
            }
            throw $exception;
        }

        $scope = $storeId === null ? ScopeConfigInterface::SCOPE_TYPE_DEFAULT : ScopeInterface::SCOPE_STORES;
        foreach ($writes as $path => $value) {
            $this->writer->save($path, $value, $scope, $storeId ?? 0);
        }
        $this->cacheTypeList->cleanType('config');
        $this->reinitableConfig->reinit();

        return $this->get($storeId);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
