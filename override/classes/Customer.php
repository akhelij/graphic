<?php

class Customer extends CustomerCore
{

    public $iban;
    public $bic;
    public $portable;
    public $civilite;

    public function __construct($id = null) {

        self::$definition['fields']['iban'] = [
            'type' => self::TYPE_STRING,
            'required' => false, 'size' => 255
        ];

        self::$definition['fields']['bic']     = [
            'type' => self::TYPE_STRING,
            'required' => false, 'size' => 255
        ];

        self::$definition['fields']['portable'] = [
            'type' => self::TYPE_STRING,
            'required' => false, 'size' => 255
        ];

        self::$definition['fields']['civilite'] = [
            'type' => self::TYPE_STRING,
            'required' => false, 'size' => 255
        ];
        parent::__construct($id);
    }
}