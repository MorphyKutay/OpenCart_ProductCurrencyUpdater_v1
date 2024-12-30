<?php
class ControllerExtensionModuleCurrencyConverter extends Controller {
    public function convert() {
        $this->load->model('extension/module/currency_converter');
        
        // TCMB'den güncel kurları çek
        $exchange_rates = $this->getTCMBRates();
        
        if ($exchange_rates) {
            $this->load->model('catalog/product');
            $products = $this->model_catalog_product->getProducts();
            
            foreach ($products as $product) {
                if ($product['currency_id'] != 'TRY') {
                    $new_price = $this->convertToTRY($product['price'], $product['currency_id'], $exchange_rates);
                    $this->model_extension_module_currency_converter->updatePrice($product['product_id'], $new_price);
                }
            }
        }
    }

    private function getTCMBRates() {
        $xml = simplexml_load_file('https://www.tcmb.gov.tr/kurlar/today.xml');
        $rates = array();
        
        if ($xml) {
            foreach ($xml->Currency as $currency) {
                $code = (string)$currency['CurrencyCode'];
                $rate = (float)$currency->BanknoteSelling;
                $rates[$code] = $rate;
            }
        }
        
        return $rates;
    }

    private function convertToTRY($price, $from_currency, $rates) {
        if (isset($rates[$from_currency])) {
            return $price * $rates[$from_currency];
        }
        return $price;
    }
} 