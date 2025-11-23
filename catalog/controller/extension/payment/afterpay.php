<?php
require_once DIR_SYSTEM . 'library/vendor/afterpay/vendor/autoload.php';

use Afterpay\SDK\HTTP\Request\ImmediatePaymentCapture as AfterpayImmediatePaymentCaptureRequest;
use Afterpay\SDK\Exception\InvalidModelException as AfterpayInvalidModelException;
use Afterpay\SDK\HTTP\Request\CreateCheckout as AfterpayCreateCheckoutRequest;
use Afterpay\SDK\HTTP\Request\GetCheckout as AfterpayGetCheckoutRequest;
use Afterpay\SDK\MerchantAccount as AfterpayMerchantAccount;

class ControllerExtensionPaymentAfterpay extends Controller {
    private $version = '1.2.0';

    public function index() {
        $data = $this->load->language('extension/payment/afterpay');

        $this->load->model('extension/payment/afterpay');
        $this->load->model('checkout/order');

        $data['button_continue'] = $this->language->get('button_continue');
        $data['text_loading'] = $this->language->get('text_loading');
        $data['testmode'] = $this->config->get('payment_afterpay_test');

        $config = $this->model_extension_payment_afterpay->getAfterpayConfiguration();
        
        if ($config) {
            $store_country_code = $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']);

            if ($store_country_code == 'GB') {
                $payment_method = 'Clearpay';
                $data['text_afterpay_info'] = $this->language->get('text_clearpay_confirm');
                $data['terms_href'] = 'https://www.clearpay.co.uk/en-GB/terms-of-service';

                $brand = 'clearpay';
                $brand_title = 'Clearpay';

                $data['afterpay_logo'] = str_ireplace('{{brand}}', $brand, $this->language->get('text_afterpay_header'));
                $data['afterpay_logo'] = str_ireplace('{{brandtitle}}', $brand_title, $data['afterpay_logo']);
            } elseif ($store_country_code == 'FR' || $store_country_code == 'IT' || $store_country_code == 'ES') {
                $payment_method = 'Clearpay';
                $data['text_afterpay_info'] = $this->language->get('text_clearpay_confirm_' . $store_country_code);

                if ($store_country_code == 'FR') {
                    $data['terms_href'] = 'https://www.clearpay.com/fr/terms';
                } elseif ($store_country_code == 'IT') {
                    $data['terms_href'] = 'https://www.clearpay.com/it/terms';
                } elseif ($store_country_code == 'ES') {
                    $data['terms_href'] = 'https://www.clearpay.com/es/terms';
                }

                $brand = 'clearpay';
                $brand_title = 'Clearpay';

                $data['afterpay_logo'] = str_ireplace('{{brand}}', $brand, $this->language->get('text_afterpay_header'));
                $data['afterpay_logo'] = str_ireplace('{{brandtitle}}', $brand_title, $data['afterpay_logo']);
            } else {
                $payment_method = 'Afterpay';

                $brand = 'afterpay';
                $brand_title = 'Afterpay';

                $data['afterpay_logo'] = str_ireplace('{{brand}}', $brand, $this->language->get('text_afterpay_header'));
                $data['afterpay_logo'] = str_ireplace('{{brandtitle}}', $brand_title, $data['afterpay_logo']);

                if ($store_country_code == 'AU') {
                    $data['text_afterpay_info'] = sprintf($this->language->get('text_afterpay_confirm'), $this->language->get('text_au_or_nz'));
                    $data['terms_href'] = 'https://www.afterpay.com/en-AU/terms-of-service';
                } elseif ($store_country_code == 'NZ') {
                    $data['text_afterpay_info'] = sprintf($this->language->get('text_afterpay_confirm'), $this->language->get('text_au_or_nz'));
                    $data['terms_href'] = 'https://www.afterpay.com/en-NZ/terms-of-service';
                } elseif ($store_country_code == 'CA') {
                    $data['text_afterpay_info'] = sprintf($this->language->get('text_afterpay_confirm'), $this->language->get('text_ca'));
                    $data['terms_href'] = 'https://www.afterpay.com/en-CA/instalment-agreement';
                } else {
                    $data['text_afterpay_info'] = sprintf($this->language->get('text_afterpay_confirm'), $this->language->get('text_us'));
                    $data['terms_href'] = 'https://www.afterpay.com/installment-agreement';
                }
            }

            $this->model_extension_payment_afterpay->editOrderPayment($this->session->data['order_id'], $payment_method);

            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            $data['afterpay_locale'] = $this->model_extension_payment_afterpay->getLocale($config['currency_code']);
            $data['afterpay_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
            $data['afterpay_currency'] = $config['currency_code'];

            if ($store_country_code == 'FR') {
                $data['text_installment'] = sprintf($this->language->get('text_installment_FR'), $this->currency->format($order_info['total'] / 4, $order_info['currency_code'], $order_info['currency_value'], true));
            } elseif ($store_country_code == 'IT') {
                $data['text_installment'] = sprintf($this->language->get('text_installment_IT'), $this->currency->format($order_info['total'] / 4, $order_info['currency_code'], $order_info['currency_value'], true));
            } elseif ($store_country_code == 'ES') {
                $data['text_installment'] = sprintf($this->language->get('text_installment_ES'), $this->currency->format($order_info['total'] / 4, $order_info['currency_code'], $order_info['currency_value'], true));
            } else {
                $data['text_installment'] = sprintf($this->language->get('text_installment'), $this->currency->format($order_info['total'] / 4, $order_info['currency_code'], $order_info['currency_value'], true));
            }

        }

        $data['continue'] = $this->url->link('extension/payment/afterpay/checkout', '', true);

        return $this->load->view('extension/payment/afterpay', $data);
    }

    public function checkout() {
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->response->redirect($this->url->link('checkout/cart'));
        }

        $this->load->model('extension/payment/afterpay');
        $this->load->model('checkout/order');
        $this->load->model('localisation/country');

        $this->load->language('extension/payment/afterpay');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if ($order_info) {
            $this->load->model('tool/image');

            $payment_country_info = $this->model_localisation_country->getCountry($order_info['payment_country_id']);
            $shipping_country_info = $this->model_localisation_country->getCountry($order_info['shipping_country_id']);

            if ($shipping_country_info) {
                $billing = array(
                    'name' => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
                    'line1' => $order_info['payment_address_1'],
                    'line2' => $order_info['payment_address_2'],
                    'area1' => $order_info['payment_city'],
                    'region' => $order_info['payment_zone'],
                    'postcode' => $order_info['payment_postcode'],
                    'countryCode' => $payment_country_info['iso_code_2']
                );

                $shipping = array(
                    'name' => $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'],
                    'line1' => $order_info['shipping_address_1'],
                    'line2' => $order_info['shipping_address_2'],
                    'area1' => $order_info['shipping_city'],
                    'region' => $order_info['shipping_zone'],
                    'postcode' => $order_info['shipping_postcode'],
                    'countryCode' => $shipping_country_info['iso_code_2']
                );
            } else {
                $billing = $shipping = array(
                    'name' => $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'],
                    'line1' => $order_info['payment_address_1'],
                    'line2' => $order_info['payment_address_2'],
                    'area1' => $order_info['payment_city'],
                    'region' => $order_info['payment_zone'],
                    'postcode' => $order_info['payment_postcode'],
                    'countryCode' => $payment_country_info['iso_code_2']
                );
            }

            $order_products = array();

            foreach ($this->cart->getProducts() as $product) {
                $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

                if ($product['image']) {
                    $image = $this->model_tool_image->resize($product['image'], 500, 500);
                } else {
                    $image = '';
                }

                $order_products[] = array(
                    'name'      => htmlspecialchars_decode($product['name']),
                    'sku'       => $product['model'],
                    'quantity'  => $product['quantity'],
                    'pageUrl'   => $this->url->link('product/product', 'product_id=' . $product['product_id']),
                    'imageUrl'  => $image,
                    'price'     => array(
                          'amount'   => $this->currency->format($unit_price, $order_info['currency_code'], $order_info['currency_value'], false),
                          'currency' => $order_info['currency_code']
                    )
                );
            }

            $createCheckoutRequest = new AfterpayCreateCheckoutRequest([
                'purchaseCountry' => $payment_country_info['iso_code_2'],
                'amount' => [ 
                    $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false), 
                    $order_info['currency_code']
                ],
                'consumer' => [
                    'phoneNumber' => $order_info['telephone'],
                    'givenNames'  => $order_info['firstname'],
                    'surname'     => $order_info['lastname'],
                    'email'       => $order_info['email']
                ],
                'billing'  => $billing,
                'shipping' => $shipping,
                'items'    => $order_products,
                'merchant' => [
                    'redirectConfirmUrl' => $this->url->link('extension/payment/afterpay/callback', '', true),
                    'redirectCancelUrl'  => $this->url->link('checkout/checkout', '', true)
                ],
                'merchantReference' => $order_info['order_id'] . '-' . time(),
            ]);
            
            if ($createCheckoutRequest->setMerchantAccount($this->getMerchantAccount())->send()) {
                $response = $createCheckoutRequest->getResponse()->getParsedBody();

                if (isset($response->redirectCheckoutUrl)) { 
                    $this->response->redirect($response->redirectCheckoutUrl);
                } else {
                    $this->session->data['error'] = $this->language->get('error_unknown_response');

                    $this->response->redirect($this->url->link('checkout/checkout', '', true));
                }
            } else {
                $error = $createCheckoutRequest->getResponse()->getParsedBody();

                if (isset($error->message)) {
                    $this->session->data['error'] = sprintf($this->language->get('error_message'), $error->message);
                } else {
                    $this->session->data['error'] = $this->language->get('error_unknown_response');
                }

                $this->response->redirect($this->url->link('checkout/checkout', '', true));
            }
        } else {
            $this->response->redirect($this->url->link('checkout/cart'));
        }
    }

    public function expressCheckout() {
        $json = array();

        $amount = 0.0;
        $popup_origin_url = '';

        $this->load->model('extension/payment/afterpay');

        $this->load->language('extension/payment/afterpay');

        if (isset($this->request->post['product_id'])) {
            $this->load->model('catalog/product');
          
            $this->load->language('checkout/cart');
            $product_info = $this->model_catalog_product->getProduct($this->request->post['product_id']);

            if ($product_info) {
                $popup_origin_url = $this->url->link('product/product', 'product_id=' . $this->request->post['product_id'], true);

                if (isset($this->request->post['quantity']) && ((int)$this->request->post['quantity'] >= $product_info['minimum'])) {
                    $quantity = (int)$this->request->post['quantity'];
                } else {
                    $quantity = $product_info['minimum'] ? $product_info['minimum'] : 1;
                }

                if (isset($this->request->post['option'])) {
                    $option = array_filter($this->request->post['option']);
                } else {
                    $option = array();
                }
          
                $product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);
          
                foreach ($product_options as $product_option) {
                    if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                        $json['error'] =  $this->language->get('error_product_option');

                        break;
                    }
                }

                if (isset($this->request->post['recurring_id'])) {
                    $recurring_id = $this->request->post['recurring_id'];
                } else {
                    $recurring_id = 0;
                }
          
                $recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);
          
                if ($recurrings) {
                    $recurring_ids = array();
            
                    foreach ($recurrings as $recurring) {
                        $recurring_ids[] = $recurring['recurring_id'];
                    }
            
                    if (!in_array($recurring_id, $recurring_ids)) {
                        $json['error'] = $this->language->get('error_recurring_required');
                    }
                }

                if ((!$json)) {
                    $this->cart->add($this->request->post['product_id'], $quantity, $option, $recurring_id);

                    if (!$this->cart->hasStock() && (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning'))) {
                        $json['error'] = $this->language->get('error_product_stock');
                    } else {
                        $amount = $this->getCartTotal();
                    }
                }
            }
        } else {
            $popup_origin_url = $this->url->link('checkout/cart', '', true);
            $amount = $this->getCartTotal();
        }

        $cart_products = array();
        
        $this->load->model('tool/image');

        foreach ($this->cart->getProducts() as $product) {
            $unit_price = $this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

            if ($product['image']) {
                $image = $this->model_tool_image->resize($product['image'], 500, 500);
            } else {
                $image = '';
            }

            $cart_products[] = array(
                'name'      => htmlspecialchars($product['name']),
                'sku'       => $product['model'],
                'quantity'  => $product['quantity'],
                'pageUrl'   => $this->url->link('product/product', 'product_id=' . $product['product_id']),
                'imageUrl'  => $image,
                'price'     => array(
                      'amount'   => (string)$this->currency->format($unit_price, $this->session->data['currency'], '', false),
                      'currency' => $this->session->data['currency']
                )
            );
        }

        if (!$json) {
            $curl_data = array(
                'amount' => [
                    'amount' => (string)$this->currency->format($amount, $this->session->data['currency'], '', false),
                    'currency' => $this->session->data['currency']
                ],
                'mode' => 'express',
                'merchant' => [
                    'popupOriginUrl' => $popup_origin_url
                ],
                'items'    => $cart_products,
            );

            $result = $this->generateCurlRequest('/v2/checkouts', $curl_data);

            if ($result && isset($result['token'])) {
                $json['token'] = $result['token'];
                $json['success'] = true;
            } elseif (isset($result['errorCode']) && isset($result['message'])) {
                if ($result['errorCode'] == 'unsupported_payment_type') {
                    $config = $this->model_extension_payment_afterpay->getAfterpayConfiguration();

                    if ($config) {
                        if ($config['currency_code'] == 'GBP') {
                            $payment_name = 'Clearpay';
                        } else {
                            $payment_name = 'Afterpay';
                        }

                        $min_amount = $this->currency->format($config['minimum_amount'], $this->session->data['currency'], '', true);
                        $max_amount = $this->currency->format($config['maximum_amount'], $this->session->data['currency'], '', true);

                        $json['error'] = sprintf($this->language->get('error_amount'), $payment_name, $min_amount, $max_amount);
                    } else {
                        $json['error'] = sprintf($this->language->get('error_message'), $result['message']);
                    }
                } else {
                    $json['error'] = sprintf($this->language->get('error_message'), $result['message']);
                }
            } else {
                $json['error'] = $this->language->get('error_unknown_response');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function expressCheckoutShippingAddress() {
        $json = array();

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $this->load->model('extension/payment/afterpay');

            unset($this->session->data['shipping_address']);
            unset($this->session->data['shipping_method']);
            unset($this->session->data['shipping_methods']);

            $this->session->data['afterpay']['customer_name'] = !empty($this->request->post['name']) ? $this->request->post['name'] : '';
            $this->session->data['afterpay']['telephone'] = !empty($this->request->post['phoneNumber']) ? $this->request->post['phoneNumber'] : '';

            $this->session->data['shipping_address']['firstname'] = !empty($this->request->post['name']) ? $this->request->post['name'] : '';
            $this->session->data['shipping_address']['lastname'] = '';
            $this->session->data['shipping_address']['company'] = '';
            $this->session->data['shipping_address']['address_1'] = !empty($this->request->post['address1']) ? $this->request->post['address1'] : '';
            $this->session->data['shipping_address']['address_2'] = !empty($this->request->post['address2']) ? $this->request->post['address2'] : '';
            $this->session->data['shipping_address']['postcode'] = !empty($this->request->post['postcode']) ? $this->request->post['postcode'] : '';
            $this->session->data['shipping_address']['city'] = !empty($this->request->post['suburb']) ? $this->request->post['suburb'] : '';
            $this->session->data['shipping_address']['custom_field'] = array();

            if (!empty($this->request->post['area2'])) {
                if (!empty($this->session->data['shipping_address']['address_2'])) {
                    $this->session->data['shipping_address']['address_2'] .= ' ' . $this->request->post['area2'];
                } else {
                    $this->session->data['shipping_address']['address_2'] = $this->request->post['area2'];
                }
            }

            if (!empty($this->request->post['countryCode']) && !empty($this->request->post['state'])) {
                $country_id = $this->model_extension_payment_afterpay->getCountryIdByIsoCode2($this->request->post['countryCode']);
                $zone_id = $this->model_extension_payment_afterpay->getZoneIdByCode(trim($this->request->post['state']));

                $this->session->data['shipping_address']['country_id'] = $country_id;
                $this->session->data['shipping_address']['zone_id'] = $zone_id;

                $this->load->model('localisation/country');

                $country_info = $this->model_localisation_country->getCountry($country_id);

                if ($country_info) {
                    $this->session->data['shipping_address']['country'] = $country_info['name'];
                    $this->session->data['shipping_address']['iso_code_2'] = $country_info['iso_code_2'];
                    $this->session->data['shipping_address']['iso_code_3'] = $country_info['iso_code_3'];
                    $this->session->data['shipping_address']['address_format'] = $country_info['address_format'];
                } else {
                    $this->session->data['shipping_address']['country'] = '';
                    $this->session->data['shipping_address']['iso_code_2'] = '';
                    $this->session->data['shipping_address']['iso_code_3'] = '';
                    $this->session->data['shipping_address']['address_format'] = '';
                }

                $this->load->model('localisation/zone');

                $zone_info = $this->model_localisation_zone->getZone($zone_id);

                if ($zone_info) {
                    $this->session->data['shipping_address']['zone'] = $zone_info['name'];
                    $this->session->data['shipping_address']['zone_code'] = $zone_info['code'];
                } else {
                    $this->session->data['shipping_address']['zone'] = '';
                    $this->session->data['shipping_address']['zone_code'] = '';
                }
            } else {
                $this->session->data['shipping_address']['country_id'] = 0;
                $this->session->data['shipping_address']['zone_id'] = 0;

                $this->session->data['shipping_address']['country'] = '';
                $this->session->data['shipping_address']['iso_code_2'] = '';
                $this->session->data['shipping_address']['iso_code_3'] = '';
                $this->session->data['shipping_address']['address_format'] = '';

                $this->session->data['shipping_address']['zone'] = '';
                $this->session->data['shipping_address']['zone_code'] = '';
            }

            // Get Shipping Methods
            $method_data = array();

            if ($this->cart->hasShipping()) {
                $this->load->model('setting/extension');

                $results = $this->model_setting_extension->getExtensions('shipping');
          
                foreach ($results as $result) {
                  if ($this->config->get('shipping_' . $result['code'] . '_status')) {
                    $this->load->model('extension/shipping/' . $result['code']);
          
                    $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);
          
                    if ($quote) {
                      $method_data[$result['code']] = array(
                        'title'      => $quote['title'],
                        'quote'      => $quote['quote'],
                        'sort_order' => $quote['sort_order'],
                        'error'      => $quote['error']
                      );
                    }
                  }
                }
          
                $sort_order = array();
          
                foreach ($method_data as $key => $value) {
                  $sort_order[$key] = $value['sort_order'];
                }
          
                array_multisort($sort_order, SORT_ASC, $method_data);

                $this->session->data['shipping_methods'] = $method_data;

                foreach ($method_data as $shipping_method) {
                    foreach ($shipping_method['quote'] as $quote) {
                        $taxed_shipping_cost = $this->tax->calculate($quote['cost'], $quote['tax_class_id'], $this->config->get('config_tax'));
                        $shipping_cost = $this->currency->format($taxed_shipping_cost, $this->session->data['currency'], '', false);
                        $order_amount = $this->currency->format($this->getCartTotal() + $taxed_shipping_cost, $this->session->data['currency'], '', false);

                        $json[] = array(
                            'id'             => $quote['code'],
                            'name'           => $shipping_method['title'],
                            'description'    => $quote['title'],
                            'shippingAmount' => [
                                'amount'   => (string)$shipping_cost,
                                'currency' => $this->session->data['currency']
                            ],
                            'orderAmount'    => [
                                'amount'   => (string)$order_amount,
                                'currency' => $this->session->data['currency']
                            ]
                        );
                    }
                }
            } else {
                $json[] = array(
                    'id'             => 'noshipping',
                    'name'           => 'No Shipping Required',
                    'description'    => 'No shipping required for the product(s) in cart.',
                    'shippingAmount' => [
                        'amount'   => '0',
                        'currency' => $this->session->data['currency']
                    ],
                    'orderAmount'    => [
                        'amount'   => (string)$this->currency->format($this->getCartTotal(), $this->session->data['currency'], '', false),
                        'currency' => $this->session->data['currency']
                    ]
                );
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function expressCheckoutUpdateShippingMethod() {
        if (!empty($this->request->post['name']) && !empty($this->request->post['id']) && $this->cart->hasShipping()) {
            $shipping = explode('.', $this->request->post['id']);
            
            $this->session->data['shipping_method'] = $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]];
        }
    }

    public function expressCheckoutComplete() {
        $json = array();

        $this->load->model('extension/payment/afterpay');

        $this->load->language('extension/payment/afterpay');

        if (isset($this->request->post['status']) && $this->request->post['status'] == 'SUCCESS' && isset($this->request->post['orderToken'])) {
            // Add Order
            $order_data = array();

            $totals = array();
            $taxes = $this->cart->getTaxes();
            $total = 0;
      
            // Because __call can not keep var references so we put them into an array.
            $total_data = array(
                'totals' => &$totals,
                'taxes'  => &$taxes,
                'total'  => &$total
            );
      
            $this->load->model('setting/extension');
      
            $sort_order = array();
      
            $results = $this->model_setting_extension->getExtensions('total');
      
            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
            }
      
            array_multisort($sort_order, SORT_ASC, $results);
      
            foreach ($results as $result) {
                if ($this->config->get('total_' . $result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);
          
                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }
      
            $sort_order = array();
      
            foreach ($totals as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }
      
            array_multisort($sort_order, SORT_ASC, $totals);
      
            $order_data['totals'] = $totals;
      
            $this->load->language('checkout/checkout');
      
            $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
            $order_data['store_id'] = $this->config->get('config_store_id');
            $order_data['store_name'] = $this->config->get('config_name');

            if ($order_data['store_id']) {
                $order_data['store_url'] = $this->config->get('config_url');
            } else {
                if ($this->request->server['HTTPS']) {
                    $order_data['store_url'] = HTTPS_SERVER;
                } else {
                    $order_data['store_url'] = HTTP_SERVER;
                }
            }

            if ($this->customer->isLogged()) {
                $this->load->model('account/customer');
        
                $customer_info = $this->model_account_customer->getCustomer($this->customer->getId());
        
                $order_data['customer_id'] = $this->customer->getId();
                $order_data['customer_group_id'] = $customer_info['customer_group_id'];
                $order_data['firstname'] = $customer_info['firstname'];
                $order_data['lastname'] = $customer_info['lastname'];
                $order_data['email'] = $customer_info['email'];
                $order_data['telephone'] = !empty($this->session->data['afterpay']['telephone']) ? $this->session->data['afterpay']['telephone'] : $customer_info['telephone'];
                $order_data['fax'] = $customer_info['fax'];
                $order_data['custom_field'] = json_decode($customer_info['custom_field'], true);
            } else {
                $order_data['customer_id'] = 0;
                $order_data['customer_group_id'] = $this->config->get('config_customer_group_id');
                $order_data['firstname'] = !empty($this->session->data['afterpay']['customer_name']) ? $this->session->data['afterpay']['customer_name'] : '';
                $order_data['lastname'] = '';
                $order_data['email'] = '';
                $order_data['telephone'] = !empty($this->session->data['afterpay']['telephone']) ? $this->session->data['afterpay']['telephone'] : '';
                $order_data['fax'] = '';
                $order_data['custom_field'] = array();
            }

            $order_data['payment_firstname'] = $this->session->data['shipping_address']['firstname'];
            $order_data['payment_lastname'] = $this->session->data['shipping_address']['lastname'];
            $order_data['payment_company'] = $this->session->data['shipping_address']['company'];
            $order_data['payment_address_1'] = $this->session->data['shipping_address']['address_1'];
            $order_data['payment_address_2'] = $this->session->data['shipping_address']['address_2'];
            $order_data['payment_city'] = $this->session->data['shipping_address']['city'];
            $order_data['payment_postcode'] = $this->session->data['shipping_address']['postcode'];
            $order_data['payment_zone'] = $this->session->data['shipping_address']['zone'];
            $order_data['payment_zone_id'] = $this->session->data['shipping_address']['zone_id'];
            $order_data['payment_country'] = $this->session->data['shipping_address']['country'];
            $order_data['payment_country_id'] = $this->session->data['shipping_address']['country_id'];
            $order_data['payment_address_format'] = $this->session->data['shipping_address']['address_format'];
            $order_data['payment_custom_field'] = array();

            $config = $this->model_extension_payment_afterpay->getAfterpayConfiguration();

            $order_data['payment_method'] = 'Afterpay Express';
            $order_data['payment_code'] = 'afterpay';
    
            if ($config) {
                $store_country_code = $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']);
    
                if ($store_country_code == 'GB') {
                    $order_data['payment_method'] = 'Clearpay Express';
                }
            }

            $order_data['shipping_firstname'] = $this->session->data['shipping_address']['firstname'];
            $order_data['shipping_lastname'] = $this->session->data['shipping_address']['lastname'];
            $order_data['shipping_company'] = $this->session->data['shipping_address']['company'];
            $order_data['shipping_address_1'] = $this->session->data['shipping_address']['address_1'];
            $order_data['shipping_address_2'] = $this->session->data['shipping_address']['address_2'];
            $order_data['shipping_city'] = $this->session->data['shipping_address']['city'];
            $order_data['shipping_postcode'] = $this->session->data['shipping_address']['postcode'];
            $order_data['shipping_zone'] = $this->session->data['shipping_address']['zone'];
            $order_data['shipping_zone_id'] = $this->session->data['shipping_address']['zone_id'];
            $order_data['shipping_country'] = $this->session->data['shipping_address']['country'];
            $order_data['shipping_country_id'] = $this->session->data['shipping_address']['country_id'];
            $order_data['shipping_address_format'] = $this->session->data['shipping_address']['address_format'];
            $order_data['shipping_custom_field'] = array();

            if (isset($this->session->data['shipping_method']['title'])) {
                $order_data['shipping_method'] = $this->session->data['shipping_method']['title'];
            } else {
                $order_data['shipping_method'] = '';
            }
    
            if (isset($this->session->data['shipping_method']['code'])) {
                $order_data['shipping_code'] = $this->session->data['shipping_method']['code'];
            } else {
                $order_data['shipping_code'] = '';
            }

            $order_data['products'] = array();

            foreach ($this->cart->getProducts() as $product) {
                $option_data = array();
        
                foreach ($product['option'] as $option) {
                    $option_data[] = array(
                        'product_option_id'       => $option['product_option_id'],
                        'product_option_value_id' => $option['product_option_value_id'],
                        'option_id'               => $option['option_id'],
                        'option_value_id'         => $option['option_value_id'],
                        'name'                    => $option['name'],
                        'value'                   => $option['value'],
                        'type'                    => $option['type']
                    );
                }
      
                $order_data['products'][] = array(
                    'product_id' => $product['product_id'],
                    'name'       => $product['name'],
                    'model'      => $product['model'],
                    'option'     => $option_data,
                    'download'   => $product['download'],
                    'quantity'   => $product['quantity'],
                    'subtract'   => $product['subtract'],
                    'price'      => $product['price'],
                    'total'      => $product['total'],
                    'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
                    'reward'     => $product['reward']
                );
            }

            // Gift Voucher
            $order_data['vouchers'] = array();
      
            if (!empty($this->session->data['vouchers'])) {
                foreach ($this->session->data['vouchers'] as $voucher) {
                    $order_data['vouchers'][] = array(
                        'description'      => $voucher['description'],
                        'code'             => token(10),
                        'to_name'          => $voucher['to_name'],
                        'to_email'         => $voucher['to_email'],
                        'from_name'        => $voucher['from_name'],
                        'from_email'       => $voucher['from_email'],
                        'voucher_theme_id' => $voucher['voucher_theme_id'],
                        'message'          => $voucher['message'],
                        'amount'           => $voucher['amount']
                    );
                }
            }

            $order_data['comment'] = '';
            $order_data['total'] = $total_data['total'];

            if (isset($this->request->cookie['tracking'])) {
                $order_data['tracking'] = $this->request->cookie['tracking'];
        
                $subtotal = $this->cart->getSubTotal();
        
                // Affiliate
                $this->load->model('affiliate/affiliate');
        
                $affiliate_info = $this->model_affiliate_affiliate->getAffiliateByCode($this->request->cookie['tracking']);
        
                if ($affiliate_info) {
                    $order_data['affiliate_id'] = $affiliate_info['affiliate_id'];
                    $order_data['commission'] = ($subtotal / 100) * $affiliate_info['commission'];
                } else {
                    $order_data['affiliate_id'] = 0;
                    $order_data['commission'] = 0;
                }
        
                // Marketing
                $this->load->model('checkout/marketing');
        
                $marketing_info = $this->model_checkout_marketing->getMarketingByCode($this->request->cookie['tracking']);
        
                if ($marketing_info) {
                    $order_data['marketing_id'] = $marketing_info['marketing_id'];
                } else {
                    $order_data['marketing_id'] = 0;
                }
            } else {
                $order_data['affiliate_id'] = 0;
                $order_data['commission'] = 0;
                $order_data['marketing_id'] = 0;
                $order_data['tracking'] = '';
            }

            $order_data['language_id'] = $this->config->get('config_language_id');
            $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
            $order_data['currency_code'] = $this->session->data['currency'];
            $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
            $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

            if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
                $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
            } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
                $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
            } else {
                $order_data['forwarded_ip'] = '';
            }
      
            if (isset($this->request->server['HTTP_USER_AGENT'])) {
                $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
            } else {
                $order_data['user_agent'] = '';
            }
      
            if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
              $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
            } else {
              $order_data['accept_language'] = '';
            }

            $this->load->model('checkout/order');
      
            $this->session->data['order_id'] = $this->model_checkout_order->addOrder($order_data);

            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            if ($order_info) {
                $curl_data = array(
                    'token'             => $this->request->post['orderToken'],
                    'amount'            => [
                        'amount'   => (string)$this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
                        'currency' => $order_info['currency_code']
                    ],
                    'merchantReference' => $order_info['order_id'] . '-' . time()
                );

                $result = $this->generateCurlRequest('/v2/payments/capture', $curl_data);

                if (!empty($result['id']) && !empty($result['status'])) {
                      $email = $result['orderDetails']['consumer']['email'];

                      $this->model_extension_payment_afterpay->updateExpressOrderEmail($this->session->data['order_id'], $email);

                      $order_info['afterpay_reference_id'] = $result['id'];

                      $afterpay_order_id = $this->model_extension_payment_afterpay->addOrder($order_info);

                      $order_status_id = $this->config->get('config_order_status_id');

                      $success = false;

                      switch($result['status']) {
                          case 'APPROVED':
                              $status = 'approved';
                              $success = true;
      
                              $order_status_id = $this->config->get('payment_afterpay_approved_status_id');
                              break;
                          case 'DECLINED':
                              $status = 'declined';
                              $success = false;
                              
                              $order_status_id = $this->config->get('payment_afterpay_declined_status_id');
                              break;
                      }
      
                      if (isset($result['originalAmount']['amount'])) {
                          $amount = $result['originalAmount']['amount'];
                      } else {
                          $amount = 0;
                      }
      
                      $this->model_extension_payment_afterpay->addTransaction($afterpay_order_id, $status, $amount);
      
                      // Do not change status for cancelled orders with order status ID 0
                      if (((!$order_info['order_status_id']) || $order_info['order_status_id']) && $success) {
                          $this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $order_status_id);
      
                          $json['redirect'] = $this->url->link('checkout/success');
                      } else {
                          $json['error'] = $this->language->get('error_unsuccessful');
                      }
                } else {
                    if (isset($result['message'])) {
                        $json['error'] = sprintf($this->language->get('error_message'), $result['message']);
                    } else {
                        $json['error'] = $this->language->get('error_unknown_response');
                    }
                }
            }
        } else {
            $json['error'] = $this->language->get('error_unknown_response');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getCartTotal() {
        // Totals
        $this->load->model('setting/extension');

        $totals = array();
        $taxes = $this->cart->getTaxes();
        $total = 0;

        $total_data = array(
            'totals' => &$totals,
            'taxes'  => &$taxes,
            'total'  => &$total
        );

        // Display prices
        $sort_order = array();

        $results = $this->model_setting_extension->getExtensions('total');

        foreach ($results as $key => $value) {
            $sort_order[$key] = $this->config->get('total_' . $value['code'] . '_sort_order');
        }

        array_multisort($sort_order, SORT_ASC, $results);

        foreach ($results as $result) {
            if ($this->config->get('total_' . $result['code'] . '_status')) {
                $this->load->model('extension/total/' . $result['code']);
                
                // We have to put the totals in an array so that they pass by reference.
                $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
            }
        }

        $sort_order = array();

        foreach ($totals as $key => $value) {
            $sort_order[$key] = $value['sort_order'];
        }

        array_multisort($sort_order, SORT_ASC, $totals);

        $afterpay_price = $this->currency->format($total, $this->session->data['currency'], '', false);

        return $afterpay_price;
    }
    
    public function callback() {
        $this->load->model('checkout/order');
        $this->load->model('extension/payment/afterpay');

        $this->load->language('extension/payment/afterpay');

        if (isset($this->request->get['status']) && $this->request->get['status'] == 'SUCCESS' && isset($this->request->get['orderToken'])) {
            $immediatePaymentCaptureRequest = new AfterpayImmediatePaymentCaptureRequest([
                'token' => $this->request->get['orderToken']
            ]);
        
            if ($immediatePaymentCaptureRequest->setMerchantAccount($this->getMerchantAccount())->send()) {
                $response = $immediatePaymentCaptureRequest->getResponse()->getParsedBody();

                $order_id = explode('-', $response->merchantReference)[0];

                $order_info = $this->model_checkout_order->getOrder($order_id);

                if ($order_info) {
                    $order_info['afterpay_reference_id'] = $response->id;

                    $afterpay_order_id = $this->model_extension_payment_afterpay->addOrder($order_info);

                    $order_status_id = $this->config->get('config_order_status_id');

                    $success = false;

                    switch($response->status) {
                        case 'APPROVED':
                            $status = 'approved';
                            $success = true;

                            $order_status_id = $this->config->get('payment_afterpay_approved_status_id');
                            break;
                        case 'DECLINED':
                            $status = 'declined';
                            $success = false;
                            
                            $order_status_id = $this->config->get('payment_afterpay_declined_status_id');
                            break;
                    }

                    if (isset($response->originalAmount->amount)) {
                        $amount = $response->originalAmount->amount;
                    } else {
                        $amount = 0;
                    }

                    $this->model_extension_payment_afterpay->addTransaction($afterpay_order_id, $status, $amount);

                    // Do not change status for cancelled orders with order status ID 0
                    if (((!$order_info['order_status_id']) || $order_info['order_status_id']) && $success) {
                        $this->model_checkout_order->addOrderHistory($order_id, $order_status_id);

                        $this->response->redirect($this->url->link('checkout/success'));
                    } else {
                        $this->session->data['error'] = $this->language->get('error_payment_failed');

                        $this->response->redirect($this->url->link('checkout/checkout'));
                    }
                }
            } else {
                $error = $immediatePaymentCaptureRequest->getResponse()->getParsedBody();

                if (isset($error->message)) {
                    $this->session->data['error'] = sprintf($this->language->get('error_message'), $error->message);
                } else {
                    $this->session->data['error'] = $this->language->get('error_unknown_response');
                }
            }
        }
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

    private function generateCurlRequest($api, $curl_data) {
        $curl_url = $this->model_extension_payment_afterpay->getApiEnvironment() . $api;
        
        $user_agent_str = 'Equotix/' . $this->version . ' (OpenCart/v' . VERSION . '; PHP/' . phpversion() . '; cURL/' . curl_version()['version'] . '; Merchant/' . $this->config->get('payment_afterpay_merchant_id') . ') ' . HTTPS_SERVER;
        
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: ' . $user_agent_str
        );
        
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $curl_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_data));
        curl_setopt($curl, CURLOPT_USERPWD, $this->config->get('payment_afterpay_merchant_id') . ":" . $this->config->get('payment_afterpay_secret_key'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        $result = curl_exec($curl);
        $result = json_decode($result, true);

        curl_close($curl);

        return $result;
    }
    
    public function eventPostViewCommonHeader($route, &$data, &$output) {
        if ($this->config->get('payment_afterpay_status')) {
            $this->load->model('extension/payment/afterpay');

            $config = $this->model_extension_payment_afterpay->getAfterpayConfiguration();

            if ($config && $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']) && $this->session->data['currency'] == $config['currency_code']) {
                $html = '<script src="https://js.afterpay.com/afterpay-1.x.js" data-min="' . $config['minimum_amount'] . '" data-max="' . $config['maximum_amount'] . '" async ></script>';
                
                $output = str_replace('</head>', $html . '</head>', $output);
            }
        }
    }
    
    public function eventPostViewCheckoutCart($route, &$data, &$output) {
        if ($this->config->get('payment_afterpay_status')) {
            $this->load->model('extension/payment/afterpay');

            $config = $this->model_extension_payment_afterpay->getAfterpayConfiguration();
            
            if ($config && $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']) && $this->model_extension_payment_afterpay->checkCategoriesRestriction() && $this->session->data['currency'] == $config['currency_code']) {
                $afterpay_locale = $this->model_extension_payment_afterpay->getLocale($config['currency_code']);
                $afterpay_badge_theme = $this->config->get('payment_afterpay_badge_theme') ? $this->config->get('payment_afterpay_badge_theme') : 'black-on-mint';
                
                $afterpay_price = $this->currency->format($this->getCartTotal(), $this->session->data['currency'], '', false);
            
                $html = '<div class="clearfix"><div class="pull-right">';
                // $html .= '<afterpay-placement data-cart-is-eligible="true" data-locale="' . $afterpay_locale . '" data-amount="' . $afterpay_price . '" data-badge-theme="' . $afterpay_badge_theme . '" data-size="md"></afterpay-placement>';
                $html .=  '</div></div>';

                // Adding Afterpay Express Button
                if (false && $this->config->get('payment_afterpay_express_checkout_status') && $afterpay_price >= $config['minimum_amount'] && $afterpay_price <= $config['maximum_amount']) {
                    $store_country_code = $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']);

                    if ($store_country_code == 'GB' || $store_country_code == 'ES' || $store_country_code == 'FR' || $store_country_code == 'IT') {
                        $path = 'clearpay';
                    } else {
                        $path = 'afterpay';
                    }

                    $html .= '<div class="buttons clearfix"><div class="pull-right">';
                    $html .= '<button id="afterpay-express-button" data-afterpay-entry-point="cart" class="cart-page"><img src="catalog/view/theme/default/image/' . $path . '/checkout_button_black-on-mint.svg" /></button>';
                    $html .= '</div></div><div class="buttons clearfix">';
                } else {
                    $html .= '<div class="buttons clearfix">';
                }
                
                $output = str_replace('<div class="buttons clearfix">', $html, $output);
                
                if (false && $this->config->get('payment_afterpay_express_checkout_status') && $afterpay_price >= $config['minimum_amount'] && $afterpay_price <= $config['maximum_amount']) {
                    $afterpay_data['country_code'] = $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']);
                    
                    // Adding Afterpay Express JS
                    if ($this->config->get('payment_afterpay_test')) {
                        if ($afterpay_data['country_code'] == 'GB') {
                            $afterpay_data['afterpay_js_href'] = 'https://portal.sandbox.clearpay.co.uk/afterpay.js?merchant_key=opencart';
                        } else {
                            $afterpay_data['afterpay_js_href'] = 'https://portal.sandbox.afterpay.com/afterpay.js?merchant_key=opencart';
                        }
                    } else {
                        if ($afterpay_data['country_code'] == 'GB') {
                            $afterpay_data['afterpay_js_href'] = 'https://portal.clearpay.co.uk/afterpay.js?merchant_key=opencart';
                        } else {
                            $afterpay_data['afterpay_js_href'] = 'https://portal.afterpay.com/afterpay.js?merchant_key=opencart';
                        }
                    }

                    $html = $this->load->view('extension/payment/afterpay_express', $afterpay_data);

                    $html .= '</head>';
                    $output = str_replace('</head>', $html, $output);
                }
            }
        }
    }
    
    public function eventPreViewExtensionModule($route, &$data) {
        if ($this->config->get('payment_afterpay_status')) {
            $this->load->model('extension/payment/afterpay');

            $config = $this->model_extension_payment_afterpay->getAfterpayConfiguration();
            
            if ($config && $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']) && $this->session->data['currency'] == $config['currency_code'] && $this->config->get('payment_afterpay_product_listing')) {
                $afterpay_min = $config['minimum_amount'];
                $afterpay_max = $config['maximum_amount'];
                $afterpay_badge_theme = $this->config->get('payment_afterpay_badge_theme') ? $this->config->get('payment_afterpay_badge_theme') : 'black-on-mint';
                $afterpay_locale = $this->model_extension_payment_afterpay->getLocale($config['currency_code']);
                
                foreach ($data['products'] as $key => $product) {
                    $afterpay_status = false;
                    $afterpay_price = 0;

                    $product_info = $this->model_catalog_product->getProduct($product['product_id']);

                    if ($product_info) {
                        $price_to_display = $this->tax->calculate((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                        $afterpay_price = $this->currency->format($price_to_display, $this->session->data['currency'], '', false);
                        
                        $category_status = $this->model_extension_payment_afterpay->checkCategoriesRestriction($product_info['product_id']);
                        
                        if ($category_status) {
                            $afterpay_status = true;
                        }
                    }
                    
                    $data['products'][$key]['afterpay_status'] = $afterpay_status;
                    $data['products'][$key]['afterpay_price'] = $afterpay_price;
                    $data['products'][$key]['afterpay_locale'] = $afterpay_locale;
                    $data['products'][$key]['afterpay_badge_theme'] = $afterpay_badge_theme;
                    $data['products'][$key]['afterpay_min'] = $afterpay_min;
                    $data['products'][$key]['afterpay_max'] = $afterpay_max;
                }
            }
        }
    }

    public function eventPostViewProductProduct($route, &$data, &$output) {
        if ($this->config->get('payment_afterpay_status') && isset($this->request->get['product_id'])) {
            $this->load->model('extension/payment/afterpay');

            if ($data['afterpay_status'] && $data['afterpay_express_checkout_status']) {
                $config = $this->model_extension_payment_afterpay->getAfterpayConfiguration();
                
                if ($config && $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']) && $this->model_extension_payment_afterpay->checkCategoriesRestriction($this->request->get['product_id']) && $this->session->data['currency'] == $config['currency_code']) {
                    $afterpay_data['country_code'] = $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']);
                    
                    // Adding Afterpay Express JS
                    if ($this->config->get('payment_afterpay_test')) {
                        if ($afterpay_data['country_code'] == 'GB') {
                            $afterpay_data['afterpay_js_href'] = 'https://portal.sandbox.clearpay.co.uk/afterpay.js?merchant_key=opencart';
                        } else {
                            $afterpay_data['afterpay_js_href'] = 'https://portal.sandbox.afterpay.com/afterpay.js?merchant_key=opencart';
                        }
                    } else {
                        if ($afterpay_data['country_code'] == 'GB') {
                            $afterpay_data['afterpay_js_href'] = 'https://portal.clearpay.co.uk/afterpay.js?merchant_key=opencart';
                        } else {
                            $afterpay_data['afterpay_js_href'] = 'https://portal.afterpay.com/afterpay.js?merchant_key=opencart';
                        }
                    }

                    $html = $this->load->view('extension/payment/afterpay_express', $afterpay_data);

                    $html .= '</head>';
                    $output = str_replace('</head>', $html, $output);
                }
            }
        }
    }
    
    public function eventPreViewProductProduct($route, &$data) {
        if ($this->config->get('payment_afterpay_status') && isset($this->request->get['product_id'])) {
            $this->load->model('extension/payment/afterpay');

            $config = $this->model_extension_payment_afterpay->getAfterpayConfiguration();

            $data['afterpay_status'] = false;
            $data['afterpay_price'] = 0;
            $data['afterpay_locale'] = '';
            $data['afterpay_badge_theme'] = $this->config->get('payment_afterpay_badge_theme') ? $this->config->get('payment_afterpay_badge_theme') : 'black-on-mint';
            $data['afterpay_min'] = 0.0;
            $data['afterpay_max'] = 0.0;
            $data['afterpay_express_checkout_status'] = false;

            $afterpay_express_price = $this->currency->format($this->getCartTotal(), $this->session->data['currency'], '', false);

            if ($config && $this->model_extension_payment_afterpay->checkStoreCountryCode($config['currency_code']) && $this->session->data['currency'] == $config['currency_code']) {
                $data['afterpay_min'] = $config['minimum_amount'];
                $data['afterpay_max'] = $config['maximum_amount'];
                $data['afterpay_locale'] = $this->model_extension_payment_afterpay->getLocale($config['currency_code']);
                
                $product_info = $this->model_catalog_product->getProduct($this->request->get['product_id']);

                if ($product_info) {
                    $price_to_display = $this->tax->calculate((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                    $data['afterpay_price'] = $this->currency->format($price_to_display, $this->session->data['currency'], '', false);

                    if ($this->model_extension_payment_afterpay->checkCategoriesRestriction($product_info['product_id'])) {
                        $data['afterpay_status'] = true;

                        if ($afterpay_express_price + $data['afterpay_price'] >= $data['afterpay_min'] && $afterpay_express_price + $data['afterpay_price'] <= $data['afterpay_max']) {
                            $data['afterpay_express_checkout_status'] = $this->config->get('payment_afterpay_express_checkout_status');
                        }
                    }
                }
                
                if ($this->config->get('payment_afterpay_product_listing')) {
                    foreach ($data['products'] as $key => $product) {
                        $afterpay_status = false;
                        $afterpay_price = 0;

                        $product_info = $this->model_catalog_product->getProduct($product['product_id']);

                        if ($product_info) {
                            $price_to_display = $this->tax->calculate((float)$product_info['special'] ? $product_info['special'] : $product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax'));
                            $afterpay_price = $this->currency->format($price_to_display, $this->session->data['currency'], '', false);
                            
                            $category_status = $this->model_extension_payment_afterpay->checkCategoriesRestriction($product_info['product_id']);

                            if ($category_status) {
                                $afterpay_status = true;
                            }
                        }
                        
                        $data['products'][$key]['afterpay_status'] = $afterpay_status;
                        $data['products'][$key]['afterpay_price'] = $afterpay_price;
                        $data['products'][$key]['afterpay_locale'] = $data['afterpay_locale'];
                        $data['products'][$key]['afterpay_badge_theme'] = $data['afterpay_badge_theme'];
                        $data['products'][$key]['afterpay_min'] = $data['afterpay_min'];
                        $data['products'][$key]['afterpay_max'] = $data['afterpay_max'];
                    }
                }
            }
        }
    }
}