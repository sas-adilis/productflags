<?php
/**
 * 2016 Adilis
 *
 * Make your shop interactive for Easter: hide objects and ask your customers to find them in order to win a
 * discount coupon. Make your brand stand out by offering an original game: a treasure hunt throughout your products.
 *
 *  @author    Adilis <support@adilis.fr>
 *  @copyright 2016 SAS Adilis
 *  @license   http://www.adilis.fr
 */

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_flag` (
    `id_product_flag` int(11) NOT NULL AUTO_INCREMENT,
    `color` varchar(12) NOT NULL DEFAULT \'transparent\',
    `background_color` varchar(12) NOT NULL DEFAULT \'transparent\',
    `position` int(10) unsigned NOT NULL DEFAULT \'0\',
    `stop_after` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
    `conditions` TEXT NULL,
    `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
    `from` datetime DEFAULT NULL,
    `to` datetime DEFAULT NULL,
    `date_add` datetime NOT NULLg
    `date_upd` datetime NOT NULL,
    PRIMARY KEY (`id_product_flag`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'product_flag_lang` (
  `id_product_flag` int(10) unsigned NOT NULL,
  `id_lang` int(10) unsigned NOT NULL,
  `text` varchar(128) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id_product_flag`,`id_lang`)
) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
