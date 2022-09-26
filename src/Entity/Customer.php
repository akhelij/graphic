<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace GraphicCustomManager\Entity;

use PrestaShop\PrestaShop\Adapter\Entity\ObjectModel;

class Customer extends ObjectModel
{
    /**
     * @var int
     */
    public $id_customer;

    /**
     * @var string
     */
    public $iban;

    public static $definition = [
        'table' => 'customer',
        'primary' => 'id_customer',
        'fields' => [
            'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'iban' => ['type' => self::TYPE_STRING, 'size' => 255],
            'bic' => ['type' => self::TYPE_STRING, 'size' => 255],
            'portable' => ['type' => self::TYPE_STRING, 'size' => 255],
            'civilite' => ['type' => self::TYPE_STRING, 'size' => 255],
        ],
    ];
}