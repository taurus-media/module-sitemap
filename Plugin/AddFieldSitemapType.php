<?php

declare(strict_types=1);

namespace Taurus\Sitemap\Plugin;

use Magento\Framework\Registry;
use Taurus\Sitemap\Model\SitemapType;

class AddFieldSitemapType
{
    /**
     * @var Registry
     */
    protected Registry $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry,
    ) {
        $this->registry = $registry;
    }

    /**
     * Get form HTML
     *
     * @return string
     */
    public function aroundGetFormHtml(
        \Magento\Sitemap\Block\Adminhtml\Edit\Form $subject,
        \Closure $proceed
    ) {
        $form = $subject->getForm();

        if (is_object($form)) {
            $model = $this->registry->registry('sitemap_sitemap');

            $fieldset = $form->getElement('add_sitemap_form');
            $fieldset->addField(
                SitemapType::FIELD_ID,
                'select',
                [
                    'name' => SitemapType::FIELD_ID,
                    'label' => __('Sitemap Type'),
                    'id' => SitemapType::FIELD_ID,
                    'title' => __('Sitemap Type'),
                    'required' => false,
                    'value' => $model->getSitemapType() ?? SitemapType::PAGES_AND_IMAGES,
                    'values' => [
                        ['label' => __('Pages and Images'), 'value' => SitemapType::PAGES_AND_IMAGES],
                        ['label' => __('Pages only'), 'value' => SitemapType::PAGES_ONLY],
                        ['label' => __('Images only'), 'value' => SitemapType::IMAGES_ONLY]
                    ]
                ]
            );

            $subject->setForm($form);
        }

        return $proceed();
    }
}
