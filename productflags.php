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
            && parent::install();
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
}