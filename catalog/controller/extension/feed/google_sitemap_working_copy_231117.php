<?php
# https://www.headstractor.com.au/index.php?route=extension/feed/google_sitemap
class ControllerExtensionFeedGoogleSitemap extends Controller {

  

    public function index() {
        if ($this->config->get('feed_google_sitemap_status')) {
            // File setup
			
			@unlink($_SERVER['DOCUMENT_ROOT'] . '/SitemapPrev.xml');
			@copy($_SERVER['DOCUMENT_ROOT'] . '/Sitemap.xml', $_SERVER['DOCUMENT_ROOT'] . '/SitemapPrev.xml');
			@unlink($_SERVER['DOCUMENT_ROOT'] . '/Sitemap.xml');

            $file = fopen($_SERVER['DOCUMENT_ROOT']  . '/Sitemap.xml', 'w');

            // Start of XML file
            fwrite($file, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            fwrite($file, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n");

            // Load models
            $this->load->model('catalog/product');
            $this->load->model('tool/image');

            // Write products to the file
            $products = $this->model_catalog_product->getProducts();
            foreach ($products as $product) {
                $this->writeProduct($file, $product);
            }

            // Write categories to the file
            $this->load->model('catalog/category');
            $this->getCategories(0, '', $file);

            // Write manufacturers to the file
            $this->load->model('catalog/manufacturer');
            $manufacturers = $this->model_catalog_manufacturer->getManufacturers();
            foreach ($manufacturers as $manufacturer) {
                $this->writeManufacturer($file, $manufacturer);
            }

            // Write information pages to the file
            $this->load->model('catalog/information');
            $informations = $this->model_catalog_information->getInformations();
            foreach ($informations as $information) {
                $this->writeInformation($file, $information);
            }

            // End of XML file
            fwrite($file, '</urlset>' . "\n");

            // Close the file
            fclose($file);

            $this->response->redirect('/Sitemap.xml');
        }
    }

    protected function getCategories($parent_id, $current_path = '', $file) {
        $results = $this->model_catalog_category->getCategories($parent_id);
        foreach ($results as $result) {
            $path = (!empty($current_path) ? $current_path . '_' : '') . $result['category_id'];
            $this->writeCategory($file, $path, $result);

			// Write products for this category
			$products = $this->model_catalog_product->getProductsByCategory($result['category_id']);
			foreach ($products as $product) {
				$this->writeProduct($file, $product, $path);
			}

            $this->getCategories($result['category_id'], $path, $file);
        }
    }

    private function writeProduct($file, $product) {
        $seo_url_query = $this->db->query("SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = 'product_id=" . (int)$product['product_id'] . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "' AND language_id = '" . (int)$this->config->get('config_language_id') . "'");
        if ($seo_url_query->num_rows) {
            $product_url = $this->config->get('config_url') . $seo_url_query->row['keyword'];
        } else {
            $product_url = $this->url->link('product/product', 'product_id=' . $product['product_id']);
        }
        fwrite($file, "<url>\n");
        fwrite($file, "  <loc>{$product_url}</loc>\n");
        fwrite($file, "  <changefreq>weekly</changefreq>\n");
        fwrite($file, "  <lastmod>" . date('Y-m-d\TH:i:sP', strtotime($product['date_modified'])) . "</lastmod>\n");
        fwrite($file, "  <priority>1.0</priority>\n");
        if ($product['image']) {
            fwrite($file, "  <image:image>\n");
            fwrite($file, "  <image:loc>" . $this->model_tool_image->resize($product['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_popup_height')) . "</image:loc>\n");
            fwrite($file, "  <image:caption>" . htmlspecialchars($product['name']) . "</image:caption>\n");
            fwrite($file, "  <image:title>" . htmlspecialchars($product['name']) . "</image:title>\n");
            fwrite($file, "  </image:image>\n");
        }
        fwrite($file, "</url>\n");
    }

    private function writeCategory($file, $path, $category) {
        fwrite($file, "<url>\n");
        fwrite($file, "  <loc>" . htmlspecialchars($this->url->link('product/category', 'path=' . $path)) . "</loc>\n");
        fwrite($file, "  <changefreq>weekly</changefreq>\n");
        fwrite($file, "  <priority>0.7</priority>\n");
        fwrite($file, "</url>\n");
    }

    private function writeManufacturer($file, $manufacturer) {
        fwrite($file, "<url>\n");
        fwrite($file, "  <loc>" . $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $manufacturer['manufacturer_id']) . "</loc>\n");
        fwrite($file, "  <changefreq>weekly</changefreq>\n");
        fwrite($file, "  <priority>0.7</priority>\n");
        fwrite($file, "</url>\n");
    }

    private function writeInformation($file, $information) {
        fwrite($file, "<url>\n");
        fwrite($file, "  <loc>" . $this->url->link('information/information', 'information_id=' . $information['information_id']) . "</loc>\n");
        fwrite($file, "  <changefreq>weekly</changefreq>\n");
        fwrite($file, "  <priority>0.5</priority>\n");
        fwrite($file, "</url>\n");
    }
}
