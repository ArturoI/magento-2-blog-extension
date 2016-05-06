<?php
/**
 * Mageplaza_Blog extension
 *                     NOTICE OF LICENSE
 * 
 *                     This source file is subject to the MIT License
 *                     that is bundled with this package in the file LICENSE.txt.
 *                     It is also available through the world-wide-web at this URL:
 *                     http://opensource.org/licenses/mit-license.php
 * 
 *                     @category  Mageplaza
 *                     @package   Mageplaza_Blog
 *                     @copyright Copyright (c) 2016
 *                     @license   http://opensource.org/licenses/mit-license.php MIT License
 */
namespace Mageplaza\Blog\Model\ResourceModel\Category;

class Tree extends \Magento\Framework\Data\Tree\Dbp
{
    /**
     * ID field
     * 
     * @var string
     */
    const ID_FIELD = 'id';

    /**
     * Path field
     * 
     * @var string
     */
    const PATH_FIELD = 'path';

    /**
     * Order field
     * 
     * @var string
     */
    const ORDER_FIELD = 'order';

    /**
     * Level field
     * 
     * @var string
     */
    const LEVEL_FIELD = 'level';

    /**
     * Event manager
     * 
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * Collection factory
     * 
     * @var \Mageplaza\Blog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Category Resource instance
     * 
     * @var \Mageplaza\Blog\Model\ResourceModel\Category
     */
    protected $categoryResource;

    /**
     * Cache instance
     * 
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * Store Manager instance
     * 
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * App resource
     * 
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $coreResource;

    /**
     * Category Collection
     * 
     * @var \Mageplaza\Blog\Model\ResourceModel\Category\Collection
     */
    protected $collection;

    /**
     * Inactive Category Ids
     * 
     * @var array
     */
    protected $inactiveCategoryIds;

    /**
     * constructor
     * 
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Mageplaza\Blog\Model\ResourceModel\Category\CollectionFactory $collectionFactory
     * @param \Mageplaza\Blog\Model\ResourceModel\Category $categoryResource
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\ResourceConnection $coreResource
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Mageplaza\Blog\Model\ResourceModel\Category\CollectionFactory $collectionFactory,
        \Mageplaza\Blog\Model\ResourceModel\Category $categoryResource,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $coreResource
    )
    {
        $this->eventManager      = $eventManager;
        $this->collectionFactory = $collectionFactory;
        $this->categoryResource  = $categoryResource;
        $this->cache             = $cache;
        $this->storeManager      = $storeManager;
        $this->coreResource      = $coreResource;
        parent::__construct(
            $coreResource->getConnection('mageplaza_blog_write'),
            $coreResource->getTableName('mageplaza_blog_category'),
            [
                \Magento\Framework\Data\Tree\Dbp::ID_FIELD => 'category_id',
                \Magento\Framework\Data\Tree\Dbp::PATH_FIELD => 'path',
                \Magento\Framework\Data\Tree\Dbp::ORDER_FIELD => 'position',
                \Magento\Framework\Data\Tree\Dbp::LEVEL_FIELD => 'level'
            ]
        );
    }


    /**
     * Add data to collection
     *
     * @param Collection $collection
     * @param boolean $sorted
     * @param array $exclude
     * @param boolean $toLoad
     * @param boolean $onlyActive
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addCollectionData(
        $collection = null,
        $sorted = false,
        $exclude = [],
        $toLoad = true,
        $onlyActive = false
    ) {
        if ($collection === null) {
            $collection = $this->getCollection($sorted);
        } else {
            $this->setCollection($collection);
        }

        if (!is_array($exclude)) {
            $exclude = [$exclude];
        }

        $nodeIds = [];
        foreach ($this->getNodes() as $node) {
            if (!in_array($node->getId(), $exclude)) {
                $nodeIds[] = $node->getId();
            }
        }
        $collection->addIdFilter($nodeIds);
        if ($onlyActive) {
            $disabledIds = $this->getDisabledIds($collection, $nodeIds);
            if ($disabledIds) {
                $collection->addFieldToFilter('category_id', ['nin' => $disabledIds]);
            }
        }


        if ($toLoad) {
            $collection->load();

            foreach ($collection as $category) {
                if ($this->getNodeById($category->getId())) {
                    $this->getNodeById($category->getId())->addData($category->getData());
                }
            }

            foreach ($this->getNodes() as $node) {
                if (!$collection->getItemById($node->getId()) && $node->getParent()) {
                    $this->removeNode($node);
                }
            }
        }

        return $this;
    }

    /**
     * Add inactive categories ids
     *
     * @param mixed $ids
     * @return $this
     */
    public function addInactiveCategoryIds($ids)
    {
        if (!is_array($this->inactiveCategoryIds)) {
            $this->initInactiveCategoryIds();
        }
        $this->inactiveCategoryIds = array_merge($ids, $this->inactiveCategoryIds);
        return $this;
    }

    /**
     * Retrieve inactive Categories ids
     *
     * @return $this
     */
    protected function initInactiveCategoryIds()
    {
        $this->inactiveCategoryIds = [];
        $this->eventManager->dispatch('mageplaza_blog_category_tree_init_inactive_category_ids', ['tree' => $this]);
        return $this;
    }

    /**
     * Retrieve inactive Categories ids
     *
     * @return array
     */
    public function getInactiveCategoryIds()
    {
        if (!is_array($this->inactiveCategoryIds)) {
            $this->initInactiveCategoryIds();
        }

        return $this->inactiveCategoryIds;
    }

    /**
     * Return disable Category ids
     *
     * @param Collection $collection
     * @param array $allIds
     * @return array
     */
    protected function getDisabledIds($collection, $allIds)
    {
        //TODO: implement this for frontend
        return [];
    }

    /**
     * Retrieve inactive Category item ids
     *
     * @param Collection $collection
     * @param int $storeId
     * @return array
     */
    protected function getInactiveItemIds($collection, $storeId)
    {
        //TODO: implement this for frontend
        return [];
    }

    /**
     * Check is Category items active
     *
     * @param int $id
     * @return boolean
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    protected function getItemIsActive($id)
    {
        //TODO: implement this for frontend
        return false;
    }

    /**
     * Get Categories collection
     *
     * @param boolean $sorted
     * @return Collection
     */
    public function getCollection($sorted = false)
    {
        if ($this->collection === null) {
            $this->collection = $this->getDefaultCollection($sorted);
        }
        return $this->collection;
    }

    /**
     * Clean unneeded collection
     *
     * @param Collection|array $object
     * @return void
     */
    protected function clean($object)
    {
        if (is_array($object)) {
            foreach ($object as $obj) {
                $this->clean($obj);
            }
        }
        unset($object);
    }

    /**
     * set collection
     *
     * @param Collection $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        if ($this->collection !== null) {
            $this->clean($this->collection);
        }
        $this->collection = $collection;
        return $this;
    }

    /**
     * get default collection
     *
     * @param boolean $sorted
     * @return Collection
     */
    protected function getDefaultCollection($sorted = false)
    {
        $collection = $this->collectionFactory->create();
        if ($sorted) {
            if (is_string($sorted)) {
                // $sorted is supposed to be attribute name
                $collection->addFieldToSort($sorted);
            } else {
                $collection->addFieldToSort('name');
            }
        }

        return $collection;
    }

    /**
     * Executing parents move method and cleaning cache after it
     *
     * @param mixed $category
     * @param mixed $newParent
     * @param mixed $prevNode
     * @return void
     */
    public function move($category, $newParent, $prevNode = null)
    {
        $this->categoryResource->move($category->getId(), $newParent->getId());
        parent::move($category, $newParent, $prevNode);

        $this->afterMove();
    }

    /**
     * Move tree after
     *
     * @return $this
     */
    protected function afterMove()
    {
        $this->cache->clean([\Mageplaza\Blog\Model\Category::CACHE_TAG]);
        return $this;
    }

    /**
     * Load whole Category tree, that will include specified Categories ids.
     *
     * @param array $ids
     * @param bool $addCollectionData
     * @return $this|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function loadByIds($ids, $addCollectionData = true)
    {
        $levelField = $this->_conn->quoteIdentifier('level');
        $pathField = $this->_conn->quoteIdentifier('path');
        // load first two levels, if no ids specified
        if (empty($ids)) {
            $select = $this->_conn
                ->select()
                ->from($this->_table, 'category_id')
                ->where($levelField . ' <= 2');
            $ids = $this->_conn->fetchCol($select);
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        foreach ($ids as $key => $id) {
            $ids[$key] = (int)$id;
        }

        // collect paths of specified IDs and prepare to collect all their parents and neighbours
        $select = $this->_conn
            ->select()
            ->from($this->_table, ['path', 'level'])
            ->where('category_id IN (?)', $ids);
        $where = [$levelField . '=0' => true];

        foreach ($this->_conn->fetchAll($select) as $item) {
            $pathIds = explode('/', $item['path']);
            $level = (int)$item['level'];
            while ($level > 0) {
                $pathIds[count($pathIds) - 1] = '%';
                $path = implode('/', $pathIds);
                $where["{$levelField}={$level} AND {$pathField} LIKE '{$path}'"] = true;
                array_pop($pathIds);
                $level--;
            }
        }
        $where = array_keys($where);

        // get all required records
        if ($addCollectionData) {
            $select = $this->createCollectionDataSelect();
        } else {
            $select = clone $this->_select;
            $select->order($this->_orderField . ' ' . \Magento\Framework\DB\Select::SQL_ASC);
        }
        $select->where(implode(' OR ', $where));

        // get array of records and add them as nodes to the tree
        $arrNodes = $this->_conn->fetchAll($select);
        if (!$arrNodes) {
            return false;
        }
        $childrenItems = [];
        foreach ($arrNodes as $key => $nodeInfo) {
            $pathToParent = explode('/', $nodeInfo[$this->_pathField]);
            array_pop($pathToParent);
            $pathToParent = implode('/', $pathToParent);
            $childrenItems[$pathToParent][] = $nodeInfo;
        }
        $this->addChildNodes($childrenItems, '', null);
        return $this;
    }

    /**
     * Load array of category parents
     *
     * @param string $path
     * @param bool $addCollectionData
     * @param bool $withRootNode
     * @return array
     */
    public function loadBreadcrumbsArray($path, $addCollectionData = true, $withRootNode = false)
    {
        $pathIds = explode('/', $path);
        if (!$withRootNode) {
            array_shift($pathIds);
        }
        $result = [];
        if (!empty($pathIds)) {
            if ($addCollectionData) {
                $select = $this->createCollectionDataSelect(false);
            } else {
                $select = clone $this->_select;
            }
            $select->where(
                'e.category_id IN(?)',
                $pathIds
            )->order(
                $this->_conn->getLengthSql('e.path') . ' ' . \Magento\Framework\DB\Select::SQL_ASC
            );
            $result = $this->_conn->fetchAll($select);
        }
        return $result;
    }


    /**
     * Obtain select for Categories
     *
     * @param bool $sorted
     * @param array $optionalAttributes
     * @return \Zend_Db_Select
     */
    protected function createCollectionDataSelect($sorted = true, $optionalAttributes = [])
    {

        $select = $this->getDefaultCollection($sorted ? $this->_orderField : false)->getSelect();

        // count children products qty plus self products qty
        $categoriesTable = $this->coreResource->getTableName('mageplaza_blog_category');

        $subConcat = $this->_conn->getConcatSql(['e.path', $this->_conn->quote('/%')]);
        $subSelect = $this->_conn->select()->from(
            ['see' => $categoriesTable],
            null
        )->where(
            'see.category_id = e.category_id'
        )->orWhere(
            'see.path LIKE ?',
            $subConcat
        );
        return $select;
    }

    /**
     * Get real existing Category ids by specified ids
     *
     * @param array $ids
     * @return array
     */
    public function getExistingCategoryIdsBySpecifiedIds($ids)
    {
        if (empty($ids)) {
            return [];
        }
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $select = $this->_conn
            ->select()
            ->from($this->_table, ['category_id'])
            ->where('category_id IN (?)', $ids);
        return $this->_conn->fetchCol($select);
    }
}
