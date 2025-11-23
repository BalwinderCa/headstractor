<?php
require_once DIR_SYSTEM . 'library/vendor/afterpay/vendor/autoload.php';

use Afterpay\SDK\MerchantAccount as AfterpayMerchantAccount;
use Afterpay\SDK\HTTP\Request\GetConfiguration as AfterpayGetConfigurationRequest;

class ModelExtensionPaymentAfterpay extends Model {
    protected $afterpay_config = array(
        'USD' => array(
             'country_code' => 'US',
             'locale'       => 'en_US'
        ),
        'AUD' => array(
             'country_code' => 'AU',
             'locale'       => 'en_AU'
        ),
        'NZD' => array(
             'country_code' => 'NZ',
             'locale'       => 'en_NZ'
        ),
        'CAD' => array(
             'country_code' => 'CA',
             'locale'       => 'en_CA'
        ),
        'GBP' => array(
             'country_code' => 'GB',
             'locale'       => 'en_GB'
        )
    );

    protected $clearpay_config = array(
        'country_code' => array('ES', 'FR', 'IT'),
        'locale'       => array(
            'ES' => 'es_ES',
            'FR' => 'fr_FR',
            'IT' => 'it_IT'
        )
    );

    public function getAfterpayConfiguration() {
        $config = array();

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "afterpay_config`");

        if ($query->num_rows && (strtotime($query->row['date_added']) < strtotime('-30 minutes'))) {
            $config = array(
                'minimum_amount'  => $query->row['minimum_amount'],
                'maximum_amount'  => $query->row['maximum_amount'],
                'currency_code'   => $query->row['currency_code']
            );
        } else {
            $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "afterpay_config`");

            $merchant = new AfterpayMerchantAccount();

            $merchant->setMerchantId($this->config->get('payment_afterpay_merchant_id'))
                     ->setSecretKey($this->config->get('payment_afterpay_secret_key'))
                     ->setCountryCode($this->checkStoreCountryCode($this->session->data['currency']));

            if ($this->config->get('payment_afterpay_test')) {
                $merchant->setApiEnvironment('sandbox');
            } else {
                $merchant->setApiEnvironment('production');
            }
            
            $get_configuration_request = new AfterpayGetConfigurationRequest();
            $get_configuration_request->setMerchantAccount($merchant)->send();
            $body = $get_configuration_request->getResponse()->getParsedBody();

            if (isset($body->maximumAmount)) {
                if (isset($body->minimumAmount)) {
                    $minimum_amount = $body->minimumAmount->amount;
                } else {
                    $minimum_amount = 0.0;
                }

                $maximum_amount = $body->maximumAmount->amount;
                $currency_code = $body->maximumAmount->currency;

                $this->db->query("INSERT INTO `" . DB_PREFIX . "afterpay_config` SET `minimum_amount` = '" . (float)$minimum_amount . "', `maximum_amount` = '" . (float)$maximum_amount . "', `currency_code` = '" . $this->db->escape($currency_code) . "', `date_added` = NOW()");

                $config = array(
                    'minimum_amount' => $minimum_amount,
                    'maximum_amount' => $maximum_amount,
                    'currency_code'  => $currency_code
                );
            }
        }

        return $config;
    }

    private function checkSpendAmount($minimum_amount, $maximum_amount, $total) {
        if ($total >= $minimum_amount && $total <= $maximum_amount) {
            return true;
        } else {
            return false;
        }
    }

    public function checkStoreCountryCode($currency_code) {
        $this->load->model('localisation/country');

        if ($currency_code != 'EUR') {
            $country_code = isset($this->afterpay_config[$currency_code]['country_code']) ? $this->afterpay_config[$currency_code]['country_code'] : '';
        } else {
            $country_code = '';
        }

        $country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));

        if ($country_code && $country_info['iso_code_2'] == $country_code) {
            return $country_info['iso_code_2'];
        } elseif ($currency_code == 'EUR' && in_array($country_info['iso_code_2'], $this->clearpay_config['country_code'])) {
            return $country_info['iso_code_2'];
        } else {
            return false;
        }
    }

    public function getLocale($currency_code) {
        $locale = false;

        if ($currency_code != 'EUR') {
            $locale = isset($this->afterpay_config[$currency_code]['locale']) ? $this->afterpay_config[$currency_code]['locale'] : false;
        } elseif ($currency_code == 'EUR') {
            $country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));

            if (isset($this->clearpay_config['locale'][$country_info['iso_code_2']])) {
                $locale = $this->clearpay_config['locale'][$country_info['iso_code_2']];
            }
        }

        return $locale;
    }

    public function checkCategoriesRestriction($product_id = 0) {
        $this->load->model('catalog/product');

        if ($product_id) {
            $products = array(array('product_id' => $product_id));
        } else {
            $products = $this->cart->getProducts();
        }

        $restricted_categories = $this->config->get('payment_afterpay_categories');

        if ($restricted_categories) {
            foreach ($products as $product) {
                $product_categories = $this->model_catalog_product->getCategories($product['product_id']);

                if ($product_categories) {
                    foreach ($product_categories as $product_category) {
                        if (in_array($product_category['category_id'], $restricted_categories)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }
    
    public function getMethod($address, $total) {
        $this->load->language('extension/payment/afterpay');

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone_to_geo_zone` WHERE `geo_zone_id` = '" . (int)$this->config->get('payment_afterpay_geo_zone_id') . "' AND `country_id` = '" . (int)$address['country_id'] . "' AND (`zone_id` = '" . (int)$address['zone_id'] . "' OR `zone_id` = '0')");

        $config = $this->getAfterpayConfiguration();

        if ($config) {
            $store_country_code = $this->checkStoreCountryCode($config['currency_code']);
        } else {
            return array();
        }

        if (!$this->checkSpendAmount($config['minimum_amount'], $config['maximum_amount'], $total)) {
            $status = false;
        } elseif ($config['currency_code'] != $this->session->data['currency']) {
            $status = false;
        } elseif (!$store_country_code) {
            $status = false;
        } elseif (!$this->checkCategoriesRestriction()) {
            $status = false;
        } elseif (!$this->config->get('payment_afterpay_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            if ($store_country_code == 'GB' || $store_country_code == 'ES' || $store_country_code == 'FR' || $store_country_code == 'IT') {
                $brand = 'clearpay';
                $brand_title = 'Clearpay';
            } else {
                $brand = 'afterpay';
                $brand_title = 'Afterpay';
            }

            if ($this->config->get('payment_afterpay_badge_theme')) {
                $theme = $this->config->get('payment_afterpay_badge_theme');
            } else {
                $theme = 'black-on-mint';
            }

            $afterpay_title = str_ireplace('{{brand}}', $brand, $this->language->get('text_title'));
            $afterpay_title = str_ireplace('{{brandtitle}}', $brand_title, $afterpay_title);
            $afterpay_title = str_ireplace('{{theme}}', $theme, $afterpay_title);

            $method_data = array(
                'code'       => 'afterpay',
                'title'      => $afterpay_title,
                'terms'      => '',
                'sort_order' => $this->config->get('payment_afterpay_sort_order')
            );
        }

        return $method_data;
    }

    public function addOrder($order_info) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "afterpay_order` SET `order_id` = '" . (int)$order_info['order_id'] . "', afterpay_reference_id = '" . $this->db->escape($order_info['afterpay_reference_id']) . "', `currency_code` = '" . $this->db->escape($order_info['currency_code']) . "', `total` = '" . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . "', `date_added` = now(), `date_modified` = now()");
    
        return $this->db->getLastId();
    }

    public function addTransaction($afterpay_order_id, $type, $amount) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "afterpay_order_transaction` SET `afterpay_order_id` = '" . (int)$afterpay_order_id . "', `date_added` = now(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (float)$amount . "'");
    }

    public function editOrderPayment($order_id, $payment_method) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET payment_method = '" . $this->db->escape($payment_method) . "' WHERE order_id = '" . (int)$order_id . "'");
    }
    
    public function getCountryIdByIsoCode2($iso_code_2) {
        $query = $this->db->query("SELECT DISTINCT `country_id` FROM `" . DB_PREFIX . "country` WHERE `iso_code_2` = '" . $this->db->escape($iso_code_2) . "'");

        if ($query->num_rows) {
            return $query->row['country_id'];
        } else {
            return 0;
        }
    }

    public function getZoneIdByCode($zone_code) {
        $query = $this->db->query("SELECT DISTINCT `zone_id` FROM `" . DB_PREFIX . "zone` WHERE `code` = '" . $this->db->escape($zone_code) . "'");

        if ($query->num_rows) {
            return $query->row['zone_id'];
        } else {
            $query = $this->db->query("SELECT DISTINCT `zone_id` FROM `" . DB_PREFIX . "zone` WHERE `name` = '" . $this->db->escape($zone_code) . "'");

            if ($query->num_rows) {
                return $query->row['zone_id'];
            }
        }

        return 0;
    }

    public function updateExpressOrderEmail($order_id, $email) {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET `email` = '" . $this->db->escape($email) . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function getApiEnvironment() {
        $config = $this->getAfterpayConfiguration();

        if ($this->config->get('payment_afterpay_test')) {
            $url = 'https://global-api-sandbox.afterpay.com';
        } else {
            $url = 'https://global-api.afterpay.com';
        }

        return $url;
    }
}