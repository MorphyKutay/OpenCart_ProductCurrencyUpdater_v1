<?php
class ControllerExtensionModuleCurrencyConverterInstall extends Controller {
    public function index() {
        $this->load->model('setting/setting');
        
        // Modül ayarlarını varsayılan değerlerle kaydet
        $this->model_setting_setting->editSetting('module_currency_converter', array(
            'module_currency_converter_status' => 1,
            'module_currency_converter_api_key' => ''
        ));

        // Veritabanında orijinal fiyatları saklamak için yeni bir tablo oluştur
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_original_price` (
                `product_id` INT(11) NOT NULL,
                `original_price` DECIMAL(15,4) NOT NULL,
                `currency_code` VARCHAR(3) NOT NULL,
                `last_update` DATETIME NOT NULL,
                PRIMARY KEY (`product_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ");
    }
} 