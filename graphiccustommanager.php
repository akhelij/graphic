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

if (!defined('_PS_VERSION_')) {
    exit;
}

use GraphicCustomManager\Domain\Customer\Exception\CustomerException;
use GraphicCustomManager\Domain\Customer\Command\UpdateCustomerCustomFieldsCommand;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Employee\EmployeeNameWithAvatarColumn;
use GraphicCustomManager\Domain\Customer\Command\UpdateCustomerCustomFieldCommand;

class GraphicCustomManager extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'graphiccustommanager';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'globalitik';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('graphic custom module');
        $this->description = $this->l('graphic custom module');

        $this->confirmUninstall = $this->l('');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('GRAPHIC_CUSTOM_MANAGER_LIVE_MODE', false);

        include(dirname(__FILE__) . '/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionOrderDetail') &&
            $this->registerHook('actionOrderGridDefinitionModifier')&&
            $this->registerHook('actionOrderGridQueryBuilderModifier')&&
            $this->registerHook('actionCustomerFormBuilderModifier')&&
            $this->registerHook('actionAfterCreateCustomerFormHandler')&&
            $this->registerHook('actionAfterUpdateCustomerFormHandler');

    }

    public function uninstall()
    {
        Configuration::deleteByName('GRAPHIC_CUSTOM_MANAGER_LIVE_MODE');

        include(dirname(__FILE__) . '/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitGraphic_custom_managerModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGraphic_custom_managerModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'GRAPHIC_CUSTOM_MANAGER_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'GRAPHIC_CUSTOM_MANAGER_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'GRAPHIC_CUSTOM_MANAGER_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'GRAPHIC_CUSTOM_MANAGER_LIVE_MODE' => Configuration::get('GRAPHIC_CUSTOM_MANAGER_LIVE_MODE', true),
            'GRAPHIC_CUSTOM_MANAGER_ACCOUNT_EMAIL' => Configuration::get('GRAPHIC_CUSTOM_MANAGER_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'GRAPHIC_CUSTOM_MANAGER_ACCOUNT_PASSWORD' => Configuration::get('GRAPHIC_CUSTOM_MANAGER_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookActionCustomerFormBuilderModifier(array $params)
    {
        /** @var FormBuilderInterface $formBuilder */
        $formBuilder = $params['form_builder'];
        $formBuilder->add('iban', TextType::class, [
            'label' => $this->getTranslator()->trans('IBAN', [], 'Modules.GraphicCustomManager'),
            'required' => false,
        ]);
        $formBuilder->add('bic', TextType::class, [
            'label' => $this->getTranslator()->trans('BIC', [], 'Modules.GraphicCustomManager'),
            'required' => false,
        ]);
        $formBuilder->add('bic', TextType::class, [
            'label' => $this->getTranslator()->trans('BIC', [], 'Modules.GraphicCustomManager'),
            'required' => false,
        ]);
        $formBuilder->add('portable', TextType::class, [
            'label' => $this->getTranslator()->trans('Mobile', [], 'Modules.GraphicCustomManager'),
            'required' => false,
        ]);
        $formBuilder->add('civilite', TextType::class, [
            'label' => $this->getTranslator()->trans('Civilité', [], 'Modules.GraphicCustomManager'),
            'required' => false,
        ]);

        $formBuilder->setData($params['data']);
    }

    public function hookActionAfterUpdateCustomerFormHandler(array $params)
    {
        $this->updateCustomerCustomFields($params);
    }

    public function hookActionAfterCreateCustomerFormHandler(array $params)
    {
        $this->updateCustomerCustomFields($params);
    }

    private function updateCustomerCustomFields(array $params)
    {
        $customerId = $params['id'];
        /** @var array $customerFormData */
        $customerFormData = $params['form_data'];
        $iban = $customerFormData['iban'];
        $bic = $customerFormData['bic'];
        $portable = $customerFormData['portable'];
        $civilite = $customerFormData['civilite'];

        /** @var CommandBusInterface $commandBus */
        $commandBus = $this->get('prestashop.core.command_bus');

        try {
            /*
             * This part demonstrates the usage of CQRS pattern command to perform write operation for Customer entity.
             * @see https://devdocs.prestashop.com/1.7/development/architecture/cqrs/ for more detailed information.
             *
             * As this is our recommended approach of writing the data but we not force to use this pattern in modules -
             * you can use directly an entity here or wrap it in custom service class.
             */
            $commandBus->handle(new UpdateCustomerCustomFieldsCommand(
                $customerId,
                $iban,
                $bic,
                $portable,
                $civilite
            ));
        } catch (CustomerException $exception) {
            $this->handleException($exception);
        }
    }

    public function hookActionOrderGridDefinitionModifier(array $params) {
        if(Context::getContext()->employee->id_profile != 4) {
            /** @var GridDefinitionInterface $orderGridDefinition */
            $orderGridDefinition = $params['definition'];

            $employeeColumn = new EmployeeNameWithAvatarColumn('employee');
            $employeeColumn->setName('Employee');
            $employeeColumn->setOptions([
                'field' => 'employee',
            ]);

            // die(dump($params['select']));

            /** @var ColumnCollection */
            $columns = $orderGridDefinition->getColumns();
            $columns->addBefore('date_add', $employeeColumn);

            $orderGridDefinition->getFilters()->add(
                (new Filter('employee', TextType::class))
                    ->setAssociatedColumn('employee')
            );
        }
    }

    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        /** @var QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        if(Context::getContext()->employee->id_profile == 4) {

            $searchQueryBuilder->andWhere('o.id_author = :employee_id');
            $searchQueryBuilder->setParameter('employee_id', Context::getContext()->employee->id);

        } else {
            $searchQueryBuilder->addSelect(
                'o.id_author, e.lastname AS `employee`'
            );

            $searchQueryBuilder->leftJoin(
                'o',
                '`' . pSQL(_DB_PREFIX_) . 'employee`',
                'e',
                'e.`id_employee` = o.`id_author`'
            );

            /** @var CustomerFilters $searchCriteria */
            $searchCriteria = $params['search_criteria'];

            if ('employee' === $searchCriteria->getOrderBy()) {
                $searchQueryBuilder->orderBy('employee', $searchCriteria->getOrderWay());
            }

            foreach ($searchCriteria->getFilters() as $filterName => $filterValue) {
                if ('employee' === $filterName) {
                    $searchQueryBuilder->andWhere("e.lastname LIKE :$filterName");
                    $searchQueryBuilder->setParameter($filterName, '%'.$filterValue.'%');
                }
            }
        }




    }

}
