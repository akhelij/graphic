<?php
/**
 * 2007-2022 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2022 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
$sql = array();

//$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'graphiccustommanager` (
//    `id_graphic_custom_manager` int(11) NOT NULL AUTO_INCREMENT,
//    PRIMARY KEY  (`id_graphic_custom_manager`)
//) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'ORDER_STATE_LANG`(`id_order_state`, `id_lang`, `name`, `template`) VALUES ("2022","1","En attente de paiement","payment")';
$sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'ORDER_STATE`(`id_order_state`, `invoice`, `send_email`, `module_name`, `color`, `unremovable`, `hidden`, `logable`, `delivery`, `shipped`, `paid`, `pdf_invoice`, `pdf_delivery`, `deleted`) VALUES ("2022","0","0","","#34209E","0","0","0","0","0","0","0","0","0")';
$sql[] = 'ALTER TABLE  `' . _DB_PREFIX_ . 'CUSTOMER` 
        ADD COLUMN `iban` VARCHAR(255) NULL AFTER `birthday`,
        ADD COLUMN `bic` VARCHAR(255) NULL AFTER `birthday`,
        ADD COLUMN `portable` VARCHAR(255) NULL AFTER `birthday`,
        ADD COLUMN `civilite` VARCHAR(255) NULL AFTER `birthday`';
$sql[] = 'ALTER TABLE  `' . _DB_PREFIX_ . 'ORDERS` ADD COLUMN `id_author` int(11) NULL DEFAULT 0 AFTER `id_order`';

foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
