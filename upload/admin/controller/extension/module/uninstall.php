<?php
class ControllerExtensionModuleCurrencyConverterUninstall extends Controller {
    public function index() {
        $this->load->model('setting/setting');
        
        // Modül ayarlarını sil
        $this->model_setting_setting->deleteSetting('module_currency_converter');

        // Orijinal fiyat tablosunu sil
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "product_original_price`");
    }
} 