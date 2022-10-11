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
            $this->registerHook('actionOrderGridDefinitionModifier') &&
            $this->registerHook('actionOrderGridQueryBuilderModifier') &&
            $this->registerHook('displayAdminOrderTop') &&
            $this->registerHook('actionCustomerFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateCustomerFormHandler') &&
            $this->registerHook('actionAfterUpdateCustomerFormHandler') &&
            $this->registerHook('actionAdminControllerInitAfter') &&
            $this->registerHook('additionalCustomerFormFields') &&
            $this->registerHook('displayAdminCustomers');
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
        //
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

        if (Tools::getValue('action') && Tools::getValue('action') == 'addorder') {
            $this->context->controller->addJS($this->_path . 'views/js/addorder.js');
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

    /**
     * ---------------------------------------------------------------------------
     * Customer
     * ---------------------------------------------------------------------------
     */

    public function hookDisplayAdminCustomers(array $params)
    {
        $customer = New CustomerCore($params['id_customer']);
        return '<div class="col-md-3">

                <div class="card">

                  <h3 class="card-header">

                    <i class="material-icons">info_outline</i>

                    ' . $this->l("Informations complementaire") . '

                  </h3>

                  <div class="card-body">

                    <div class="row mb-1">
                      <div class="col-4 text-right">
                        IBAN
                      </div>
                      <div class="customer-social-title col-8">
                        '. $customer->iban .'
                      </div>
                    </div>
                    
                    <div class="row mb-1">
                      <div class="col-4 text-right">
                        BIC
                      </div>
                      <div class="customer-social-title col-8">
                        '. $customer->bic .'
                      </div>
                    </div>
                    
                    <div class="row mb-1">
                      <div class="col-4 text-right">
                        Portable
                      </div>
                      <div class="customer-social-title col-8">
                        '. $customer->portable .'
                      </div>
                    </div>
                    
                    <div class="row mb-1">
                      <div class="col-4 text-right">
                        Civilité
                      </div>
                      <div class="customer-social-title col-8">
                        '. $customer->civilite .'
                      </div>
                    </div>

                  </div>

                </div>

                </div>';


    }

    public function hookAdditionalCustomerFormFields($params) {

        return [
            (new FormField)
                ->setName('iban')
                ->setType('text')
                ->setRequired(false)
                ->setLabel($this->l('IBAN')),
            (new FormField)
                ->setName('bic')
                ->setType('text')
                ->setRequired(false)
                ->setLabel($this->l('BIC')),
            (new FormField)
                ->setName('portable')
                ->setType('text')
                ->setRequired(false)
                ->setLabel($this->l('Portable')),
            (new FormField)
                ->setName('civilite')
                ->setType('text')
                ->setRequired(false)
                ->setLabel($this->l('Civilité'))
        ];
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
            'required' => false
        ]);
        $formBuilder->add('portable', TextType::class, [
            'label' => $this->getTranslator()->trans('Mobile', [], 'Modules.GraphicCustomManager'),
            'required' => false
        ]);
        $formBuilder->add('civilite', TextType::class, [
            'label' => $this->getTranslator()->trans('Civilité', [], 'Modules.GraphicCustomManager'),
            'required' => false
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

        \Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'customer SET `iban` = \'' . $iban . '\', `bic` = \'' . $bic . '\', `portable` = \'' . $portable . '\', `civilite`=\'' . $civilite . '\' WHERE `id_customer`=
        '.$customerId);
    }

    /**
     * ---------------------------------------------------------------------------
     * Customer
     * ---------------------------------------------------------------------------
     */



    /**
     * ---------------------------------------------------------------------------
     * Order
     * ---------------------------------------------------------------------------
     */

    public function hookActionAdminControllerInitAfter(array $params) {
        if(isset($_REQUEST['print_cgu']) && $_REQUEST['print_cgu']) {
            $this->generatePDF($_REQUEST['order_id'], 'D');
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
                'o.id_author, e.firstname AS `employee`'
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
                    $searchQueryBuilder->andWhere("e.firstname LIKE :$filterName");
                    $searchQueryBuilder->setParameter($filterName, '%'.$filterValue.'%');
                }
            }
        }




    }

    public function hookDisplayAdminOrderTop(array $params)
    {
        $more_info = '';
        $order_downloaded = $this->checkIfOrderIsDownloaded($params["id_order"]);
        if(count($order_downloaded)) {
            $more_info .= " <p class='btn'> File Downloaded at : <span style='color:#E74C3C'>". $order_downloaded[0]["date_download"] ."</span> By : <span style='color:#E74C3C'>".$order_downloaded[0]["ip_downloader"] . "</span></p>";
        }

        $html = "";
        if(isset($_REQUEST['order_signature']) && $_REQUEST['order_signature'] && isset($_FILES['fileToUpload'])) {

            $result = $this->uploadFile($params["id_order"]);

            if($result['success']) {
                $html .= $this->getDownloadDocBtn($result['data']['path']);
            } else {
                $html .= "<button class='btn' style='background-color:#E74C3C;color:#fff'>".$result["message"]."</button>";
            }
        } else {
            $result = $this->checkIfOrderIsSigned($params["id_order"]);

            if(count($result)) {
                $html .= $this->getDownloadDocBtn($result[0]['signed_document']);
            }
        }

        return '
            <div class="order-actions col-md-12"">
                 <form class="order-actions-print" method="post" action="'.$_SERVER['REQUEST_URI'].'">                    
                    <div class="input-group">
                      <input type="hidden" name="print_cgu" value="true">
                      <input type="hidden" name="order_id" value="'.$params["id_order"].'">
                      <button type="submit" class="btn btn-action">
                        <i class="material-icons" aria-hidden="true">print</i>
                        Imprimer CGU
                      </button>
                    </div>
                  </form>                  
                  
                  
                  <form class="order-actions-print" method="post" action="'.$_SERVER['REQUEST_URI'].'"  enctype="multipart/form-data">
                    <div class="input-group">
                    '. $html .'
                      <input type="hidden" name="order_signature" value="true">
                      <input type="hidden" name="order_id" value="'.$params["id_order"].'"> 
                      <input type="file" name="fileToUpload" id="fileToUpload"  class="btn">
                      <button type="submit" value="Signer la commande" name="submit" class="btn btn-action">                     
                        <i class="material-icons" aria-hidden="true">upload</i>     
                        Signer la commande                   
                      </button>
                    </div>
                  </form>
            </div> '. $more_info.'
        ';
//        $this->get('prestashop.module.demovieworderhooks.repository.order_repository');
    }



    public function checkIfOrderIsSigned($id_order) {
        $query = new DbQuery();
        $query->select('signature_date, signed_document');
        $query->from('orders', 'o');
        $query->where('`id_order` = ' . (int) $id_order);
        $query->where('`signed_document` IS NOT NULL');

        return Db::getInstance()->executeS($query);
    }

    public function checkIfOrderIsDownloaded($id_order) {
        $query = new DbQuery();
        $query->select('date_download, ip_downloader');
        $query->from('orders', 'o');
        $query->where('`id_order` = ' . (int) $id_order);
        $query->where('`date_download` IS NOT NULL');

        return Db::getInstance()->executeS($query);
    }

    public function getDownloadDocBtn($path) {
        return '<a href="'.'\\..\\'.str_replace("/","\\",$path).'" class="btn btn-action" target="_blank">
                    <i class="material-icons" aria-hidden="true">print</i>
                    Imprimer le document signer
                  </a>';
    }

    public function uploadFile($order_id) {
        $target_dir = "../upload/";
        $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
        $pdfFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        if ($_FILES["fileToUpload"]["size"] > 5000000) {
            return [
                "success" => false,
                "message" => "Sorry, your file is too large."
            ];
        }

        if($pdfFileType != "pdf") {
            return [
                "success" => false,
                "message" => "Sorry, only PDF files are allowed."
            ];
        }

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {

            $this->saveDateAndPathOfSignature($order_id, $target_file);
            return [
                "success" => true,
                "message" => "Order Signed.",
                "data"    => [
                    "path" => $target_file
                ]
            ];
        }

        return [
            "success" => false,
            "message" => "Sorry, there was an error uploading your file."
        ];
    }

    public function saveDateAndPathOfSignature($order_id, $path) {
        \Db::getInstance()->execute('UPDATE ' . _DB_PREFIX_ . 'orders SET `signature_date`="' . date('Y-m-d H:i:s', time()) . '", `signed_document`="' . $path . '", `current_state`= 2023 WHERE `id_order`=
                '.strval($order_id));
    }

    /**
     * ---------------------------------------------------------------------------
     * Order
     * ---------------------------------------------------------------------------
     */




    /**
     * ---------------------------------------------------------------------------
     * PDF GENERATOR
     *
     * Destination where to send the document. It can be one of the following:
     * I: send the file inline to the browser. The PDF viewer is used if available.
     * D: send to the browser and force a file download with the name given by name.
     * F: save to a local file with the name given by name (may include a path).
     * S: return the document as a string.
     *
     * ---------------------------------------------------------------------------
     */

    public function getPdfAsAttachement($content)
    {
        return ['content' => $content, 'name' => 'CustomPDF', 'mime' => 'application/pdf'];
    }

    public function generatePDF($id_order, $output = 'F' )
    {
        $order = new Order((int) $id_order);
        $customer = new Customer((int) $order->id_customer);
        $myCustomSlipVarsForPdfContent = $this->myContentDatasPresenter($customer);
        $myCustomSlipVarsForPdfFooter  = $this->myFooterDatasPresenter($order);
        $myCustomSlipVarsForPdfHeader  = $this->myHeaderDatasPresenter($order);
        $pdfGen = new PDFGenerator();
        $pdfGen->setFontForLang(Context::getContext()->language->iso_code);
        $pdfGen->startPageGroup();
        $pdfGen->createHeader($this->getHeader($myCustomSlipVarsForPdfHeader));
        $pdfGen->createFooter($this->getFooter($myCustomSlipVarsForPdfFooter));
        $pdfGen->createContent($this->getPdfContent($myCustomSlipVarsForPdfContent));
        $pdfGen->writePage();
        if ($output != 'S') {
            $pdfGen->render('my_custom_pdf.pdf', $output);
        } else {
            return $pdfGen->render('my_custom_pdf.pdf', $output);
        }
    }

    /**
     * Returns the template's HTML content.
     *
     * @return string HTML content
     */
    public function getPdfContent(array $myCustomSlipVarsForPdfContent): string
    {
        $this->context->smarty->assign($myCustomSlipVarsForPdfContent);
        $tpls = array(
            'style_tab'     => $this->context->smarty->fetch(_PS_ROOT_DIR_.'/pdf/invoice.style-tab.tpl'),
            'addresses_tab' => $this->context->smarty->fetch(_PS_ROOT_DIR_.'/pdf/invoice.addresses-tab.tpl'),
            'product_tab'   => $this->context->smarty->fetch(_PS_ROOT_DIR_.'/pdf/invoice.product-tab.tpl'),
        );
        $this->context->smarty->assign($tpls);

        return $this->context->smarty->fetch(_PS_ROOT_DIR_.'/pdf/invoice.tpl');
    }

    /**
     * Returns the template's HTML footer.
     *
     * @return string HTML footer
     */
    public function getFooter(array $myCustomSlipVarsForPdfFooter): string
    {
        $this->context->smarty->assign($myCustomSlipVarsForPdfFooter);
        return $this->context->smarty->fetch(_PS_ROOT_DIR_.'/pdf/footer.tpl');
    }

    /**
     * Returns the template's HTML header.
     *
     * @return string HTML header
     */
    public function getHeader(array $myCustomSlipVarsForPdfHeader): string
    {
        $this->context->smarty->assign($myCustomSlipVarsForPdfHeader);
        return $this->context->smarty->fetch(_PS_ROOT_DIR_.'/pdf/header.tpl');
    }


    /**
     * Format your order data here for pdf content : ['tpl_var_name'=>'tpl_value']
     *
     * @return array
     */
    public function myContentDatasPresenter(Customer $customer): array
    {
        return [
            "summary_tab" => $customer->firstname.' '.$customer->lastname,
            "note_tab" => $customer->email
        ];
    }

    /**
     * Format your order data here for pdf footer : ['tpl_var_name'=>'tpl_value']
     *
     * @return array
     */
    public function myFooterDatasPresenter(Order $myOrderObject): array
    {
        return [
            "free_text" => "Lorem Ipsum"
        ];
    }

    /**
     * Format your order data here for pdf header : ['tpl_var_name'=>'tpl_value']
     *
     * @return array
     */
    public function myHeaderDatasPresenter(Order $myOrderObject): array
    {
        return [
            "title" => $myOrderObject->reference
        ];
    }

    /**
     * ---------------------------------------------------------------------------
     * PDF GENERATOR
     * ---------------------------------------------------------------------------
     */



    /**
     * ---------------------------------------------------------------------------
     * MAIL
     * ---------------------------------------------------------------------------
     */

        public function sendMail($params, $file_attachment)
        {
            Mail::Send(

                $this->context->language->id,

                'forward_msg',

                $this->trans(

                    'Fwd: Customer message',

                    [],

                    'Emails.Subject',

                    $this->context->language->locale

                ),

                $params,

                "gmoail101@gmail.com",//$employee->email,

                "Mohamed Akhelij", //$employee->firstname . ' ' . $employee->lastname,

                "gmoail101@gmail.com",//$current_employee->email,

                "Mohamed Akhelij", //$current_employee->firstname . ' ' . $current_employee->lastname,

                $file_attachment,

                null,

                _PS_MAIL_DIR_,

                true

            );
        }

    /**
     * ---------------------------------------------------------------------------
     * MAIL
     * ---------------------------------------------------------------------------
     */

}
