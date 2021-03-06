<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Customer\Ui\Component\Listing;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Customer\Ui\Component\ColumnFactory;
use Magento\Customer\Api\Data\AttributeMetadataInterface as AttributeMetadata;

class Columns extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var int
     */
    protected $columnSortOrder;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @param ContextInterface $context
     * @param ColumnFactory $columnFactory
     * @param AttributeRepository $attributeRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        ColumnFactory $columnFactory,
        AttributeRepository $attributeRepository,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->columnFactory = $columnFactory;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @return int
     */
    protected function getDefaultSortOrder()
    {
        $max = 0;
        foreach ($this->components as $component) {
            $config = $component->getData('config');
            if (isset($config['sortOrder']) && $config['sortOrder'] > $max) {
                $max = $config['sortOrder'];
            }
        }
        return ++$max;
    }

    /**
     * Update actions column sort order
     *
     * @return void
     */
    protected function updateActionColumnSortOrder()
    {
        if (isset($this->components['actions'])) {
            $component = $this->components['actions'];
            $component->setData(
                'config',
                array_merge($component->getData('config'), ['sortOrder' => ++$this->columnSortOrder])
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $this->columnSortOrder = $this->getDefaultSortOrder();
        foreach ($this->attributeRepository->getList() as $newAttributeCode => $attributeData) {
            if (isset($this->components[$newAttributeCode])) {
                $this->updateColumn($attributeData, $newAttributeCode);
            } elseif (!$attributeData[AttributeMetadata::BACKEND_TYPE] != 'static'
                && $attributeData[AttributeMetadata::IS_USED_IN_GRID]
            ) {
                $this->addColumn($attributeData, $newAttributeCode);
            }
        }
        $this->updateActionColumnSortOrder();
        parent::prepare();
    }

    /**
     * @param array $attributeData
     * @param string $columnName
     * @return void
     */
    public function addColumn(array $attributeData, $columnName)
    {
        $config['sortOrder'] = ++$this->columnSortOrder;
        $column = $this->columnFactory->create($attributeData, $columnName, $this->getContext(), $config);
        $column->prepare();
        $this->addComponent($attributeData[AttributeMetadata::ATTRIBUTE_CODE], $column);
    }

    /**
     * @param array $attributeData
     * @param string $newAttributeCode
     * @return void
     */
    public function updateColumn(array $attributeData, $newAttributeCode)
    {
        $component = $this->components[$attributeData[AttributeMetadata::ATTRIBUTE_CODE]];

        if ($attributeData[AttributeMetadata::BACKEND_TYPE] != 'static') {
            if ($attributeData[AttributeMetadata::IS_USED_IN_GRID]) {
                $config = array_merge(
                    $component->getData('config'),
                    [
                        'name' => $newAttributeCode,
                        'dataType' => $attributeData[AttributeMetadata::BACKEND_TYPE],
                        'visible' => $attributeData[AttributeMetadata::IS_VISIBLE_IN_GRID]
                    ]
                );
                $component->setData('config', $config);
            }
        } else {
            $component->setData(
                'config',
                array_merge(
                    $component->getData('config'),
                    ['visible' => $attributeData[AttributeMetadata::IS_VISIBLE_IN_GRID]]
                )
            );
        }
    }
}
