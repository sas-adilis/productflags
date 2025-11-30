<?php

class ProductFlag extends ObjectModel
{
    public static $cache_product_supplier = null;
    public static $cache_product_category = null;
    public static $cache_product_flag = [];
    public static $cache_flags = [];

    public $background_color = '#000000';
    public $color = '#ffffff';
    public $text;
    public $active = 0;
    public $position;
    public $from;
    public $to;
    public $conditions = null;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'primary' => 'id_product_flag',
        'table' => 'product_flag',
        'multishop' => false,
        'multilang' => true,
        'fields' => [
            'text' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'lang' => true, 'required' => true, 'size' => 128],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor'],
            'background_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor'],
            'position' => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
            'conditions' => ['type' => self::TYPE_STRING],
            'from' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true],
            'to' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'],
        ]
    ];

    public static function getProductFlagsIds($id_product)
    {
        $product_flags = Db::getInstance()->executeS('
            SELECT id_product_flag
            FROM ' . _DB_PREFIX_ . 'product_flag_product
            WHERE id_product = ' . (int)$id_product
        );


        if (!$product_flags) {
            return [];
        }

        return array_column($product_flags, 'id_product_flag');
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public static function getFlags(int $id_lang, bool $active_only = true, bool $check_dates = true)
    {
        $key = $id_lang . '_' . (int) $active_only;
        if (isset(self::$cache_flags[$key])) {
            return self::$cache_flags[$key];
        }

        $query = new DbQuery();
        $query->select('*');
        $query->from('product_flag', 'p');
        $query->innerJoin('product_flag_lang', 'l', 'p.id_product_flag = l.id_product_flag AND l.id_lang = ' . (int) $id_lang);
        if ($check_dates) {
            $query->where('`from` <= NOW()');
            $query->where('`to` >= NOW()');
        }
        if ($active_only) {
            $query->where('p.active = 1');
        }
        $query->orderBy('p.position ASC');
        $flags = Db::getInstance()->executeS($query);

        foreach ($flags as &$flag) {
            $flag['conditions'] = json_decode($flag['conditions'], true);
        }

        self::$cache_flags[$key] = $flags;
        return $flags;
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public static function getProductFlags(array $product, $active_only = true)
    {
        $id_lang = (int) Context::getContext()->language->id;
        $id_product = (int) $product['id_product'];

        $key = $id_lang . '_' . (int) $id_product;
        if (isset(self::$cache_product_flag[$key])) {
            return self::$cache_product_flag[$key];
        }

        $id_manufacturer = (int) $product['id_manufacturer'];
        $quantity = (int) $product['quantity_all_versions'];

        $flags = self::getFlags($id_lang, $active_only);

        if (!self::isSupplierCacheReady()) {
            $ids_suppliers = [];
            foreach ($flags as $flag) {
                $conditions = $flag['conditions'] ?? [];
                if (!empty($conditions['supplier']) && (int)$conditions['supplier']) {
                    $ids_suppliers[] = (int)$conditions['supplier'];
                }
            }
            if (count($ids_suppliers) > 0) {
                $ids_suppliers = array_unique($ids_suppliers);
                self::prepareProductSupplierCache($ids_suppliers);
            }
        }

        if (!self::isCategoryCacheReady()) {
            $id_categories = [];
            foreach ($flags as $flag) {
                $conditions = $flag['conditions'] ?? [];
                if (!empty($conditions['category'] && is_array($conditions['category']))) {
                    $id_categories  = array_merge($id_categories, $conditions['category']);
                }
            }
            if (count($id_categories) > 0) {
                $id_categories = array_unique($id_categories);
                self::prepareProductCategoryCache($id_categories);
            }
        }



        $product_flags = [];
        foreach ($flags as $flag) {
            $conditions = $flag['conditions'] ?? [];

            if (!empty($conditions['include']) && in_array($id_product, $conditions['include'])) {
                $product_flags[] = $flag;
                continue;
            }

            if (!empty($conditions['exclude']) && in_array($id_product, $conditions['exclude'])) {
                continue;
            }

            if (!empty($conditions['manufacturer']) && (int)$conditions['manufacturer'] && (int)$conditions['manufacturer'] != $id_manufacturer) {
                continue;
            }

            if (!empty($conditions['supplier']) && (int)$conditions['supplier'] && !self::isProductFromSupplier($id_product, (int)$conditions['supplier'])) {
                continue;
            }

            if (!empty($conditions['new']) && (int)$conditions['new'] && !(int)$product['new']) {
                continue;
            }

            if (!empty($conditions['sale']) && (int)$conditions['sale'] && !(int)$product['sale']) {
                continue;
            }

            if (!empty($conditions['quantity_from']) && (int)$conditions['quantity_from'] && (int)$conditions['quantity_from'] > $quantity) {
                continue;
            }

            if (!empty($conditions['quantity_to']) && (int)$conditions['quantity_to'] && (int)$conditions['quantity_to'] < $quantity) {
                continue;
            }

            if (!empty($conditions['category'] && is_array($conditions['category']))) {
                $has_at_least_one = false;
                foreach ($conditions['category'] as $id_category) {
                    if (self::isProductInCategory($id_product, (int)$id_category)) {
                        $has_at_least_one = true;
                        break;

                    }
                }

                if (!$has_at_least_one) {
                    continue;
                }
            }

            $product_flags[] = $flag;
        }

        self::$cache_product_flag[$key] = $product_flags;
        return $product_flags;
    }

    public static function saveProductFlags($id_product, $flags)
    {
        Db::getInstance()->delete('product_flag_product', 'id_product = ' . (int)$id_product);
        if (empty($flags)) {
            return;
        }
        $values = [];
        foreach ($flags as $flag) {
            $values[] = [
                'id_product' => (int)$id_product,
                'id_product_flag' => (int)$flag
            ];
        }

        Db::getInstance()->insert('product_flag_product', $values);
    }

    private static function isProductFromSupplier(int $id_product, int $id_supplier): bool
    {
        if (!self::isSupplierCacheReady()) {
            return false;
        }

        return isset(self::$cache_product_supplier[$id_supplier][$id_product]);
    }

    private static function isProductInCategory(int $id_product, int $id_category): bool
    {
        if (!self::isCategoryCacheReady()) {
            return false;
        }
        return isset(self::$cache_product_category[$id_category][$id_product]);
    }

    private static function isSupplierCacheReady(): bool
    {
        return self::$cache_product_supplier !== null;
    }

    private static function isCategoryCacheReady(): bool
    {
        return self::$cache_product_category !== null;
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    private static function prepareProductSupplierCache(array $ids_suppliers)
    {
        if (!self::isSupplierCacheReady()) {
            $query = new DbQuery();
            $query->select('ps.id_product, p.id_supplier');
            $query->from('product_supplier', 'ps');
            $query->where('p.id_supplier IN (' . implode(',', array_map('intval', $ids_suppliers)) . ')');

            $results = Db::getInstance()->executeS($query);
            self::$cache_product_supplier = [];
            foreach ($results as $row) {
                if (!isset(self::$cache_product_supplier[$row['id_supplier']])) {
                    self::$cache_product_supplier[$row['id_supplier']] = [];
                }
                self::$cache_product_supplier[$row['id_supplier']][$row['id_product']] = 1;
            }
        }
    }

    private static function prepareProductCategoryCache(array $id_categories)
    {
        if (!self::isCategoryCacheReady()) {
            $query = new DbQuery();
            $query->select('cp.id_product, cp.id_category');
            $query->from('category_product', 'cp');
            $query->where('cp.id_category IN (' . implode(',', array_map('intval', $id_categories)) . ')');

            $results = Db::getInstance()->executeS($query);
            self::$cache_product_category = [];
            foreach ($results as $row) {
                if (!isset(self::$cache_product_category[$row['id_category']])) {
                    self::$cache_product_category[$row['id_category']] = [];
                }
                self::$cache_product_category[$row['id_category']][$row['id_product']] = 1;
            }
        }
    }

    public function add($auto_date = true, $null_values = false)
    {
        if ($this->position <= 0) {
            $this->position = self::getHigherPosition() + 1;
        }
        return parent::add($auto_date, $null_values); // TODO: Change the autogenerated stub
    }

    public function delete()
    {
        return parent::delete() && self::cleanPositions();
    }

    public function updatePosition($way, $position, $id_product_flag = null)
    {
        if (!$id_product_flag) {
            $id_product_flag = $this->id;
        }
        $moved_product_flag = Db::getInstance()->getRow(
            'SELECT position, id_product_flag FROM '._DB_PREFIX_.'product_flag
            WHERE `id_product_flag` = '.(int)$id_product_flag.'
            ORDER BY `position` ASC'
        );

        if (!$moved_product_flag || !isset($position)) {
            return false;
        }

        return (
            Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'product_flag`
            SET `position`= `position` '.($way ? '- 1' : '+ 1').'
            WHERE `position`
            '.($way
                    ? '> '.(int)$moved_product_flag['position'].' AND `position` <= '.(int)$position
                    : '< '.(int)$moved_product_flag['position'].' AND `position` >= '.(int)$position
                )
            ) && Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'product_flag`
                SET `position` = '.(int)$position.'
                WHERE `id_product_flag`='.(int)$moved_product_flag['id_product_flag']
            )
        );
    }

    public static function cleanPositions()
    {
        $return = true;
        $result = Db::getInstance()->executeS('
            SELECT id_product_flag
            FROM '._DB_PREFIX_.'product_flag ORDER BY position
        ');

        $position = 0;
        foreach ($result as $value) {
            $return &= Db::getInstance()->execute(
                'UPDATE `'._DB_PREFIX_.'product_flag`
                SET `position` = '.(int)$position++.'
                WHERE `id_product_flag` = '.(int)$value['id_product_flag']
            );
        }
        return $return;
    }

    public static function getHigherPosition()
    {
        $position = DB::getInstance()->getValue('SELECT MAX(position) FROM '._DB_PREFIX_.'product_flag');
        return (is_numeric($position)) ? $position : -1;
    }
}