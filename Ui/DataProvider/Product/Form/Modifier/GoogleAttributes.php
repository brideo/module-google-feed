<?php
/**
 * UpturnStudio_GoogleFeed
 */
declare(strict_types=1);

namespace UpturnStudio\GoogleFeed\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Escaper;
use UpturnStudio\GoogleFeed\Model\ResourceModel\ProductAttribute as ProductAttributeResource;

/**
 * Shows, read-only, the Google attribute values that were pushed for the product being edited.
 */
class GoogleAttributes extends AbstractModifier
{
    /**
     * @param LocatorInterface $locator
     * @param ProductAttributeResource $resource
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly LocatorInterface $locator,
        private readonly ProductAttributeResource $resource,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $sku = (string) $this->locator->getProduct()->getSku();
        $rows = $sku === '' ? [] : $this->resource->getBySku($sku);
        if (!$rows) {
            return $meta;
        }

        $meta['upturnstudio_google_attributes'] = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => 'fieldset',
                        'label' => __('Google Feed Attributes'),
                        'collapsible' => true,
                        'sortOrder' => 900,
                    ],
                ],
            ],
            'children' => [
                'values' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/html',
                                'content' => $this->renderTable($rows),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return $meta;
    }

    /**
     * Render the values as a table.
     *
     * @param array[] $rows
     * @return string
     */
    private function renderTable(array $rows): string
    {
        $html = '<p>' . $this->escaper->escapeHtml(
            __('These values are set through the Google Feeds API and replace the feed mapping for this product.')
        ) . '</p><table class="admin__table-secondary"><thead><tr>';
        foreach ([__('Google Attribute'), __('Value'), __('Store View'), __('Source'), __('Updated (UTC)')] as $heading) {
            $html .= '<th>' . $this->escaper->escapeHtml($heading) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $cells = [
                $row['attribute_code'],
                (string) $row['value'] === '' ? __('(removed from feed)') : $row['value'],
                (int) $row['store_id'] === 0 ? __('All') : $row['store_id'],
                $row['source'],
                $row['updated_at'],
            ];
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $html .= '<td>' . $this->escaper->escapeHtml((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }
}
