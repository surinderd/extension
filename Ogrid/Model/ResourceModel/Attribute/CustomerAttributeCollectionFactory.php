<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2021 Amasty (https://www.amasty.com)
 * @package Amasty_Ogrid
 */


namespace Amasty\Ogrid\Model\ResourceModel\Attribute;

class CustomerAttributeCollectionFactory
{
    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }
    public function create()
    {
        return $this->objectManager->create(
            'Amasty\CustomerAttributes\Model\ResourceModel\Customer\GuestAttributes\Collection'
        );
    }
}
