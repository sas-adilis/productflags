<?php
class AdminProductFlagController extends ModuleAdminController {

    /** @var $module ProductFlags */
    public $module;

    /** @var $object ProductFlag */
    public $object;

    protected $position_identifier = 'id_product_flag';


    public function __construct()
    {
        parent::__construct();

        $this->table = 'product_flag';
        $this->className = 'ProductFlag';

        $this->identifier = 'id_product_flag';
        $this->lang = true;
        $this->list_id = 'product_flag';
        $this->bootstrap = true;
        $this->_defaultOrderBy = 'position';
        $this->show_form_cancel_button = false;

        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->addRowAction('duplicate');

        $this->fields_list = array(
            'id_product_flag' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 25
            ),
            'text' => array(
                'title' => $this->l('Text'),
                'callback' => 'displayFlag',
            ),
            'from' => array(
                'title' => $this->l('From'),
                'type' => 'date',
            ),
            'to' => array(
                'title' => $this->l('To'),
                'type' => 'date',
            ),
            'position' => array(
                'title' => $this->l('Priority'),
                'filter_key' => 'a!position',
                'position' => 'position',
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'stop_after' => array(
                'title' => $this->l('Stop after this flag'),
                'active' => 'stop_after',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'orderby' => false
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'class' => 'fixed-width-xs',
                'align' => 'center',
                'orderby' => false
            )
        );
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri().'views/css/admin.css');
        $this->addJS('https://cdnjs.cloudflare.com/ajax/libs/jscolor/2.5.2/jscolor.min.js');
        $this->addJS($this->module->getPathUri().'views/js/admin.js');
    }


    public function renderForm(): string
    {

        $manufacturers = [];
        foreach (Manufacturer::getManufacturers() as $manufacturer) {
            if (!isset($manufacturers[$manufacturer['id_manufacturer']])) {
                $manufacturers[$manufacturer['id_manufacturer']] = $manufacturer;
            }
        }
        $manufacturers = array_values($manufacturers);

        if (!Validate::isLoadedObject($this->object)) {
            $this->object->from = date('Y-m-d H:i:s');
            $this->object->to = date('Y-m-d H:i:s', strtotime('+1 year'));
        } else {
            $this->fields_value['conditions'] = Tools::getValue('conditions', json_decode($this->object->conditions, true));
        }

        $this->multiple_fieldsets = true;
        $this->fields_form[]['form'] = array(
            'legend' => [
                'title' => $this->l('Product Flag'),
                'icon' => 'icon-flag'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'name' => 'text',
                    'id' => 'text',
                    'label' => $this->l('Text'),
                    'required' => true,
                    'lang' => true,
                ],
                [
                    'type' => 'switch',
                    'name' => 'stop_after',
                    'required' => true,
                    'is_bool' => true,
                    'label' => $this->l('Stop after this flag'),
                    'desc' => $this->l('If enabled, no other flags will be displayed after this one.'),
                    'values' => [
                        ['id' => 'stop_after_on', 'value' => 1, 'label' => $this->l('Yes')],
                        ['id' => 'stop_after_off', 'value' => 0, 'label' => $this->l('No')],
                    ]
                ],
                [
                    'type' => 'separator',
                    'form_group_class' => 'separator',
                    'name' => 'separator_colors',
                    'label' => $this->l('Colors'),
                ],
                [
                    'type' => 'color',
                    'name' => 'color',
                    'id' => 'color',
                    'label' => $this->l('Text color'),
                    'required' => true,
                    'class' => 'fixed-width-lg',
                ],
                [
                    'type' => 'color',
                    'name' => 'background_color',
                    'id' => 'background_color',
                    'label' => $this->l('Background color'),
                    'required' => true,
                    'class' => 'fixed-width-lg',
                ],
                [
                    'type' => 'separator',
                    'form_group_class' => 'separator',
                    'name' => 'separator_colors',
                    'label' => $this->l('Display conditions'),
                ],
                [
                    'type' => 'conditions',
                    'label' => $this->l('Conditions'),
                    'name' => 'conditions',
                    'categories' => $this->getCategories(),
                    'manufacturers' => $manufacturers,
                    'suppliers' => Supplier::getSuppliers(),
                    'products' => Product::getProducts($this->context->language->id, 0, 1000, 'name', 'asc'),
                    'desc' => $this->l('Define here the conditions under which this flag will be displayed on products. If no conditions are defined or met, products displaying this flag must be selected manually from the product sheet in the back office.'),
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('From'),
                    'name' => 'from',
                    'desc' => $this->l('Format: YYYY-MM-DD HH:MM:SS'),
                ],
                [
                    'type' => 'datetime',
                    'label' => $this->l('To'),
                    'name' => 'to',
                    'desc' => $this->l('Format: YYYY-MM-DD HH:MM:SS'),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'required' => true,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        ]
                    ]
                ]
            ],
            'buttons' => array(
                'cancel' => array(
                    'title' => $this->l('Back to list'),
                    'href' => (Tools::safeOutput(Tools::getValue('back'))) ?: $this->context->link->getAdminLink('AdminProductFlag'),
                    'icon' => 'process-icon-cancel',
                ),
                'saveAndStay' => array(
                    'title' => $this->l('Save and stay'),
                    'name' => 'submitAdd' . $this->table . 'AndStay',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right',
                    'icon' => 'process-icon-save',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );
        return parent::renderForm();

    }


    /**
     * @throws PrestaShopException
     */
    public function initProcess()
    {
        if (Tools::getIsset('duplicate' . $this->table)) {
            if ($this->access('add')) {
                $this->action = 'duplicate';
            } else {
                $this->errors[] = Tools::displayError('You do not have permission to add this.');
            }
        } elseif ((isset($_GET['stop_after' . $this->table]) || isset($_GET['stop_after'])) && Tools::getValue($this->identifier)) {
            /* Change object status (active, inactive) */
            if ($this->access('edit')) {
                $this->action = 'stop_after';
            } else {
                $this->errors[] = $this->trans('You do not have permission to edit this.', [], 'Admin.Notifications.Error');
            }
        } else {
            parent::initProcess();
        }
    }

    public function displayFlag($text, $row): string
    {
        $style = 'color: '.$row['color'].'; background-color: '.$row['background_color'].';';
        return '<span class="flag" style="'.$style.'">'.htmlspecialchars($text).'</span>';
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function ajaxProcessUpdatePositions()
    {
        $way = (int)Tools::getValue('way');
        $id_product_flag = (int)Tools::getValue('id');
        $positions = Tools::getValue($this->table);

        foreach ($positions as $position => $value) {
            $pos = explode('_', $value);
            if (isset($pos[2]) && (int)$pos[2] === $id_product_flag) {
                if ($product_flag = new ProductFlag((int)$pos[2])) {
                    if (isset($position) && $product_flag->updatePosition($way, $position)) {
                        echo 'ok position '.(int)$position.' for flag '.(int)$pos[1].'\r\n';
                    } else {
                        echo '{"hasError" : true, "errors" : ';
                        echo '"Can not update flag '. $id_product_flag .' to position '.(int)$position.' "}';
                    }
                } else {
                    echo '{"hasError" : true, "errors" : ';
                    echo '"This flag ('. $id_product_flag .') can t be loaded"}';
                }
                break;
            }
        }
    }

    public function getCategories(): array
    {
        $categories_array = [];
        $categories = Category::getNestedCategories($this->context->shop->id_category, false, false);

        // Fonction récursive interne
        $walk = function ($category, &$categories_array, $breadcrumb = '') use (&$walk) {
            $name = $breadcrumb ? $breadcrumb . ' > ' . $category['name'] : $category['name'];
            $categories_array[] = [
                'id_category' => $category['id_category'],
                'name' => $name,
            ];
            if (isset($category['children']) && is_array($category['children'])) {
                foreach ($category['children'] as $children) {
                    $walk($children, $categories_array, $name);
                }
            }
        };

        $walk($categories[$this->context->shop->id_category], $categories_array);

        return $categories_array;
    }

    /**
     * @param ProductFlag $object
     */
    protected function copyFromPost(&$object, $table)
    {
        parent::copyFromPost($object, $table);

        $conditions = [];
        $conditions_posted = Tools::getValue('conditions', []);

        if (!empty($conditions_posted['category'])) {
            $conditions['category'] = array_map('intval', $conditions_posted['category']);
        }

        if (!empty($conditions_posted['manufacturer']) && (int)$conditions_posted['manufacturer']) {
            $conditions['manufacturer'] = (int)$conditions_posted['manufacturer'];
        }

        if (!empty($conditions_posted['supplier']) && (int)$conditions_posted['supplier']) {
            $conditions['supplier'] = (int)$conditions_posted['supplier'];
        }

        if (!empty($conditions_posted['new']) && (int)$conditions_posted['new']) {
            $conditions['new'] = 1;
        }

        if (!empty($conditions_posted['sale']) && (int)$conditions_posted['sale']) {
            $conditions['sale'] = 1;
        }

        if (!empty($conditions_posted['quantity_from']) && (int)$conditions_posted['quantity_from']) {
            $conditions['quantity_from'] = (int)$conditions_posted['quantity_from'];
        }

        if (!empty($conditions_posted['quantity_to']) && (int)$conditions_posted['quantity_to']) {
            $conditions['quantity_to'] = (int)$conditions_posted['quantity_to'];
        }

        if (!empty($conditions_posted['exclude'])) {
            $conditions['exclude'] = array_map('intval', $conditions_posted['exclude']);
        }

        if (!empty($conditions_posted['include'])) {
            $conditions['include'] = array_map('intval', $conditions_posted['include']);
            if (isset($conditions['exclude'])) {
                $conditions['include'] = array_values(array_diff($conditions['include'], $conditions['exclude']));
            }
        }

        $object->conditions = json_encode($conditions);
    }

    /**
     * @throws PrestaShopException
     */
    public function processDuplicate()
    {
        $object = new $this->className((int) Tools::getValue($this->identifier));
        if (Validate::isLoadedObject($object)) {
            $clone = clone $object;
            $clone->active = 0;
            unset($clone->id);
            if (!$clone->add()) {
                $this->errors[] = Tools::displayError('An error occurred while duplicate an object.');
            }
        } else {
            $this->errors[] = Tools::displayError('An error occurred while duplicate an object.');
        }

        if (!count($this->errors) && isset($clone)) {
            $this->redirect_after = self::$currentIndex . '&conf=19&token=' . $this->token;
        }
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function ajaxProcessUpdateProduct() {
        $id_product = (int)Tools::getValue('id_product');
        $id_product_flag = (int)Tools::getValue('id_product_flag');
        $active = Tools::getValue('active');

        $product_flag = new ProductFlag($id_product_flag);
        if (!Validate::isLoadedObject($product_flag)) {
            $result = ['success' => false, 'error' => 'Invalid product flag'];
            exit(json_encode($result));
        }

        $conditions = json_decode($product_flag->conditions, true);
        if (!is_array($conditions)) {
            $conditions = [];
        }

        switch ($active) {
            case '1':
                // Activate flag for this product
                if (empty($conditions['include'])) {
                    $conditions['include'] = [];
                }
                if (!in_array($id_product, $conditions['include'])) {
                    $conditions['include'][] = $id_product;
                }
                // Remove from exclude if present
                if (!empty($conditions['exclude'])) {
                    $conditions['exclude'] = array_diff($conditions['exclude'], [$id_product]);
                }
                break;
            case '0':
                // Deactivate flag for this product
                if (empty($conditions['exclude'])) {
                    $conditions['exclude'] = [];
                }
                if (!in_array($id_product, $conditions['exclude'])) {
                    $conditions['exclude'][] = $id_product;
                }
                // Remove from include if present
                if (!empty($conditions['include'])) {
                    $conditions['include'] = array_diff($conditions['include'], [$id_product]);
                }
                break;
            case 'auto':
                // Remove from both include and exclude
                if (!empty($conditions['include'])) {
                    $conditions['include'] = array_diff($conditions['include'], [$id_product]);
                }
                if (!empty($conditions['exclude'])) {
                    $conditions['exclude'] = array_diff($conditions['exclude'], [$id_product]);
                }
                break;
            default:
                $result = ['success' => false, 'error' => 'Invalid active value'];
                exit(json_encode($result));
        }

        $product_flag->conditions = json_encode($conditions);
        if (!$product_flag->save()) {
            $result = ['success' => false, 'error' => 'Failed to save product flag'];
            exit(json_encode($result));
        }

        $result = ['success' => true];
        exit(json_encode($result));
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processStopAfter(): ProductFlag
    {
        /** @var ProductFlag $object */
        if (Validate::isLoadedObject($object = $this->loadObject())) {
            $object->stop_after = !$object->stop_after;
            if ($object->update()) {
                $this->redirect_after = self::$currentIndex . '&token=' . $this->token;
                $page = (int) Tools::getValue('page');
                $page = $page > 1 ? '&submitFilter' . $this->table . '=' . $page : '';
                $this->redirect_after .= '&conf=5' . $page;
            } else {
                $this->errors[] = $this->trans('An error occurred while updating the status.', [], 'Admin.Notifications.Error');
            }
        } else {
            $this->errors[] = $this->trans('An error occurred while updating the status for an object.', [], 'Admin.Notifications.Error') .
                ' <b>' . $this->table . '</b> ' .
                $this->trans('(cannot load object)', [], 'Admin.Notifications.Error');
        }

        return $object;
    }
}