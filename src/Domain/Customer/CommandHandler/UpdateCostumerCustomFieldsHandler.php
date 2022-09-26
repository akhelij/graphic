<?php
/**
 * 2007-2019 Friends of PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0).
 * It is also available through the world-wide-web at this URL: https://opensource.org/licenses/AFL-3.0
 */

namespace GraphicCustomManager\Domain\Customer\CommandHandler;

use GraphicCustomManager\Domain\Customer\Command\UpdateCustomerCustomFieldsCommand;
use GraphicCustomManager\Entity\Customer;
use GraphicCustomManager\Repository\CustomerRepository;
use PrestaShopException;

/**
 * used to update customers review status.
 */
class UpdateCostumerCustomFieldsHandler extends AbstractCustomerHandler
{
    /**
     * @var CustomerRepository
     */
    private $customerRepository;

    /**
     * @param CustomerRepository $customerRepository
     */
    public function __construct(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    public function handle(UpdateCustomerCustomFieldCommand $command)
    {
        $customerId = $this->customerRepository->findIdByCustomer($command->getCustomerId()->getValue());

        $customer = new Customer($customerId);

        if (0 >= $customer->id) {
            $customer = $this->createCustomer($command->getCustomerId()->getValue());
        }

        $customer->iban = $command->getIban();
        $customer->bic = $command->getBic();
        $customer->portable = $command->getPortable();
        $customer->civilite = $command->getCivilite();

        try {
            if (false === $customer->update()) {
                throw new CannotSaveCustomFieldsException(
                    sprintf('Failed to update fields for customer with id "%s"', $customer->id)
                );
            }
        } catch (PrestaShopException $exception) {
            /*
             * @see https://devdocs.prestashop.com/1.7/development/architecture/domain-exceptions/
             */
            throw new CannotToggleAllowedToReviewStatusException(
                'An unexpected error occurred when updating customer CUSTOM FIELDS'
            );
        }
    }
}