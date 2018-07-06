<?php

namespace HoerElectronic\ContaoImport;

/**
 * Class Integrity
 *
 * @property \Template|object Template
 */
class Export extends \BackendModule {

	public function generate() {

		return "<div>Hallo lexA!</div>";
	}

	protected function compile() {

	}
}





// <?php

// /**
//  * Isotope eCommerce for Contao Open Source CMS
//  *
//  * Copyright (C) 2009-2016 terminal42 gmbh & Isotope eCommerce Workgroup
//  *
//  * @link       https://isotopeecommerce.org
//  * @license    https://opensource.org/licenses/lgpl-3.0.html
//  */

// namespace Isotope\BackendModule;

// use Isotope\Interfaces\IsotopeIntegrityCheck;


// /**
//  * Class Integrity
//  *
//  * @property \Template|object Template
//  */
// class Integrity extends \BackendModule
// {

//     /**
//      * Template
//      * @var string
//      */
//     protected $strTemplate = 'be_iso_integrity';

//     /**
//      * Generate the module
//      * @return string
//      */
//     public function generate()
//     {
//         if (!\BackendUser::getInstance()->isAdmin) {
//             return '<p class="tl_gerror">'.$GLOBALS['TL_LANG']['tl_iso_integrity']['permission'].'</p>';
//         }

//         \System::loadLanguageFile('tl_iso_integrity');

//         return parent::generate();
//     }

//     /**
//      * Generate the module
//      */
//     protected function compile()
//     {
//         /** @var IsotopeIntegrityCheck[] $arrChecks */
//         $arrChecks = array();
//         $arrTasks = array();
//         $blnReload = false;

//         if ('tl_iso_integrity' === \Input::post('FORM_SUBMIT')) {
//             $arrTasks = (array) \Input::post('tasks');
//         }

//         $this->Template->hasFixes = false;

//         foreach ($GLOBALS['ISO_INTEGRITY'] as $strClass) {

//             /** @var IsotopeIntegrityCheck $objCheck */
//             $objCheck = new $strClass();

//             if (!($objCheck instanceof IsotopeIntegrityCheck)) {
//                 throw new \LogicException('Class "' . $strClass . '" must implement IsotopeIntegrityCheck interface');
//             }

//             if (in_array($objCheck->getId(), $arrTasks) && $objCheck->hasError() && $objCheck->canRepair()) {

//                 $objCheck->repair();
//                 $blnReload = true;

//             } else {

//                 $blnError = $objCheck->hasError();
//                 $blnRepair = $objCheck->canRepair();

//                 $arrChecks[] = [
//                     'id'          => $objCheck->getId(),
//                     'name'        => $objCheck->getName(),
//                     'description' => $objCheck->getDescription(),
//                     'error'       => $blnError,
//                     'repair'      => $blnError && $blnRepair,
//                 ];

//                 if ($blnError && $blnRepair) {
//                     $this->Template->hasFixes = true;
//                 }
//             }
//         }

//         if ($blnReload) {
//             \Controller::reload();
//         }

//         $this->Template->checks = $arrChecks;
//         $this->Template->action = \Environment::get('request');
//         $this->Template->back = str_replace('&mod=integrity', '', \Environment::get('request'));
//     }
// }







// <?php

// /**
//  * Isotope eCommerce for Contao Open Source CMS
//  *
//  * Copyright (C) 2009-2016 terminal42 gmbh & Isotope eCommerce Workgroup
//  *
//  * @link       https://isotopeecommerce.org
//  * @license    https://opensource.org/licenses/lgpl-3.0.html
//  */

// namespace Isotope\BackendModule;

// /**
//  * Class ModuleIsotopeSetup
//  *
//  * Back end module Isotope "setup".
//  * @copyright  Isotope eCommerce Workgroup 2009-2012
//  * @author     Andreas Schempp <andreas.schempp@terminal42.ch>
//  * @author     Fred Bliss <fred.bliss@intelligentspark.com>
//  */
// class Setup extends BackendOverview
// {
//     /**
//      * {@inheritdoc}
//      */
//     protected function getModules()
//     {
//         $return = [];

//         $this->addIntroduction($return);

//         foreach ($GLOBALS['ISO_MOD'] as $strGroup => $arrModules) {
//             foreach ($arrModules as $strModule => $arrConfig) {

//                 if ($this->checkUserAccess($strModule)) {
//                     if (is_array($arrConfig['tables'])) {
//                         $GLOBALS['BE_MOD']['isotope']['iso_setup']['tables'] += $arrConfig['tables'];
//                     }

//                     $return[$strGroup]['modules'][$strModule] = array_merge($arrConfig, array
//                     (
//                         'label'         => specialchars($GLOBALS['TL_LANG']['IMD'][$strModule][0] ?: $strModule),
//                         'description'   => specialchars(strip_tags($GLOBALS['TL_LANG']['IMD'][$strModule][1])),
//                         'href'          => TL_SCRIPT . '?do=iso_setup&mod=' . $strModule,
//                         'class'         => $arrConfig['class'],
//                     ));

//                     $strLabel = str_replace(':hide', '', $strGroup);
//                     $return[$strGroup]['label'] = $GLOBALS['TL_LANG']['IMD'][$strLabel] ?: $strLabel;
//                 }
//             }
//         }

//         return $return;
//     }

//     /**
//      * {@inheritdoc}
//      */
//     protected function checkUserAccess($module)
//     {
//         return \BackendUser::getInstance()->isAdmin || \BackendUser::getInstance()->hasAccess($module, 'iso_modules');
//     }


//     /**
//      * Adds first steps and fundraising hints
//      *
//      * @param array $return
//      */
//     protected function addIntroduction(array &$return)
//     {
//         if (\BackendUser::getInstance()->isAdmin) {
//             $objTemplate = new \BackendTemplate('be_iso_introduction');

//             $return['introduction']['label'] = &$GLOBALS['TL_LANG']['MSC']['isotopeIntroductionLegend'];
//             $return['introduction']['html']  = $objTemplate->parse();
//         }
//     }
// }





// <?php

// /**
//  * Isotope eCommerce for Contao Open Source CMS
//  *
//  * Copyright (C) 2009-2016 terminal42 gmbh & Isotope eCommerce Workgroup
//  *
//  * @link       https://isotopeecommerce.org
//  * @license    https://opensource.org/licenses/lgpl-3.0.html
//  */

// namespace Isotope\BackendModule;


// abstract class BackendOverview extends \BackendModule
// {

//     /**
//      * Template
//      * @var string
//      */
//     protected $strTemplate = 'be_iso_overview';

//     /**
//      * Isotope modules
//      * @var array
//      */
//     protected $arrModules = array();


//     /**
//      * Get modules
//      * @return array
//      */
//     abstract protected function getModules();

//     /**
//      * Check if a user has access to the current module
//      * @return boolean
//      */
//     abstract protected function checkUserAccess($module);


//     /**
//      * Generate the module
//      * @return string
//      */
//     public function generate()
//     {
//         $this->arrModules = array();

//         // enable collapsing legends
//         $session = \Session::getInstance()->get('fieldset_states');
//         foreach ($this->getModules() as $k => $arrGroup) {
//             list($k, $hide) = explode(':', $k, 2);

//             if (isset($session['iso_be_overview_legend'][$k])) {
//                 $arrGroup['collapse'] = !$session['iso_be_overview_legend'][$k];
//             } elseif ('hide' === $hide) {
//                 $arrGroup['collapse'] = true;
//             }

//             $this->arrModules[$k] = $arrGroup;
//         }

//         // Open module
//         if (\Input::get('mod') != '') {
//             return $this->getModule(\Input::get('mod'));
//         } // Table set but module missing, fix the saveNcreate link
//         elseif (\Input::get('table') != '') {
//             foreach ($this->arrModules as $arrGroup) {
//                 if (isset($arrGroup['modules'])) {
//                     foreach ($arrGroup['modules'] as $strModule => $arrConfig) {
//                         if (is_array($arrConfig['tables'])
//                             && in_array(\Input::get('table'), $arrConfig['tables'], true)
//                         ) {
//                             \Controller::redirect(\Backend::addToUrl('mod=' . $strModule));
//                         }
//                     }
//                 }
//             }
//         }

//         return parent::generate();
//     }


//     /**
//      * Generate the module
//      */
//     protected function compile()
//     {
//         if (version_compare(VERSION, '4.0', '<')) {
//             $versionClass = 'iso_backend3';
//         } elseif (version_compare(VERSION, '4.2', '<')) {
//             $versionClass = 'iso_backend42';
//         } else {
//             $versionClass = 'iso_backend44';
//         }


//         $this->Template->versionClass = $versionClass;
//         $this->Template->modules = $this->arrModules;
//     }


//     /**
//      * Open a module and return it as HTML
//      * @param string
//      * @return mixed
//      */
//     protected function getModule($module)
//     {
//         $arrModule = array();
//         $dc = null;

//         foreach ($this->arrModules as $arrGroup) {
//             if (!empty($arrGroup['modules']) && array_key_exists($module, $arrGroup['modules'])) {
//                 $arrModule =& $arrGroup['modules'][$module];
//             }
//         }

//         // Check whether the current user has access to the current module
//         if (!$this->checkUserAccess($module)) {
//             \System::log('Module "' . $module . '" was not allowed for user "' . $this->User->username . '"', __METHOD__, TL_ERROR);
//             \Controller::redirect('contao/main.php?act=error');
//         }

//         // Redirect the user to the specified page
//         if ($arrModule['redirect'] != '') {
//             \Controller::redirect($arrModule['redirect']);
//         }

//         $strTable = \Input::get('table');

//         if ($strTable == '' && $arrModule['callback'] == '') {
//             \Controller::redirect(\Backend::addToUrl('table=' . $arrModule['tables'][0]));
//         }

//         // Add module style sheet
//         if (isset($arrModule['stylesheet'])) {
//             $GLOBALS['TL_CSS'][] = $arrModule['stylesheet'];
//         }

//         // Add module javascript
//         if (isset($arrModule['javascript'])) {
//             $GLOBALS['TL_JAVASCRIPT'][] = $arrModule['javascript'];
//         }

//         // Redirect if the current table does not belong to the current module
//         if ($strTable != '') {
//             if (!in_array($strTable, (array) $arrModule['tables'], true)) {
//                 \System::log('Table "' . $strTable . '" is not allowed in module "' . $module . '"', __METHOD__, TL_ERROR);
//                 \Controller::redirect('contao/main.php?act=error');
//             }

//             // Load the language and DCA file
//             \System::loadLanguageFile($strTable);
//             \Controller::loadDataContainer($strTable);

//             // Include all excluded fields which are allowed for the current user
//             if ($GLOBALS['TL_DCA'][$strTable]['fields']) {
//                 foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $k => $v) {
//                     if ($v['exclude'] && \BackendUser::getInstance()->hasAccess($strTable . '::' . $k, 'alexf')) {
//                         $GLOBALS['TL_DCA'][$strTable]['fields'][$k]['exclude'] = false;
//                     }
//                 }
//             }

//             // Fabricate a new data container object
//             if (!strlen($GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'])) {
//                 \System::log('Missing data container for table "' . $strTable . '"', __METHOD__, TL_ERROR);
//                 trigger_error('Could not create a data container object', E_USER_ERROR);
//             }

//             $dataContainer = 'DC_' . $GLOBALS['TL_DCA'][$strTable]['config']['dataContainer'];
//             $dc            = new $dataContainer($strTable);
//         }

//         // AJAX request
//         if ($_POST && \Environment::get('isAjaxRequest')) {
//             $this->objAjax->executePostActions($dc);
//         }

//         // Call module callback
//         elseif (class_exists($arrModule['callback'])) {

//             /** @var \BackendModule $objCallback */
//             $objCallback = new $arrModule['callback']($dc, $arrModule);

//             return $objCallback->generate();
//         }

//         // Custom action (if key is not defined in config.php the default action will be called)
//         elseif (\Input::get('key') && isset($arrModule[\Input::get('key')])) {
//             $objCallback = new $arrModule[\Input::get('key')][0]();

//             return $objCallback->{$arrModule[\Input::get('key')][1]}($dc, $strTable, $arrModule);
//         } // Default action
//         elseif (is_object($dc)) {
//             $act = (string) \Input::get('act');

//             if ('' === $act || 'paste' === $act || 'select' === $act) {
//                 $act = ($dc instanceof \listable) ? 'showAll' : 'edit';
//             }

//             switch ($act) {
//                 case 'delete':
//                 case 'show':
//                 case 'showAll':
//                 case 'undo':
//                     if (!$dc instanceof \listable) {
//                         \System::log('Data container ' . $strTable . ' is not listable', __METHOD__, TL_ERROR);
//                         trigger_error('The current data container is not listable', E_USER_ERROR);
//                     }
//                     break;

//                 case 'create':
//                 case 'cut':
//                 case 'cutAll':
//                 case 'copy':
//                 case 'copyAll':
//                 case 'move':
//                 case 'edit':
//                     if (!$dc instanceof \editable) {
//                         \System::log('Data container ' . $strTable . ' is not editable', __METHOD__, TL_ERROR);
//                         trigger_error('The current data container is not editable', E_USER_ERROR);
//                     }
//                     break;
//             }

//             return $dc->$act();
//         }

//         return null;
//     }
// }
