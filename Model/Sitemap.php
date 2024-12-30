<?php

namespace Taurus\Sitemap\Model;

use Taurus\Sitemap\Model\SitemapType;

class Sitemap extends \Magento\Sitemap\Model\Sitemap
{
    /**
     * @param $url
     * @param $lastmod
     * @param $changefreq
     * @param $priority
     * @param $images
     * @return string
     */
    protected function _getSitemapRow($url, $lastmod = null, $changefreq = null, $priority = null, $images = null)
    {
        if ((string)$this->getSitemapType() === SitemapType::IMAGES_ONLY && !$images) { // if sitemap type is Images Only - we don't add pages without images
            return '';
        }

        $url = $this->_getUrl($url);
        $row = '<loc>' . $this->_escaper->escapeUrl($url) . '</loc>';
        if ($lastmod) {
            $row .= '<lastmod>' . $this->_getFormattedLastmodDate($lastmod) . '</lastmod>';
        }
        if ($changefreq) {
            $row .= '<changefreq>' . $this->_escaper->escapeHtml($changefreq) . '</changefreq>';
        }
        if ($priority) {
            $row .= sprintf('<priority>%.1f</priority>', $this->_escaper->escapeHtml($priority));
        }

        if (in_array((string)$this->getSitemapType(), ['', SitemapType::PAGES_AND_IMAGES, SitemapType::IMAGES_ONLY])) {
            if ($images) {
                // Add Images to sitemap
                foreach ($images->getCollection() as $image) {
                    $row .= '<image:image>';
                    $row .= '<image:loc>' . $this->_escaper->escapeUrl($image->getUrl()) . '</image:loc>';
                    if ($images->getTitle()) {
                        $row .= '<image:title>' . $this->escapeXmlText($images->getTitle()) . '</image:title>';
                    }
                    if ($image->getCaption()) {
                        $row .= '<image:caption>' . $this->escapeXmlText($image->getCaption()) . '</image:caption>';
                    }
                    $row .= '</image:image>';
                }

                // Add PageMap image for Google web search
                if ((string)$this->getSitemapType() !== SitemapType::IMAGES_ONLY) {
                    $row .= '<PageMap xmlns="http://www.google.com/schemas/sitemap-pagemap/1.0"><DataObject type="thumbnail">';
                    $row .= '<Attribute name="name" value="' . $this->_escaper->escapeHtmlAttr($images->getTitle()) . '"/>';
                    $row .= '<Attribute name="src" value="' . $this->_escaper->escapeUrl($images->getThumbnail()) . '"/>';
                    $row .= '</DataObject></PageMap>';
                }
            }
        }

        return '<url>' . $row . '</url>';
    }

    /**
     * Write sitemap row
     *
     * @param string $row
     * @return void
     */
    protected function _writeSitemapRow($row)
    {
        if (strlen($row)) { // if sitemap type is Images Only - we don't add pages without images
            $this->_getStream()->write($row . PHP_EOL);
        }
    }

    /**
     * Escape string for XML context.
     *
     * @param string $text
     * @return string
     */
    private function escapeXmlText(string $text): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $fragment = $doc->createDocumentFragment();
        $fragment->appendChild($doc->createTextNode($text));
        return $doc->saveXML($fragment);
    }
}
