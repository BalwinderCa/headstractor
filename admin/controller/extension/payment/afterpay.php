<?php
require_once DIR_SYSTEM . 'library/vendor/afterpay/vendor/autoload.php';

use Afterpay\SDK\MerchantAccount as AfterpayMerchantAccount;
use Afterpay\SDK\HTTP\Request\CreateRefund as AfterpayCreateRefundRequest;
use Afterpay\SDK\HTTP\Request\GetConfiguration as AfterpayGetConfigurationRequest;

class ControllerExtensionPaymentAfterpay extends Controller {
    private $error = array();
    private $version = '1.2.0';

    protected $afterpay_config = array(
        'USD' => 'US',
        'AUD' => 'AU',
        'NZD' => 'NZ',
        'CAD' => 'CA',
        'GBP' => 'GB',
        'EUR' => array('ES', 'FR', 'IT')
    );

    public function index() {
        $data = $this->load->language('extension/payment/afterpay');

        if ($this->config->get('config_currency') == 'GBP' || $this->config->get('config_currency') == 'EUR') {
            $data = array_merge($data, $this->load->language('extension/payment/clearpay'));
            
            $this->document->setTitle($this->language->get('heading_title'));
        } else {
            $data['heading_title'] = $this->language->get('heading_title_afterpay');
            
            $this->document->setTitle($this->language->get('heading_title_afterpay'));
        }

        $this->load->model('setting/setting');
        $this->load->model('extension/payment/afterpay');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            if ($this->config->get('config_currency') == 'EUR') {
                $this->request->post['payment_afterpay_express_checkout_status'] = 0;
            }

            $this->model_setting_setting->editSetting('payment_afterpay', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            if (isset($this->request->get['continue']) && $this->request->get['continue']) {
                $this->response->redirect($this->url->link('extension/payment/afterpay', 'user_token=' . $this->session->data['user_token'], true));
            } else {
                $this->response->redirect($this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
            }
        }

        if ($this->config->get('payment_afterpay_merchant_id') && $this->config->get('payment_afterpay_secret_key')) {
            $config = $this->verifyAfterpayConfiguration();
        } else {
            $config = array();
        }

        if (isset($config['error'])) {
            $data['error_config'] = $config['error'];

            $data['payment_afterpay_minimum_amount'] = 'N/A';
            $data['payment_afterpay_maximum_amount'] = 'N/A';
        } else {
            $data['error_config'] = '';

            $data['payment_afterpay_minimum_amount'] = isset($config['minimum_amount']) ? $config['minimum_amount'] : 'N/A';
            $data['payment_afterpay_maximum_amount'] = isset($config['maximum_amount']) ? $config['maximum_amount'] : 'N/A';
        }
        
        $data['user_token'] = $this->session->data['user_token'];

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['merchant_id'])) {
            $data['error_merchant_id'] = $this->error['merchant_id'];
        } else {
            $data['error_merchant_id'] = '';
        }
    
        if (isset($this->error['secret_key'])) {
            $data['error_secret_key'] = $this->error['secret_key'];
        } else {
            $data['error_secret_key'] = '';
        }

        if (!$this->config->get('payment_afterpay_approved_status_id')) {
            $data['error_order_statuses'] = $this->language->get('error_order_statuses');
        } else {
            $data['error_order_statuses'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/afterpay', 'user_token=' . $this->session->data['user_token'], true),
        );

        $data['action'] = $this->url->link('extension/payment/afterpay', 'user_token=' . $this->session->data['user_token'], true);
        $data['continue'] = $this->url->link('extension/payment/afterpay', 'user_token=' . $this->session->data['user_token'] . '&continue=1', true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);
        
        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));

        $this->load->model('catalog/category');

        if (isset($this->request->post['payment_afterpay_categories'])) {
            $categories = $this->request->post['payment_afterpay_categories'];
        } elseif ($this->config->get('payment_afterpay_categories')) {
            $categories = $this->config->get('payment_afterpay_categories');
        } else {
            $categories = array();
        }

        $data['product_categories'] = array();

        foreach ($categories as $category_id) {
            $category_info = $this->model_catalog_category->getCategory($category_id);
      
            if ($category_info) {
                $data['product_categories'][] = array(
                    'category_id' => $category_info['category_id'],
                    'name'        => ($category_info['path']) ? $category_info['path'] . ' &gt; ' . $category_info['name'] : $category_info['name']
                );
            }
        }
        
        if (isset($this->request->post['payment_afterpay_merchant_id'])) {
            $data['payment_afterpay_merchant_id'] = $this->request->post['payment_afterpay_merchant_id'];
        } else {
            $data['payment_afterpay_merchant_id'] = $this->config->get('payment_afterpay_merchant_id');
        }
    
        if (isset($this->request->post['payment_afterpay_secret_key'])) {
            $data['payment_afterpay_secret_key'] = $this->request->post['payment_afterpay_secret_key'];
        } else {
            $data['payment_afterpay_secret_key'] = $this->config->get('payment_afterpay_secret_key');
        }

        if (isset($this->request->post['payment_afterpay_test'])) {
            $data['payment_afterpay_test'] = $this->request->post['payment_afterpay_test'];
        } else {
            $data['payment_afterpay_test'] = $this->config->get('payment_afterpay_test');
        }

        if (isset($this->request->post['payment_afterpay_geo_zone_id'])) {
            $data['payment_afterpay_geo_zone_id'] = $this->request->post['payment_afterpay_geo_zone_id'];
        } else {
            $data['payment_afterpay_geo_zone_id'] = $this->config->get('payment_afterpay_geo_zone_id');
        }

        $this->load->model('localisation/geo_zone');

        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_afterpay_product_listing'])) {
            $data['payment_afterpay_product_listing'] = $this->request->post['payment_afterpay_product_listing'];
        } else {
            $data['payment_afterpay_product_listing'] = $this->config->get('payment_afterpay_product_listing');
        }

        if (isset($this->request->post['payment_afterpay_badge_theme'])) {
            $data['payment_afterpay_badge_theme'] = $this->request->post['payment_afterpay_badge_theme'];
        } else {
            $data['payment_afterpay_badge_theme'] = $this->config->get('payment_afterpay_badge_theme');
        }

        if (isset($this->request->post['payment_afterpay_status'])) {
            $data['payment_afterpay_status'] = $this->request->post['payment_afterpay_status'];
        } else {
            $data['payment_afterpay_status'] = $this->config->get('payment_afterpay_status');
        }

        if (isset($this->request->post['payment_afterpay_sort_order'])) {
            $data['payment_afterpay_sort_order'] = $this->request->post['payment_afterpay_sort_order'];
        } else {
            $data['payment_afterpay_sort_order'] = $this->config->get('payment_afterpay_sort_order');
        }
        
        if (isset($this->request->post['payment_afterpay_express_checkout_status'])) {
            $data['payment_afterpay_express_checkout_status'] = $this->request->post['payment_afterpay_express_checkout_status'];
        } else {
            $data['payment_afterpay_express_checkout_status'] = $this->config->get('payment_afterpay_express_checkout_status');
        }

        if (isset($this->request->post['payment_afterpay_approved_status_id'])) {
            $data['payment_afterpay_approved_status_id'] = $this->request->post['payment_afterpay_approved_status_id'];
        } else {
            $data['payment_afterpay_approved_status_id'] = $this->config->get('payment_afterpay_approved_status_id');
        }

        if (isset($this->request->post['payment_afterpay_declined_status_id'])) {
            $data['payment_afterpay_declined_status_id'] = $this->request->post['payment_afterpay_declined_status_id'];
        } else {
            $data['payment_afterpay_declined_status_id'] = $this->config->get('payment_afterpay_declined_status_id');
        }

        if (isset($this->request->post['payment_afterpay_refunded_status_id'])) {
            $data['payment_afterpay_refunded_status_id'] = $this->request->post['payment_afterpay_refunded_status_id'];
        } else {
            $data['payment_afterpay_refunded_status_id'] = $this->config->get('payment_afterpay_refunded_status_id');
        }

        $data['currency_code'] = $this->config->get('config_currency');

        $this->load->model('localisation/order_status');

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/afterpay', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/afterpay')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_afterpay_merchant_id']) {
            $this->error['merchant_id'] = $this->language->get('error_merchant_id');
        }
  
        if (!$this->request->post['payment_afterpay_secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }

        return !$this->error;
    }

    public function install() {
        if (!$this->user->hasPermission('modify', 'extension/extension/payment')) {
            return;
        }
        
        $this->load->model('extension/payment/afterpay');

        $this->model_extension_payment_afterpay->install();
        
        $this->load->model('setting/event');
        
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/common/header/after', 'extension/payment/afterpay/eventPostViewCommonHeader');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/checkout/cart/after', 'extension/payment/afterpay/eventPostViewCheckoutCart');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/product/product/after', 'extension/payment/afterpay/eventPostViewProductProduct');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/extension/module/featured/before', 'extension/payment/afterpay/eventPreViewExtensionModule');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/extension/module/latest/before', 'extension/payment/afterpay/eventPreViewExtensionModule');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/extension/module/special/before', 'extension/payment/afterpay/eventPreViewExtensionModule');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/extension/module/bestseller/before', 'extension/payment/afterpay/eventPreViewExtensionModule');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/product/category/before', 'extension/payment/afterpay/eventPreViewExtensionModule');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/product/special/before', 'extension/payment/afterpay/eventPreViewExtensionModule');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/product/manufacturer/before', 'extension/payment/afterpay/eventPreViewExtensionModule');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/product/search/before', 'extension/payment/afterpay/eventPreViewExtensionModule');
        $this->model_setting_event->addEvent('payment_afterpay', 'catalog/view/product/product/before', 'extension/payment/afterpay/eventPreViewProductProduct');
    }

    public function uninstall() {
        if (!$this->user->hasPermission('modify', 'extension/extension/payment')) {
            return;
        }
        
        $this->load->model('extension/payment/afterpay');

        $this->model_extension_payment_afterpay->uninstall();
        
        $this->load->model('setting/event');
        
        $this->model_setting_event->deleteEventByCode('payment_afterpay');
    }
    
    public function order() {
        if ($this->config->get('payment_afterpay_status') && isset($this->request->get['order_id'])) {
            $this->load->model('extension/payment/afterpay');
      
            $afterpay_order = $this->model_extension_payment_afterpay->getOrder($this->request->get['order_id']);
      
            if (!empty($afterpay_order)) {
                $data = $this->load->language('extension/payment/afterpay');
                
                if ($this->config->get('config_currency') == 'GBP' || $this->config->get('config_currency') == 'EUR') {
                    $data = array_merge($data, $this->load->language('extension/payment/clearpay'));
                }

                $data['afterpay_order'] = $afterpay_order;
        
                $data['order_id'] = $this->request->get['order_id'];
                $data['user_token'] = $this->session->data['user_token'];
        
                return $this->load->view('extension/payment/afterpay_order', $data);
            }
        }
    }

    public function refund() {
        $json = array();

        $this->load->language('extension/payment/afterpay');
        
        if ($this->config->get('config_currency') == 'GBP' || $this->config->get('config_currency') == 'EUR') {
            $this->load->language('extension/payment/clearpay');
        }

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];
        } else {
            $order_id = 0;
        }
        
        $this->load->model('extension/payment/afterpay');

        $afterpay_order_info = $this->model_extension_payment_afterpay->getOrder($order_id);

        if ($afterpay_order_info) {
            $request_id = $order_id . '-' . $afterpay_order_info['afterpay_order_id'] . '-' . $this->model_extension_payment_afterpay->getRefundsNumber($afterpay_order_info['afterpay_order_id']);

            $refundRequest = new AfterpayCreateRefundRequest([
                'amount' => [
                    'amount'   => $this->request->post['refund_amount'],
                    'currency' => $afterpay_order_info['currency_code']
                ],
                'requestId'         => $request_id,
                'merchantReference' => $order_id
            ]);

            $refundRequest->setOrderId($afterpay_order_info['afterpay_reference_id']);

            if ($refundRequest->setMerchantAccount($this->getMerchantAccount())->send()) {
                $response = $refundRequest->getResponse()->getParsedBody();

                $request_id = $order_id . '-' . $afterpay_order_info['afterpay_order_id'] . '-' . $this->model_extension_payment_afterpay->getRefundsNumber($afterpay_order_info['afterpay_order_id']);

                if (isset($response->refundId) && $response->requestId == $request_id) {
                    $this->model_extension_payment_afterpay->addTransaction($afterpay_order_info['afterpay_order_id'], 'refunded', $response->amount->amount);

                    $comment = 'Refunded: ' . $response->amount->currency . ' ' . $response->amount->amount . ' (Refund ID: ' . $response->refundId . ')';
                    $this->model_extension_payment_afterpay->setOrderRefund($order_id, $comment);

                    $json['success']= sprintf($this->language->get('text_refunded'), $order_id);
                } else {
                    $json['error'] = $this->language->get('error_unknown');
                }
            } else {
                $error = $refundRequest->getResponse()->getParsedBody();

                if (isset($error->message)) {
                    $json['error'] = sprintf($this->language->get('error_message'), $error->message);
                } else {
                    $json['error'] = $this->language->get('error_unknown');
                }
            }
        } else {
            $json['error'] = $this->language->get('error_not_found');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function verifyAfterpayConfiguration() {
        $data = array();

        $get_configuration_request = new AfterpayGetConfigurationRequest();
        $get_configuration_request->setMerchantAccount($this->getMerchantAccount())->send();
        $body = $get_configuration_request->getResponse()->getParsedBody();

        if (isset($body->errorCode) && isset($body->message)) {
            if (isset($body->errorCode) && $body->errorCode == 'unauthorized') {
                $data['error'] = $this->language->get('error_credentials');
            } else {
                $data['error'] = sprintf($this->language->get('error_message'), $body->message);
            }
        } elseif (isset($body->maximumAmount)) {
            if (isset($body->minimumAmount)) {
                $minimum_amount = $body->minimumAmount->amount;
            } else {
                $minimum_amount = 0.0;
            }

            $maximum_amount = $body->maximumAmount->amount;
            $currency_code = $body->maximumAmount->currency;

            $config = array(
                'minimum_amount' => $minimum_amount,
                'maximum_amount' => $maximum_amount,
                'currency_code'  => $currency_code
            );

            $this->model_extension_payment_afterpay->updateAfterpayConfiguration($config);

            $country_code = isset($this->afterpay_config[$currency_code]) ? $this->afterpay_config[$currency_code] : '';

            if ($country_code && $currency_code != 'EUR') {
                $store_country_code = $this->getStoreCountryCode();

                if ($country_code != $store_country_code || $currency_code != $this->config->get('config_currency')) {
                    $data['error'] = $this->language->get('error_store_config');
                } else {
                    $data = $config;
                }
            } elseif ($country_code && $currency_code == 'EUR') {
                $store_country_code = $this->getStoreCountryCode();

                if (!in_array($store_country_code, $country_code) || $currency_code != $this->config->get('config_currency')) {
                    $data['error'] = $this->language->get('error_store_config');
                } else {
                    $data = $config;
                }
            } else {
                $data['error'] = $this->language->get('error_unknown');
            }
        } else {
            $data['error'] = $this->language->get('error_unknown');
        }

        return $data;
    }

    private function getStoreCountryCode() {
        $this->load->model('localisation/country');

        $country_info = $this->model_localisation_country->getCountry($this->config->get('config_country_id'));

        if ($country_info) {
            return $country_info['iso_code_2'];
        } else {
            return '';
        }
    }

    private function getMerchantAccount() { 
        $merchant = new AfterpayMerchantAccount();

        $merchant->setMerchantId($this->config->get('payment_afterpay_merchant_id'))
                 ->setSecretKey($this->config->get('payment_afterpay_secret_key'))
                 ->setCountryCode($this->getStoreCountryCode());

        if ($this->config->get('payment_afterpay_test')) {
            $merchant->setApiEnvironment('sandbox');
        } else {
            $merchant->setApiEnvironment('production');
        }

        return $merchant;
    }
}
