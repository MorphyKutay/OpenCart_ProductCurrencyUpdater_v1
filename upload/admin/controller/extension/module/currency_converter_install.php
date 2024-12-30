<?php
class ControllerExtensionModuleCurrencyConverterInstall extends Controller {
    public function index() {
        $this->load->model('setting/setting');
        $this->load->model('setting/module');
        
        // Modül ayarlarını varsayılan değerlerle kaydet
        $this->model_setting_setting->editSetting('module_currency_converter', array(
            'module_currency_converter_status' => 1
        ));

        // Modülü yükle
        $this->model_setting_module->addModule('currency_converter', array(
            'name' => 'Döviz Çevirici',
            'status' => 1
        ));

        // no_image.png dosyasını kontrol et
        if (!file_exists(DIR_IMAGE . 'no_image.png')) {
            // Varsayılan no_image.png dosyasını kopyala
            copy(DIR_IMAGE . 'catalog/no_image.png', DIR_IMAGE . 'no_image.png');
        }
    }
} 