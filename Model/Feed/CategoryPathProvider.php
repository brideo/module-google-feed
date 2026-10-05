<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Model\Feed;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Category breadcrumbs for a store, loaded once per store and reused for every product.
 */
class CategoryPathProvider
{
    private const SEPARATOR = ' > ';

    /**
     * @var array[] Store ID => category ID => ['level' => int, 'path' => string]
     */
    private array $paths = [];

    /**
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Breadcrumb of the deepest of the given categories within the store's tree.
     *
     * @param int[] $categoryIds
     * @param int $storeId
     * @return string
     */
    public function getDeepestPath(array $categoryIds, int $storeId): string
    {
        $paths = $this->paths[$storeId] ??= $this->load($storeId);

        $best = null;
        foreach ($categoryIds as $categoryId) {
            $candidate = $paths[(int) $categoryId] ?? null;
            if ($candidate !== null && ($best === null || $candidate['level'] > $best['level'])) {
                $best = $candidate;
            }
        }

        return $best['path'] ?? '';
    }

    /**
     * Build breadcrumbs for every active category under the store's root.
     *
     * @param int $storeId
     * @return array[]
     */
    private function load(int $storeId): array
    {
        $rootId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId)
            ->addAttributeToSelect('name')
            ->addAttributeToFilter('is_active', 1)
            ->addFieldToFilter('path', ['like' => '1/' . $rootId . '/%']);

        $names = [];
        $rawPaths = [];
        foreach ($collection as $category) {
            $names[(int) $category->getId()] = (string) $category->getName();
            $rawPaths[(int) $category->getId()] = (string) $category->getPath();
        }

        $paths = [];
        foreach ($rawPaths as $categoryId => $rawPath) {
            // Drop the tree root and the store root, which shoppers never see.
            $ids = array_slice(explode('/', $rawPath), 2);
            $parts = [];
            foreach ($ids as $id) {
                if (!isset($names[(int) $id])) {
                    // An inactive ancestor hides the whole branch.
                    continue 2;
                }
                $parts[] = $names[(int) $id];
            }
            $paths[$categoryId] = ['level' => count($parts), 'path' => implode(self::SEPARATOR, $parts)];
        }

        return $paths;
    }
}
