<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\Indexer;

use Magento\Framework\App\Resource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Search\Request\Dimension;
use Magento\Framework\Indexer\IndexStructureInterface;
use Magento\Framework\Indexer\ScopeResolver\IndexScopeResolver;

class IndexStructure implements IndexStructureInterface
{
    /**
     * @var Resource
     */
    private $resource;
    /**
     * @var IndexScopeResolver
     */
    private $indexScopeResolver;

    /**
     * @param Resource $resource
     * @param IndexScopeResolver $indexScopeResolver
     */
    public function __construct(
        Resource $resource,
        IndexScopeResolver $indexScopeResolver
    ) {
        $this->resource = $resource;
        $this->indexScopeResolver = $indexScopeResolver;
    }

    /**
     * @param string $index
     * @param Dimension[] $dimensions
     * @return void
     */
    public function delete($index, array $dimensions = [])
    {
        $tableName = $this->indexScopeResolver->resolve($index, $dimensions);
        if ($this->resource->getConnection()->isTableExists($tableName)) {
            $this->resource->getConnection()->dropTable($tableName);
        }
    }

    /**
     * @param string $index
     * @param array $fields
     * @param array $dimensions
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return void
     */
    public function create($index, array $fields, array $dimensions = [])
    {
        $this->createFulltextIndex($this->indexScopeResolver->resolve($index, $dimensions));
    }

    /**
     * @param string $tableName
     * @throws \Zend_Db_Exception
     * @return void
     */
    protected function createFulltextIndex($tableName)
    {
        $table = $this->resource->getConnection()->newTable($tableName)
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false],
                'Entity ID'
            )->addColumn(
                'attribute_id',
                Table::TYPE_INTEGER,
                10,
                ['unsigned' => true, 'nullable' => false]
            )->addColumn(
                'data_index',
                Table::TYPE_TEXT,
                '4g',
                ['nullable' => true],
                'Data index'
            )->addIndex(
                'idx_primary',
                ['entity_id', 'attribute_id'],
                ['type' => AdapterInterface::INDEX_TYPE_PRIMARY]
            )->addIndex(
                'FTI_FULLTEXT_DATA_INDEX',
                ['data_index'],
                ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
            );
        $this->resource->getConnection()->createTable($table);
    }
}
