<?php

require_once __DIR__.'/classes/ProductFlag.php';

class ProductFlags extends Module
{
    function __construct()
    {
        $this->name = 'productflags';
        $this->author = 'Adilis';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->displayName = $this->l('Product Flags');
        $this->description = $this->l('Manage product flags like "New", "On Sale", etc.');
        $this->confirmUninstall = $this->l('Are you sure ?');

        parent::__construct();
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install(): bool
    {
        if (file_exists($this->getLocalPath().'sql/install.php')) {
            require_once($this->getLocalPath().'sql/install.php');
        }

        return
            $this->installTab()
            && parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackofficeHeader')
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductFlagsModifier')
            && $this->registerHook('actionObjectProductFlagAddAfter')
            && $this->registerHook('actionObjectProductFlagUpdateAfter')
            && $this->registerHook('actionObjectProductFlagDeleteAfter')
            ;
    }


    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall(): bool
    {
        if (file_exists($this->getLocalPath().'sql/uninstall.php')) {
            require_once($this->getLocalPath().'sql/uninstall.php');
        }

        return
            $this->uninstallTab()
            && parent::uninstall();
    }


    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function installTab(): bool
    {
        $tab = new Tab((int)\Tab::getIdFromClassName('AdminProductFlag'));
        $tab->class_name = 'AdminProductFlag';
        $tab->id_parent = 0;
        $tab->module = $this->name;
        foreach(\Language::getLanguages(false) as $lang) {
            $tab->name[$lang['id_lang']] = $this->displayName;
        }
        if (!$tab->add()) {
            return false;
        }
        return true;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function uninstallTab(): bool
    {
        $tab = new Tab((int)\Tab::getIdFromClassName('AdminProductFlag'));
        if (Validate::isLoadedObject($tab) && !$tab->delete()) {
            return false;
        }
        return true;
    }

    public function getContent() {
        Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminProductFlag'));
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function hookActionProductFlagsModifier($params)
    {
        if (!isset($params['product']) || !is_array($params['product'])) {
            return;
        }

        $flags = ProductFlag::getProductFlags($params['product']);


        if (!empty($flags)) {
            foreach ($flags as $flag) {
                $type = 'product-flag-'.$flag['id_product_flag'];
                $params['flags'][$type] = [
                    'type' => $type,
                    'label' => $flag['text'],
                    'color' => $flag['color'],
                    'background_color' => $flag['background_color']
                ];
            }
        }
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerStylesheet(
            'module-productflags-css',
            'modules/'.$this->name.'/views/css/productflags.css',
            [
                'media' => 'all',
                'priority' => 150,
            ]
        );
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function generateCSSFile(): bool
    {
        $flags = ProductFlag::getFlags($this->context->language->id, true, false);
        $css = '';
        foreach ($flags as $flag) {
            $css .= '.product-flags .product-flag.product-flag-'.$flag['id_product_flag'].' {';
            $css .= 'color: '.$flag['color'].';';
            $css .= 'background-color: '.$flag['background_color'].';';
            $css .= '}';
        }

        $result = file_put_contents($this->getLocalPath().'/views/css/productflags.css', $css);
        if ($result === false) {
            PrestaShopLogger::addLog('ProductFlags: Failed to write CSS file.', 3, null, 'ProductFlags', null, true);
        }

        return $result !== false;
    }



    /**
     * @throws PrestaShopDatabaseException
     */
    public function hookActionObjectProductFlagAddAfter()
    {
        $this->generateCSSFile();
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function hookActionObjectProductFlagUpdateAfter($object)
    {
        $this->generateCSSFile();
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function hookActionObjectProductFlagDeleteAfter($object)
    {
        $this->generateCSSFile();
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = Tools::getValue('id_product', (int) $params['id_product']);
        $product = new Product($id_product, true, $this->context->language->id);
        $productArray = [
            'id_product' => $product->id,
            'new' => $product->new,
            'sale' => $product->on_sale,
            'id_manufacturer' => $product->id_manufacturer,
            'quantity_all_versions' => StockAvailable::getQuantityAvailableByProduct($product->id),
        ];

        $available_flags = ProductFlag::getFlags($this->context->language->id, false, false);
        ProductFlag::prepareCachesForFlags($available_flags);

        foreach ($available_flags as &$flag) {
            $flag['active'] = 'auto';
            $flag['auto_criteria'] = $this->l('No');
            $conditions = $flag['conditions'] ?? [];
            if (!empty($conditions['include']) && in_array($id_product, $conditions['include'])) {
                $flag['active'] = 1;
                continue;
            }

            if (!empty($conditions['exclude']) && in_array($id_product, $conditions['exclude'])) {
                $flag['active'] = 0;
                continue;
            }

            $flag['auto_criteria'] = ProductFlag::canApply($flag, $productArray) ? $this->l('Yes') : $this->l('No');
        }
        unset($flag);

        $this->context->smarty->assign(
            [
                'available_flags' => $available_flags,
                'ajax_url' => Context::getContext()->link->getAdminLink('AdminProductFlag', true, [], [
                    'ajax' => 1,
                    'action' =>'updateProduct',
                    'id_product' => $id_product,
                ]),
            ]
        );
        return $this->display(__FILE__, 'admin-products-extra.tpl');
    }

    public function hookDisplayBackofficeHeader()
    {
        if (Tools::getValue('controller') != 'AdminProducts') {
            return;
        }
        $this->context->controller->addJs($this->_path.'views/js/admin-product.js');
        $this->context->controller->addCss($this->_path.'views/css/admin-product.css');
    }
}