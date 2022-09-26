<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace GraphicCustomManager\Domain\Customer\Command;

use PrestaShop\PrestaShop\Core\Domain\Customer\Exception\CustomerException;
use PrestaShop\PrestaShop\Core\Domain\Customer\ValueObject\CustomerId;

/**
 * used to update customers review status.
 *
 * @see \GraphicCustomManager\Domain\Costumer\CommandHandler\UpdateCostumerCustomFieldsHandler how the data is handled.
 */
class UpdateCustomerCustomFieldsCommand
{
    /**
     * @var CustomerId
     */
    private $customerId;

    /**
     * @var string
     */
    private $iban;

    /**
     * @var string
     */
    private $bic;

    /**
     * @var string
     */
    private $portable;

    /**
     * @var string
     */
    private $civilite;

    /**
     * @param int $customerId
     * @param string $iban
     * @param string $bic
     * @param string $portable
     * @param string $civilite
     *
     * @throws CustomerException
     */
    public function __construct($customerId, $iban, $bic, $portable, $civilite)
    {
        $this->customerId = new CustomerId($customerId);
        $this->iban = $iban;
        $this->bic = $bic;
        $this->portable = $portable;
        $this->civilite = $civilite;
    }

    /**
     * @return CustomerId
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @return string
     */
    public function getIban()
    {
        return $this->iban;
    }


    /**
     * @return string
     */
    public function getBic()
    {
        return $this->bic;
    }

    /**
     * @return string
     */
    public function getPortable()
    {
        return $this->portable;
    }

    /**
     * @return string
     */
    public function getCivilite()
    {
        return $this->civilite;
    }
}