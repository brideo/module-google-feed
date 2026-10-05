<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Math\Random;
use UpturnStudio\GoogleFeed\Model\ResourceModel\Feed as FeedResource;
use UpturnStudio\GoogleFeed\Model\ResourceModel\Feed\CollectionFactory;

/**
 * Loads and persists feed definitions.
 */
class FeedRepository
{
    private const TOKEN_LENGTH = 32;

    /**
     * @param FeedFactory $feedFactory
     * @param FeedResource $resource
     * @param CollectionFactory $collectionFactory
     * @param Random $random
     * @param Filesystem $filesystem
     */
    public function __construct(
        private readonly FeedFactory $feedFactory,
        private readonly FeedResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly Random $random,
        private readonly Filesystem $filesystem
    ) {
    }

    /**
     * Load a feed by ID.
     *
     * @param int $feedId
     * @return Feed
     * @throws NoSuchEntityException
     */
    public function getById(int $feedId): Feed
    {
        $feed = $this->feedFactory->create();
        $this->resource->load($feed, $feedId);
        if (!$feed->getId()) {
            throw new NoSuchEntityException(__('The feed with ID "%1" does not exist.', $feedId));
        }

        return $feed;
    }

    /**
     * Save a feed, assigning its file token on first save.
     *
     * @param Feed $feed
     * @return Feed
     */
    public function save(Feed $feed): Feed
    {
        if ($feed->getToken() === '') {
            $feed->setData(
                'token',
                $this->random->getRandomString(self::TOKEN_LENGTH, Random::CHARS_LOWERS . Random::CHARS_DIGITS)
            );
        }
        $this->resource->save($feed);

        return $feed;
    }

    /**
     * Delete a feed together with its generated file.
     *
     * @param Feed $feed
     * @return void
     */
    public function delete(Feed $feed): void
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        if ($feed->getToken() !== '' && $mediaDirectory->isExist($feed->getRelativePath())) {
            $mediaDirectory->delete($feed->getRelativePath());
        }
        $this->resource->delete($feed);
    }

    /**
     * All feeds, optionally only the active ones.
     *
     * @param bool $activeOnly
     * @return Feed[]
     */
    public function getAll(bool $activeOnly = false): array
    {
        $collection = $this->collectionFactory->create();
        if ($activeOnly) {
            $collection->addFieldToFilter('is_active', 1);
        }

        return array_values($collection->getItems());
    }
}
