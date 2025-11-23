<?php
class ModelExtensionPaymentAfterpay extends Model {
    public function updateAfterpayConfiguration($data = array()) {
        if ($data) {
            $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "afterpay_config`");
            $this->db->query("INSERT INTO `" . DB_PREFIX . "afterpay_config` SET `minimum_amount` = '" . (float)$data['minimum_amount'] . "', `maximum_amount` = '" . (float)$data['maximum_amount'] . "', `currency_code` = '" . $this->db->escape($data['currency_code']) . "', `date_added` = NOW()");
        }
    }

    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "afterpay_config` (
            `minimum_amount` DECIMAL( 10, 2 ) NOT NULL,
            `maximum_amount` DECIMAL( 10, 2 ) NOT NULL,
            `currency_code` varchar(3) NOT NULL,
            `date_added` DATETIME NOT NULL
        ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "afterpay_order` (
          `afterpay_order_id` int(11) NOT NULL AUTO_INCREMENT,
          `order_id` int(11) NOT NULL,
          `afterpay_reference_id` varchar(40) NOT NULL,
          `currency_code` CHAR(3) NOT NULL,
          `total` DECIMAL( 10, 2 ) NOT NULL,
          `date_added` DATETIME NOT NULL,
          `date_modified` DATETIME NOT NULL,
          PRIMARY KEY (`afterpay_order_id`)
        ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
        
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "afterpay_order_transaction` (
          `afterpay_order_transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
          `afterpay_order_id` INT(11) NOT NULL,
          `date_added` DATETIME NOT NULL,
          `type` ENUM('approved', 'declined', 'refunded') DEFAULT NULL,
          `amount` DECIMAL( 10, 2 ) NOT NULL,
          PRIMARY KEY (`afterpay_order_transaction_id`)
        ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;");
    }

    public function uninstall() {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "afterpay_config`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "afterpay_order`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "afterpay_order_transaction`");
    }

    public function setOrderRefund($order_id, $comment) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$this->config->get('payment_afterpay_refunded_status_id') . "', notify = '0', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
    }

    public function getOrder($order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "afterpay_order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

        if ($query->num_rows) {
            $query->row['transactions'] = $this->getTransactions($query->row['afterpay_order_id']);
            
            return $query->row;
        } else {
            return false;
        }
    }
  
    public function addTransaction($afterpay_order_id, $type, $total) {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "afterpay_order_transaction` SET `afterpay_order_id` = '" . (int)$afterpay_order_id . "', `date_added` = now(), `type` = '" . $this->db->escape($type) . "', `amount` = '" . (float)$total . "'");
    }

    public function getRefundsNumber($afterpay_order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "afterpay_order_transaction` WHERE `afterpay_order_id` = '" . (int)$afterpay_order_id . "' AND `type` = 'refunded'");

        return $query->num_rows;
    }


    private function getTransactions($afterpay_order_id) {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "afterpay_order_transaction` WHERE `afterpay_order_id` = '" . (int)$afterpay_order_id . "'");
    
        return $query->rows;
    }
}