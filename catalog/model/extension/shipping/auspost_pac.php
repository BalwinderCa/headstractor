<?php
/*****************************************************************************
 *
 * ---------------------------------------------------------------
 * Australia Post: Postage Assesment Calculator for OpenCart 3.0
 *    (c) 2019 WWWShop, unauthorized reproduction is prohibited
 * ---------------------------------------------------------------
 *
 * Developer: WWWShop (opencart@wwwshop.com.au)
 * Date: 2019-09-10
 * Website: http://wwwshop.com.au/
 *
 *****************************************************************************/
class ModelExtensionShippingAuspostPac extends Model {
	const MIN_LENGTH = 5;
	const MIN_GIRTH = 15;
	const MIN_WEIGHT = 0.1;

	const MAX_PACKAGE_DOM_WEIGHT = 22;
	const MAX_PACKAGE_INT_WEIGHT = 20;
	const MAX_PACKAGE_LENGTH = 105;
	const MAX_PACKAGE_GIRTH = 140;
	const MAX_CUBIC_METRES = 0.25;

	const MAX_EXTRA_COVER = 5000;

	private $is_domestic;
	private $region;
	private $address;

	private $errors = array();
	private $packages = array();
	private $quote_data = array();

	private $length_class_id;
	private $weight_class_id;

	public function __construct($registry) {
		parent::__construct($registry);

		$length_class_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "length_class mc LEFT JOIN " . DB_PREFIX . "length_class_description mcd ON (mc.length_class_id = mcd.length_class_id) WHERE mcd.unit = 'cm' LIMIT 1");

		$this->length_class_id = false;
		if ($length_class_query->num_rows) {
			$this->length_class_id = $length_class_query->row['length_class_id'];
		}

		$weight_class_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "weight_class wc LEFT JOIN " . DB_PREFIX . "weight_class_description wcd ON (wc.weight_class_id = wcd.weight_class_id) WHERE wcd.unit = 'kg' LIMIT 1");

		$this->weight_class_id = false;
		if ($weight_class_query->num_rows) {
			$this->weight_class_id = $weight_class_query->row['weight_class_id'];
		}
	}

	function getQuote($address) {
		$this->load->language('extension/shipping/auspost_pac');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_auspost_pac_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('shipping_auspost_pac_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		if ($this->length_class_id === false) {
			$status = false;
		}

		if ($this->weight_class_id === false) {
			$status = false;
		}

		if ($status) {
			$total_weight = $this->getTotalWeight();
			$min_weight = ($this->config->get('shipping_auspost_pac_min_weight') > 0 ? $this->config->get('shipping_auspost_pac_min_weight') : false);
			$max_weight = ($this->config->get('shipping_auspost_pac_max_weight') > 0 ? $this->config->get('shipping_auspost_pac_max_weight') : false);

			if (($min_weight !== false && $total_weight < $min_weight)
				|| ($max_weight !== false && $total_weight > $max_weight)) {
				$status = false;
			}
		}

		$this->is_domestic = ($address['iso_code_2'] == 'AU');

		$method_data = array();

		if ($status) {
            $this->generatePackages();

            if (empty($this->errors)) {
                $this->address = $address;
                $this->region = ($this->is_domestic ? 'domestic' : 'international');

                $this->getParcels();

                if (count($this->packages) == 1 && $this->hasSatchels()) {
                    $this->getSatchels();
                }

                $this->sortShippingQuotes();
            }
		}

		if ($status) {
			$method_data = array(
				'code'       => 'auspost_pac',
				'title'      => $this->language->get('text_title'),
				'quote'      => $this->quote_data,
				'sort_order' => $this->config->get('shipping_auspost_pac_sort_order'),
				'error'      => implode("<br />\n", $this->errors),
			);
		}

		return $method_data;
	}

	private function getParcels() {
		$services = array();

		foreach ($this->packages as $package) {
			$info = array();

			if ($this->is_domestic) {
				$info = array(
					'from_postcode' => $this->config->get('shipping_auspost_pac_origin_postcode'),
					'to_postcode' => $this->address['postcode'],
					'length' => $package->length,
					'width' => $package->width,
					'height' => $package->height,
					'weight' => $package->weight,
					'cost' => $package->cost,
				);
			}
			else {
				$info = array(
					'country_code' => $this->address['iso_code_2'],
					'weight' => $package->weight,
					'cost' => $package->cost,
				);
			}

			$data = $this->call('service', array('parcel', $this->region), $info);

			if (isset($data->error)) {
				$this->errors[ucfirst(strtolower($data->error->errorMessage))] = ucfirst(strtolower($data->error->errorMessage));
			}
			else if (isset($data->services->service)) {
				$service_costs = $this->getServiceCosts($data, $info, 'isNotSatchel');

				foreach ($service_costs as $service) {
					if (!isset($services[$service->id])) {
						$services[$service->id] = (object) array(
							'id' => $service->id,
							'count' => 0,
							'code' => $service->code,
							'option' => (isset($service->option) ? $service->option : ''),
							'suboption' => (isset($service->suboption) ? $service->suboption : ''),
							'name' => $service->name,
							'price' => 0,
							'time' => (isset($service->time) ? $service->time : ''),
							'info' => $info,
							'packages' => array(),
						);
					}

					$services[$service->id]->count++;
					$services[$service->id]->price += $service->price;
					$services[$service->id]->packages[] =& $package;
				}
			}
		}

		// Add shipping services that are available for all packages.
		foreach ($services as $key => $service) {
			if (count($this->packages) == $service->count) {
				$this->addShippingQuote($service, $service->info);
			}
		}
	}

	private function getSatchels() {
		if (!$this->is_domestic) {
			return;
		}

		$length = 5;
		$width = 5;
		$height = 5;

		if ($this->packages[0]->length > $length) {
			$length = $this->packages[0]->length;
		}
		if ($this->packages[0]->width > $width) {
			$width = $this->packages[0]->width;
		}
		if ($this->packages[0]->height > $height) {
			$height = $this->packages[0]->height;
		}

		$info = array(
			'from_postcode' => $this->config->get('shipping_auspost_pac_origin_postcode'),
			'to_postcode' => $this->address['postcode'],
			'length' => $length,
			'width' => $width,
			'height' => $height,
			'weight' => $this->packages[0]->actual_weight,
			'cost' => $this->packages[0]->cost,
		);

		$data = $this->call('service', array('parcel', $this->region), $info);

		if (isset($data->error)) {
			$this->errors[ucfirst(strtolower($data->error->errorMessage))] = ucfirst(strtolower($data->error->errorMessage));
		}
		else if (isset($data->services->service)) {
			$services = $this->getServiceCosts($data, $info, 'isSatchel');

			foreach ($services as $service) {
				$this->addShippingQuote($service, $info);
			}
		}
	}

	private function generatePackages() {
		$this->packages = array();

		$products = $this->cart->getProducts();

		$items = array();
		foreach ($products as $key => $product) {
			for ($i = 0; $i < $product['quantity']; $i++) {
				$items[] =& $products[$key];
			}
		}

		if ($this->is_domestic) {
			$max_weight = self::MAX_PACKAGE_DOM_WEIGHT;
		} else {
			$max_weight = self::MAX_PACKAGE_INT_WEIGHT;
		}

		$count = count($items);

		for ($i = 0; $i < $count; $i++) {
			for ($j = 0; $j < $count; $j++) {
                if ($this->config->get('shipping_auspost_pac_one_item_per_parcel') && isset($this->packages[$j])) {
                    continue;
                }

				$width = $this->length->convert($items[$i]['width'], $items[$i]['length_class_id'], $this->length_class_id);
				$height = $this->length->convert($items[$i]['height'], $items[$i]['length_class_id'], $this->length_class_id);
				$length = $this->length->convert($items[$i]['length'], $items[$i]['length_class_id'], $this->length_class_id);
				$actual_weight = $this->weight->convert($items[$i]['weight'], $items[$i]['weight_class_id'], $this->weight_class_id) / $items[$i]['quantity'];
				$cubic_metres = ($length / 100) * ($width / 100) * ($height / 100);

				if ($actual_weight > $max_weight) {
					$this->errors[] = 'Unable to send using Australia Post. A product in your cart is over the maximum size of <strong>'.$max_weight.'kg</strong>.';
					return;
				}

				if ($length > self::MAX_PACKAGE_LENGTH) {
					$this->errors[] = 'Unable to send using Australia Post. A product in your cart is over the maximum length of <strong>'.self::MAX_PACKAGE_LENGTH.'cm</strong>.';
					return;
				}

				if ($this->is_domestic) {
					if ($cubic_metres > self::MAX_CUBIC_METRES) {
						$this->errors[] = 'Unable to send using Australia Post. A product in your cart is over the maximum size of <strong>'.self::MAX_CUBIC_METRES.' cubic metres</strong>.';
						return;
					}
				} else {
					if (($width + $height) * 2 > self::MAX_PACKAGE_GIRTH) {
						$this->errors[] = 'Unable to send using Australia Post. A product in your cart is over the maximum girth of <strong>'.self::MAX_PACKAGE_GIRTH.'cm</strong>.';
						return;
					}
				}

				if (!isset($this->packages[$j])) {
					$this->packages[$j] = (object) array(
						'max_weight' => $max_weight,
						'weight' => 0,
						'actual_weight' => 0,
						'cubic_weight' => 0,
						'width' => 0,
						'length' => 0,
						'height' => 0,
						'cost' => 0,
					);
				}

				$cost = $items[$i]['total'] / $items[$i]['quantity'];

				// Parcel volume in cubic metres (Cubic weight) = (L * W * H) * 250
				$cubic_weight = $cubic_metres * 250;

				if ($this->packages[$j]->actual_weight + $actual_weight <= $this->packages[$j]->max_weight) {
					if (!$this->is_domestic) {
						$cubic_cm = ($this->packages[$j]->cubic_weight + $cubic_weight) / 250 * pow(100, 3);
						$cubed_root = pow($cubic_cm, 1/3);

						// Make sure the height & width are smaller than 140cm in girth (140 / 2 / 2 = 35)
						if ($cubed_root > 35) {
							$length = round(($cubic_cm / (35 * 35)) * 100) / 100;
							$height = 35;
							$width = 35;
						} else {
							$length = round($cubed_root * 100) / 100;
							$height = round($cubed_root * 100) / 100;
							$width = round($cubed_root * 100) / 100;
						}
					}

					// If the length is smaller than 105cm, add it to the package.
					if ($length <= 105) {
						$this->packages[$j]->actual_weight += $actual_weight;
						$this->packages[$j]->cubic_weight += $cubic_weight;

						$this->packages[$j]->length = $length;
						$this->packages[$j]->height = $height;
						$this->packages[$j]->width = $width;

						$this->packages[$j]->cost += $cost;

						break;
					}

					// Continue onto the next package
				}
			}
		}

		foreach ($this->packages as $key => $package) {
			if ($package->length < self::MIN_LENGTH) {
				$this->packages[$key]->length = self::MIN_LENGTH;
			}
			if ($package->height < self::MIN_LENGTH) {
				$this->packages[$key]->height = self::MIN_LENGTH;
			}
			if ($package->width < self::MIN_LENGTH) {
				$this->packages[$key]->width = self::MIN_LENGTH;
			}

			// Make sure the height & width are larger than 15cm in girth (width * height * 2 >= 15).
			if (2 * ($package->height + $package->width) < self::MIN_GIRTH) {
				$this->packages[$key]->length = self::MIN_LENGTH;
				$this->packages[$key]->height = self::MIN_LENGTH;
				$this->packages[$key]->width = self::MIN_LENGTH;
			}

			$this->packages[$key]->weight = $package->actual_weight;

			if ($package->actual_weight < self::MIN_WEIGHT) {
				$this->packages[$key]->weight = self::MIN_WEIGHT;
			}
		}

		if (!$this->config->get('shipping_auspost_pac_multiple_packages')) {
			if (count($this->packages) > 1) {
				$this->errors[] = 'Unable to send using Australia Post. The contents of your cart is to large to send in a single parcel.';
				return;
			}
		}

		return;
	}

	private function getServiceCosts($data, $info, $check = false) {
		$available = $this->getAvailableServices($data, $info, $check);

		$costs = array();
		foreach ($available as $service) {
			if (empty($service->price)) {
				$query = $info;

                if (!isset($service->option) || empty($service->option)) {
                    $service->option = [null];
                }

				$query['service_code'] = $service->code;

				if (isset($service->suboption)) {
					$query['suboption_code'] = $service->suboption;
				}
				if (isset($service->extra_cover)) {
					$query['extra_cover'] = ceil($service->extra_cover);
				}

                $result = null;

                foreach ($service->option as $option) {
                    if ($option) {
                        $query['option_code'] = $option;
                    }

                    $tmp_result = $this->call('calculate', array('parcel', $this->region), $query);

                    if (!isset($tmp_result->postage_result)) {
                        continue 2;
                    }

                    if (isset($tmp_result->postage_result->costs->cost) && !is_array($tmp_result->postage_result->costs->cost)) {
                        $tmp_result->postage_result->costs->cost = [$tmp_result->postage_result->costs->cost];
                    }

                    if (!$result) {
                        $result = $tmp_result;
                    } else {
                        if (isset($tmp_result->postage_result->costs->cost[1])) {
                            $result->postage_result->costs->cost[] = $tmp_result->postage_result->costs->cost[1];
                        }
                    }
                }

				$name_extras = array();
				if (isset($result->postage_result->costs->cost) && is_array($result->postage_result->costs->cost)) {
					$i = 0; foreach ($result->postage_result->costs->cost as $cost) {
						if ($i++ == 0) { continue; }
						$name_extras[] = $cost->item;
					}
				}

				$name_extra = '';
				if (!empty($name_extras)) {
					$name_extra = ' (' . implode(', ', $name_extras) . ')';
				}

                if ($service->option) {
                    if (!is_array($service->option)) {
                        $options = array($service->option);
                    } else {
                        $options = $service->option;
                    }
                } else {
                    $options = array();
                }

                $option = isset($service->option)
                    ? is_array($service->option) ? implode('_', $service->option) : $service->option
                    : '';

                $suboption = isset($service->suboption)
                    ? is_array($service->suboption) ? implode('_', $service->suboption) : $service->suboption
                    : '';

                $price = isset($result->postage_result->costs->cost)
                    ? array_sum(array_map(function($item) { return $item->cost; }, $result->postage_result->costs->cost))
                    : $result->postage_result->total_cost;

				$cost = (object) array(
					'id' => $service->code.$option.$suboption,
					'name' => $result->postage_result->service.$name_extra,
					'code' => $service->code,
					'option' => $options,
					'suboption' => (isset($service->suboption) ? $service->suboption : ''),
					'price' => $price,
				);

				$time = '';
				if (isset($result->postage_result->delivery_time)) {
					$cost->time = $this->parseShippingTime($result->postage_result->delivery_time);
				}

				$costs[] = $cost;
			}
			else {
				$service->id = $service->code;

				$costs[] = $service;
			}
		}

		return $costs;
	}

	private function getAvailableServices($data, $info, $check = false) {
		$active = $this->getServices();

		if (!is_array($active) || empty($active)) {
			return array();
		}

		$services = array();

		foreach ($data->services->service as $service_node) {
            if (!isset($service_node->code)) {
                continue;
            }

			if (is_callable(array($this, $check))) {
				if (!$this->{$check}($service_node->code)) {
					continue;
				}
			}

            foreach ($active as $service) {
                if ($service_node->code != $service['code']) {
                    continue;
                }

				if ($this->is_domestic) {
					if (isset($service_node->options->option)) {
						$options = $service_node->options->option;
						$options = (is_array($options) ? $options : array($options));

                        if ($options) {
                            $item = (object) array(
                                'name' => array(),
                                'code' => $service_node->code,
                                'option' => array(),
                                'suboption' => array(),
                            );

                            foreach ($options as $option_node) {
                                $option =& $service['option'][$option_node->code];

                                if (isset($option['status'])) {
                                    $item->name[] = $option_node->name;
                                    $item->option[] = $option_node->code;

                                    if (isset($option_node->suboptions)) {
                                        $suboptions = $option_node->suboptions->option;
                                        $suboptions = (is_array($suboptions) ? $suboptions : array($suboptions));
                                        foreach ($suboptions as $suboption_node) {
                                            if (isset($suboption_node->code)) {
                                                $suboption =& $option['suboption'][$suboption_node->code];

                                                if (isset($suboption['status'])) {
                                                    if ($suboption_node->code == 'AUS_SERVICE_OPTION_EXTRA_COVER') {
                                                        //$item->extra_cover = $suboption['value'];
                                                        $item->extra_cover = ($info['cost'] > self::MAX_EXTRA_COVER ? self::MAX_EXTRA_COVER : $info['cost']);
                                                    }

                                                    $item->suboption[] = $suboption_node->code;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $services[] = $item;
                        }
					}
					else {
						$services[] = (object) array(
							'name' => $service_node->name,
							'code' => $service_node->code,
							'price' => (isset($service_node->price) ? $service_node->price : ''),
						);
					}
				}
				else {
					$item = (object) array(
						'name' => $service_node->name,
						'code' => $service_node->code,
						'option' => array(),
					);

                    $key = $service_node->code;

					if (isset($service_node->options->option)) {
						$options = $service_node->options->option;
						$options = (is_array($options) ? $options : array($options));
						foreach ($options as $option_node) {
                            if (isset($option_node->code)) {
                                $option =& $service['option'][$option_node->code];

                                if (isset($option['status'])) {
                                    $key .= '-' . $option_node->code;

                                    if ($option_node->code == 'INT_EXTRA_COVER') {
                                        $item->extra_cover = ($info['cost'] > self::MAX_EXTRA_COVER ? self::MAX_EXTRA_COVER : $info['cost']);
                                    }

                                    $item->option[] = $option_node->code;
                                }
                            }
						}
					}

					$services[$key] = $item;
				}
			}
		}

		return $services;
	}

    private function getServices() {
		$services = $this->config->get('shipping_auspost_pac_service');

        if (!is_array($services)) {
            $services = array();
        }

        $results = array();

        foreach ($services as $service) {
            if (!isset($service['is_domestic']) && !$this->is_domestic || isset($service['is_domestic']) && $service['is_domestic'] == $this->is_domestic) {
                $id = array($service['code']);

                if (isset($service['option'])) {
                    foreach ($service['option'] as $code => $option) {
                        if (isset($option['status']) && $option['status']) {
                            $id[] = $code;

                            if (isset($option['suboption'])) {
                                foreach ($option['suboption'] as $code => $suboption) {
                                    if (isset($suboption['status']) && $suboption['status']) {
                                        $id[] = $code;
                                    }
                                }
                            }
                        }
                    }
                }

                $results[implode('', $id)] = $service;
            }
        }

        return $results;
    }

	private function getShippingTime($service, $args) {
		$args['service_code'] = $service->code;

		if (!empty($service->option)) {
			$args['option_code'] = $service->option;
		}

		if (!empty($service->suboption)) {
			$args['suboption_code'] = $service->suboption;
		}

		$data = $this->call('calculate', array('parcel', $this->region), $args);

		$time = '';
		if (isset($data->postage_result->delivery_time)) {
			$time = $this->parseShippingTime($data->postage_result->delivery_time);
		}

		return $time;
	}

	private function parseShippingTime($str) {
		$time = '';

		if (preg_match('@time:[\s]*(.*)$@i', $str, $matches)) {
			$time = strtolower($matches[1]);
		}
		else if (strpos(strtolower($str), 'next business day') !== false) {
			$time = 'Next business day';
		}
		else if (preg_match('@([\S]+ (to|in) [\d]+ business days)@i', $str, $matches)) {
			$time = strtolower($matches[1]);
		}
		else if (preg_match('@(Same business day delivery if lodged over the counter before [0-9a-zA-z]+)@i', $str, $matches)) {
			$time = strtolower($matches[1]);
		}

		if (!empty($time)) {
			$time = ' ('.$time.')';
		}

		return $time;
	}

	public function isSatchel($code) {
		return (strpos($code, '_SATCHEL_') !== false);
	}

	public function isNotSatchel($code) {
		return (!$this->isSatchel($code));
	}

	private function hasSatchels() {
		$active = $this->getServices();

		if (!is_array($active) || empty($active)) {
			return false;
		}

		foreach ($active as $code => $value) {
			if ($this->isSatchel($code)) {
                return true;
			}
		}

		return false;
	}

	private function addShippingQuote($service, $info) {
		if (!isset($service->price)) {
			return;
		}

		$code = strtolower($service->id);

		$time = '';
		if ($this->config->get('shipping_auspost_pac_show_delivery_time')) {
			if (isset($service->time) && !empty($service->time)) {
				$time = $service->time;
			}
			else {
				$time = $this->getShippingTime($service, $info);
			}
		}

		$handling_fee_percentage = (float) $this->config->get('shipping_auspost_pac_handling_fee');

	    if ($handling_fee_percentage > 0) {
	        $handling_fee = $service->price * ($handling_fee_percentage / 100);
	        $service->price += $handling_fee;
	    }

        $is_domestic = isset($info['from_postcode']);

        if ($is_domestic && $this->config->get('shipping_auspost_pac_remove_gst_from_price')) {
            $service->price = $service->price / 1.1;
        }

        // Fix naming of 1kg satchels
        if (strncmp($service->name, 'Small-Medium satchel', 20) === 0) {
            if (strncmp($code, 'aus_parcel_regular_satchel_1kg', 30) === 0) {
                $service->name = str_replace('Small-Medium satchel', 'Parcel Post Small/Medium Satchel', $service->name);
            } elseif (strncmp($code, 'aus_parcel_express_satchel_1kg', 30) === 0) {
                $service->name = str_replace('Small-Medium satchel', 'Express Post Small/Medium Satchel', $service->name);
            }
        }

        $satchel_regular_codes = [
            'aus_parcel_regular_satchel_small',
            'aus_parcel_regular_satchel_mediu',
            'aus_parcel_regular_satchel_large',
            'aus_parcel_regular_satchel_extra',
        ];

        if (in_array(substr($code, 0, 32), $satchel_regular_codes)) {
            $service->name = 'Parcel Post ' . $service->name . ' Satchel';
        }

        $satchel_express_codes = [
            'aus_parcel_express_satchel_small',
            'aus_parcel_express_satchel_mediu',
            'aus_parcel_express_satchel_large',
            'aus_parcel_express_satchel_extra',
        ];

        if (in_array(substr($code, 0, 32), $satchel_express_codes)) {
            $service->name = 'Express Post ' . $service->name . ' Satchel';
        }

		$this->quote_data[$code] = array(
			'code'         => 'auspost_pac.' . $code,
			'title'        => htmlentities($service->name).$time,
			'cost'         => $this->currency->convert($service->price, 'AUD', $this->config->get('config_currency')),
			'tax_class_id' => $this->config->get('shipping_auspost_pac_tax_class_id'),
			'text'         => $this->currency->format($this->tax->calculate($this->currency->convert($service->price, 'AUD', $this->session->data['currency']), $this->config->get('shipping_auspost_pac_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'], 1.0000000)
		);
	}

	private function sortShippingQuotes() {
		uasort($this->quote_data, function($a, $b) {
            if ($a['cost'] == $b['cost']) return 0;
            return ($a['cost'] > $b['cost'] ? 1 : -1);
        });
	}

	private function getTotalWeight() {
		$weight = 0;

		foreach ($this->cart->getProducts() as $product) {
			$weight += $this->weight->convert($product['weight'], $product['weight_class_id'], $this->weight_class_id);
		}

		return $weight;
	}

	private function buildQuery($query) {
		$parts = array();

		foreach ($query as $key => $value) {
            $treat_as_array = is_array($value) && count($value) > 1;

			if (!is_array($value)) {
				$value = array($value);
			}

			foreach ($value as $v) {
				$parts[] = urlencode($key) . ($treat_as_array ? '[]' : '') . '=' . urlencode($v);
			}
		}

		return implode('&', $parts);
	}

    private static $CACHE = [];

	private function call($call, $args = array(), $query = array(), $format = 'json') {
		$headers = array(
			'AUTH-KEY: ' . $this->config->get('shipping_auspost_pac_api_key'),
		);

		$query_str = '';
		if (!empty($query)) {
			$query_str = '?'.$this->buildQuery($query);
		}

		$path = '';
		if (!empty($args)) {
			$path = implode('/', $args) . '/';
		}

        $url = 'https://digitalapi.auspost.com.au/postage/'.$path.$call.'.'.$format.$query_str;
        //$url = 'https://digitalapi-staging.npe.auspost.com.au/postage-staging/'.$path.$call.'.'.$format.$query_str;

        if (!isset(self::$CACHE[$url])) {
            $ch = curl_init();  

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            self::$CACHE[$url] = curl_exec($ch);
        }

        $result = json_decode(self::$CACHE[$url]);

		return (
			!empty($result)
			? $result
			: (object) $result
		);
	}
}