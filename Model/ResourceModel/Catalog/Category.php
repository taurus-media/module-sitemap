<?php

namespace Taurus\Sitemap\Model\ResourceModel\Catalog;

use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Catalog\Model\ResourceModel\Category as CategoryResourceModel;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Category\Image as CategoryImageModel;
use Magento\Catalog\Model\CategoryFactory;

class Category extends \Magento\Sitemap\Model\ResourceModel\Catalog\Category
{
    /**
     * @var EavConfig
     */
    protected EavConfig $eavConfig;

    /**
     * @var CategoryImageModel
     */
    protected CategoryImageModel $CategoryImageModel;

    /**
     * @var CategoryFactory
     */
    protected CategoryFactory $categoryFactory;

    /**
     * @param EavConfig $eavConfig
     * @param CategoryImageModel $CategoryImageModel
     * @param CategoryFactory $categoryFactory
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CategoryResourceModel $categoryResource
     * @param MetadataPool $metadataPool
     * @param $connectionName
     */
    public function __construct(
        EavConfig $eavConfig,
        CategoryImageModel $CategoryImageModel,
        CategoryFactory $categoryFactory,
        Context $context,
        StoreManagerInterface $storeManager,
        CategoryResourceModel $categoryResource,
        MetadataPool $metadataPool,
        $connectionName = null
    ) {
        $this->eavConfig = $eavConfig;
        $this->CategoryImageModel = $CategoryImageModel;
        $this->categoryFactory = $categoryFactory;

        parent::__construct($context, $storeManager, $categoryResource, $metadataPool, $connectionName);}

    /**
     * Get category collection array
     *
     * @param null|string|bool|int|\Magento\Store\Model\Store $storeId
     * @return array|bool
     */
    public function getCollection($storeId)
    {
        $categories = [];

        /* @var $store \Magento\Store\Model\Store */
        $store = $this->_storeManager->getStore($storeId);

        if (!$store) {
            return false;
        }

        $connection = $this->getConnection();

        $this->_select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            $this->getIdFieldName() . '=?',
            $store->getRootCategoryId()
        );
        $categoryRow = $connection->fetchRow($this->_select);

        if (!$categoryRow) {
            return false;
        }

        $imageAttributeId = $this->eavConfig->getAttribute('catalog_category', 'image')->getId();

        $this->_select = $connection->select()->from(
            ['e' => $this->getMainTable()],
            [$this->getIdFieldName(), 'updated_at']
        )->joinLeft(
            ['url_rewrite' => $this->getTable('url_rewrite')],
            'e.entity_id = url_rewrite.entity_id AND url_rewrite.is_autogenerated = 1'
            . $connection->quoteInto(' AND url_rewrite.store_id = ?', $store->getId())
            . $connection->quoteInto(' AND url_rewrite.entity_type = ?', CategoryUrlRewriteGenerator::ENTITY_TYPE),
            ['url' => 'request_path']
        )->joinLeft( // load category image
            ['ccev' => $this->getTable('catalog_category_entity_varchar')],
            'e.entity_id = ccev.entity_id AND ccev.attribute_id = ' . $imageAttributeId,
            ['image' => 'ccev.value']
        )->where(
            'e.path LIKE ?',
            $categoryRow['path'] . '/%'
        );

        $this->_addFilter($storeId, 'is_active', 1);

        $query = $connection->query($this->_select);
        while ($row = $query->fetch()) {
            $category = $this->_prepareCategory($row);
            $categories[$category->getId()] = $category;
        }

        return $categories;
    }

    /**
     * Prepare category
     *
     * @param array $categoryRow
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareCategory(array $categoryRow)
    {
        $category = new \Magento\Framework\DataObject();
        $category->setId($categoryRow[$this->getIdFieldName()]);
        $categoryUrl = !empty($categoryRow['url']) ? $categoryRow['url'] : 'catalog/category/view/id/' .
            $category->getId();
        $category->setUrl($categoryUrl);
        $category->setUpdatedAt($categoryRow['updated_at']);

        // add category image
        $categoryObject = $this->categoryFactory->create();
        $categoryObject->setImage($categoryRow['image']);

        if ($categoryRow['image']) {
            $imagesCollection = [
                new \Magento\Framework\DataObject(
                    ['url' => $this->CategoryImageModel->getUrl($categoryObject)]
                )
            ];
            $category->setImages(
                new \Magento\Framework\DataObject(
                    ['collection' => $imagesCollection]
                )
            );
        }

        return $category;
    }
}
