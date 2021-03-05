<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Ogrid
 */


namespace Amasty\Ogrid\Model\Column;

use Amasty\Base\Model\Serializer;
use Amasty\Ogrid\Model\Column;
use Magento\Framework\DB\Helper;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Module\Manager;

class Product extends Column
{
    protected $_alias_prefix = 'amasty_ogrid_product_';

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var bool
     */
    private $isUseInventoryStock;

    public function __construct(
        $fieldKey,
        $resourceModel,
        Serializer $serializer,
        Helper $dbHelper,
        Manager $moduleManager,
        $columns = [],
        $primaryKey = 'entity_id',
        $foreignKey = 'entity_id'
    ) {
        $this->moduleManager = $moduleManager;
        parent::__construct($fieldKey, $resourceModel, $serializer, $dbHelper, $columns, $primaryKey, $foreignKey);
    }

    /**
     * @param AbstractCollection $collection
     * @throws \Zend_Db_Select_Exception
     */
    public function addFieldToSelect($collection)
    {
        $fromPart = $collection->getSelect()->getPart(\Magento\Framework\DB\Select::FROM);
        
        if ($this->_fieldKey == 'qty_available' && !isset($fromPart['amasty_stock_item_table'])) {
            $this->addStockTableToCollection($collection, 'amasty_stock_item_table');
        } else {
            $collection->getSelect()->columns([
                $this->_alias_prefix . $this->_fieldKey => $this->_fieldKey
            ]);
        }

        foreach ($this->_columns as $column) {
            $collection->getSelect()->columns([
                $this->_alias_prefix . $column => $column
            ]);
        }
    }

    /**
     * @param AbstractCollection $orderItemCollection
     * @param mixed $value
     */
    public function addFieldToFilter($orderItemCollection, $value)
    {
        $fieldToFilter = 'main_table.' . $this->_fieldKey;

        if (is_array($value)) {
            if ($this->_fieldKey === 'qty_available') {
                $fieldToFilter = $this->isUseMagentoInventoryStock()
                    ? 'amasty_stock_item_table.quantity'
                    : 'amasty_stock_item_table.qty';
            }

            if (isset($value['from'])) {
                $orderItemCollection->addFieldToFilter($fieldToFilter, ['gteq' => $value['from']]);
            }

            if (isset($value['to'])) {
                $orderItemCollection->addFieldToFilter($fieldToFilter, ['lteq' => $value['to']]);
            }
        } else {
            $orderItemCollection->addFieldToFilter($fieldToFilter, ['like' => '%'. $value . '%']);
        }
    }

    /**
     * @param AbstractCollection $orderItemCollection
     * @param string $alias
     */
    private function addStockTableToCollection($orderItemCollection, $alias)
    {
        if ($this->isUseMagentoInventoryStock()) {
            $orderItemCollection->getSelect()->joinLeft(
                [$alias => $orderItemCollection->getTable('inventory_source_item')],
                $alias . '.sku = main_table.sku AND ' . $alias . '.status = 1',
                [$this->_alias_prefix . $this->_fieldKey => new \Zend_Db_Expr('SUM(' . $alias . '.quantity)')]
            )->group('main_table.item_id');
        } else {
            $orderItemCollection->getSelect()->joinLeft(
                [$alias => $orderItemCollection->getTable('cataloginventory_stock_item')],
                $alias . '.product_id = main_table.product_id',
                $alias . '.qty AS ' . $this->_alias_prefix . $this->_fieldKey
            );
        }
    }

    /**
     * @return bool
     */
    private function isUseMagentoInventoryStock()
    {
        if ($this->isUseInventoryStock === null) {
            $this->isUseInventoryStock = $this->moduleManager->isEnabled('Magento_Inventory');
        }

        return $this->isUseInventoryStock;
    }
}
