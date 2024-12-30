<?php
class ControllerExtensionModuleCurrencyConverter extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/module/currency_converter');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->model_setting_setting->editSetting('module_currency_converter', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        // Dil değişkenlerini data'ya ekle
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['entry_status'] = $this->language->get('entry_status');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/currency_converter', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/currency_converter', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_currency_converter_status'])) {
            $data['module_currency_converter_status'] = $this->request->post['module_currency_converter_status'];
        } else {
            $data['module_currency_converter_status'] = $this->config->get('module_currency_converter_status');
        }

        // Ürünleri direkt SQL ile çekelim
        $sql = "SELECT p.product_id, pd.name, p.price, p.image 
                FROM " . DB_PREFIX . "product p 
                LEFT JOIN " . DB_PREFIX . "product_description pd 
                ON (p.product_id = pd.product_id) 
                WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

        $query = $this->db->query($sql);
        $products = $query->rows;

        // Ürün resimlerini işle
        $this->load->model('tool/image');

        foreach ($products as &$product) {
            try {
                // Resim varsa ve dosya mevcutsa
                if (!empty($product['image']) && file_exists(DIR_IMAGE . $product['image'])) {
                    $product['thumb'] = $this->model_tool_image->resize($product['image'], 40, 40);
                } else {
                    // Resim yoksa veya dosya bulunamadıysa
                    $product['thumb'] = $this->model_tool_image->resize('no_image.png', 40, 40);
                }
            } catch (Exception $e) {
                // Herhangi bir hata durumunda
                $product['thumb'] = $this->model_tool_image->resize('no_image.png', 40, 40);
                
                // Hata logla
                $this->log->write('Currency Converter Image Error: ' . $e->getMessage());
            }
        }

        $data['products'] = $products;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        // Journal tema stil dosyasını yükle
        $this->document->addStyle('view/javascript/currency_converter/style.css');

        $this->response->setOutput($this->load->view('extension/module/currency_converter', $data));
    }

    public function convert() {
        $this->load->language('extension/module/currency_converter');
        
        $this->response->addHeader('Content-Type: application/json');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'extension/module/currency_converter')) {
            $json['error'] = $this->language->get('error_permission');
            $this->response->setOutput(json_encode($json));
            return;
        }

        try {
            if (!isset($this->request->post['selected']) || empty($this->request->post['selected'])) {
                throw new Exception('Lütfen en az bir ürün seçiniz');
            }

            // TCMB'den döviz kurunu al
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]);
            
            $xml_content = @file_get_contents('https://www.tcmb.gov.tr/kurlar/today.xml', false, $context);
            
            if (!$xml_content) {
                throw new Exception('TCMB sunucusuna bağlanılamadı');
            }
            
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($xml_content);
            
            if (!$xml) {
                throw new Exception('XML verisi işlenemedi');
            }
            
            $usd_rate = null;
            foreach ($xml->Currency as $currency) {
                if ((string)$currency['CurrencyCode'] === 'USD') {
                    $usd_rate = (float)str_replace(',', '.', $currency->BanknoteSelling);
                    break;
                }
            }

            if (!$usd_rate) {
                throw new Exception('USD kuru bulunamadı');
            }

            $success_count = 0;
            $error_count = 0;
            $errors = array();

            // Seçili ürünleri tek sorguda al
            $product_ids = array_map('intval', $this->request->post['selected']);
            $sql = "SELECT product_id, price 
                    FROM " . DB_PREFIX . "product 
                    WHERE product_id IN (" . implode(',', $product_ids) . ")";
            
            $query = $this->db->query($sql);
            $products = $query->rows;

            foreach ($products as $product) {
                try {
                    // USD fiyatı TL'ye çevir
                    $new_price = round($product['price'] * $usd_rate, 2);
                    
                    // Fiyatı güncelle
                    $this->db->query("UPDATE " . DB_PREFIX . "product SET 
                        price = '" . (float)$new_price . "',
                        date_modified = NOW() 
                        WHERE product_id = '" . (int)$product['product_id'] . "'");
                    
                    $success_count++;
                    
                } catch (Exception $e) {
                    $error_count++;
                    $errors[] = "Ürün ID: " . $product['product_id'] . " - " . $e->getMessage();
                }
            }

            if ($success_count > 0) {
                $json['success'] = sprintf('%d ürünün fiyatı başarıyla güncellendi (USD Kuru: %s TL)', 
                    $success_count, 
                    number_format($usd_rate, 4)
                );
                if ($error_count > 0) {
                    $json['success'] .= sprintf(', %d ürün güncellenemedi', $error_count);
                    $json['errors'] = $errors;
                }
            } else {
                throw new Exception('Hiçbir ürün güncellenemedi');
            }

        } catch (Exception $e) {
            $json['error'] = $e->getMessage();
        }

        $this->response->setOutput(json_encode($json));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/currency_converter')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
} 