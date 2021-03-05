<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Ogrid
 */

namespace Amasty\Ogrid\Model\Indexer;

use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;
use Amasty\Ogrid\Model\ResourceModel\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Convert\DataObject as ObjectConverter;

class IndexerHandler implements IndexerInterface
{
    protected $_data;

    protected $_fields;

    protected $_resource;

    protected $_batch;

    protected $_eavConfig;

    protected $_batchSize;

    protected $_indexScopeResolver;

    protected $_indexStructure;

    protected $_attributeCollectionFactory;

    protected $_objectConverter;

    protected $_attributeCollection;

    protected $_attributesHash = [];

    public function __construct(
        ResourceConnection $resource,
        IndexStructure $indexStructure,
        Config $eavConfig,
        Batch $batch,
        IndexScopeResolver $indexScopeResolver,
        AttributeCollectionFactory $attributeCollectionFactory,
        ObjectConverter $objectConverter,
		\Amasty\Base\Model\Serializer $serializer,
        array $data,
        $batchSize = 200
    ) {
        $this->_indexScopeResolver = $indexScopeResolver;
        $this->_resource = $resource;
        $this->_batch = $batch;
        $this->_eavConfig = $eavConfig;
        $this->_data = $data;
        $this->_fields = [];

        $this->_batchSize = $batchSize;
        $this->_indexStructure = $indexStructure;
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_objectConverter = $objectConverter;
		$this->serializer = $serializer;
    }

    protected function getAttributeCollection()
    {
        if ($this->_attributeCollection === null) {
            $this->_attributeCollection = $this->_attributeCollectionFactory->create()
                ->addFieldToFilter('attribute_id', ['notnull' => true]);
        }
        return $this->_attributeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function saveIndex($dimensions, \Traversable $documents)
    {
        foreach ($this->_batch->getItems($documents, $this->_batchSize) as $batchDocuments) {
            $this->insertDocuments($batchDocuments, $dimensions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteIndex($dimensions, \Traversable $documents)
    {
        foreach ($this->_batch->getItems($documents, $this->_batchSize) as $batchDocuments) {
            $this->_resource->getConnection()
                ->delete($this->getTableName($dimensions), ['item_id in (?)' => $batchDocuments]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanIndex($dimensions)
    {
        $this->_indexStructure->create(
            $this->getIndexName(),
            $this->_objectConverter->toOptionHash(
                $this->getAttributeCollection()->getItems(),
                'attribute_id', 'attribute_code'
            ),
            $dimensions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable($dimensions = [])
    {
        return true;
    }

    /**
     * @param Dimension[] $dimensions
     * @return string
     */
    private function getTableName($dimensions)
    {
        return $this->_indexScopeResolver->resolve($this->getIndexName(), $dimensions);
    }

    /**
     * @return string
     */
    private function getIndexName()
    {
        return $this->_data['indexer_id'];
    }

    private function insertDocuments(array $documents, array $dimensions)
    {
        $attributesHash = $this->getAttributeHash();

        $documents = $this->_prepareFields($documents, $attributesHash);
        if (empty($documents)) {
            return;
        }

        $this->_resource->getConnection()->insertOnDuplicate(
            $this->getTableName($dimensions),
            $documents,
            $attributesHash
        );
    }

    /**
     * @param array $documents
     * @return array
     */
    protected function _prepareFields(array $documents, array $attributes)
    {
        $insertDocuments = [];

        foreach ($documents as $entityId => $document) {
            $attributesData = [];

            foreach ($attributes as $attributeId => $attributeCode) {
				if ($attributeId == '667') {					
					$attributesData[$attributeCode] = $this->getTurkoptionvalue($entityId);					
                } else if(array_key_exists($attributeId, $document)) {
                    $attributesData[$attributeCode] = $document[$attributeId];
                } else {
                    $attributesData[$attributeCode] = null;
                }
            }

            $insertDocuments[$entityId ] = array_merge([
                'order_item_id' => $entityId
            ], $attributesData);
        }

        return $insertDocuments;
    }

    public function getIndexedAttributesHash($dimensions)
    {
        return $this->_indexStructure->getIndexedAttributes(
            $this->getIndexName(),
            $this->_objectConverter->toOptionHash(
                $this->getAttributeCollection()->getItems(),
                'attribute_id',
                'attribute_code'
            ),
            $dimensions
        );
    }

    public function getNoneIndexedAttributesHash()
    {
        return $this->_objectConverter->toOptionHash(
            $this->getAttributeCollection()->getItems(),
            'attribute_id',
            'attribute_code'
        );
    }

    public function setAttributeHash($attributesHash)
    {
        $this->_attributesHash = $attributesHash;

        return $this;
    }

    public function getAttributeHash()
    {
        return $this->_attributesHash;
    }
	
	public function getTurkoptionvalue($order_item_id)
	{
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');			
		$connection = $resource->getConnection();
		$itemtableName = $resource->getTableName('sales_order_item');
		$tableName = $resource->getTableName('catalog_product_option_type_title');
		$sql_item = "Select `product_options` FROM " . $itemtableName . " where item_id = '".$order_item_id."'";
		$product_options = $connection->fetchOne($sql_item);
		$data = $this->serializer->unserialize($product_options);		
		if (array_key_exists("options",$data)) {
			$valueId = '';
			$label = '';
			$title = '';
			foreach ($data['options'] as $idx => $vals) {

				if (key_exists('option_id', $vals)) {
					 unset($vals['option_id']);						
				}

				if (key_exists('option_value', $vals)) {
					$valueId = $vals['option_value'];						
					unset($vals['option_value']);
				}
				$label = $vals['label'];

				$vals = [$vals['label'], $vals['value']];
				$options[$idx] = implode(': ', $vals);
			}
			if ($valueId) {
				$dp_title = '';
				$fullname = '';
				$sql = "Select `title` FROM " . $tableName . " Where option_type_id = '" . $valueId . "' and store_id = 36";
				$title = $connection->fetchOne($sql);				
				if ($label != '' && $title != '') {
					$fullname = $label . ': ' . $title;
				}
				if($fullname != ""){
					return $fullname;
				}else{
					return null;
				}
			}
		}
		return null;		
	}
}