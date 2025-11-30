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
            && $this->registerHook('actionProductFlagsModifier');
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
}