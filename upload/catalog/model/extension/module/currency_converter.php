<?php
class ModelExtensionModuleCurrencyConverter extends Model {
    public function updatePrice($product_id, $price) {
        $this->db->query("UPDATE " . DB_PREFIX . "product SET price = '" . (float)$price . "' WHERE product_id = '" . (int)$product_id . "'");
    }
} 