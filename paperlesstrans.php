<?php

require_once 'paperlesstrans.civix.php';

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function paperlesstrans_civicrm_config(&$config) {
  _paperlesstrans_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @param array $files
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function paperlesstrans_civicrm_xmlMenu(&$files) {
  _paperlesstrans_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function paperlesstrans_civicrm_install() {
  _paperlesstrans_civix_civicrm_install();
}

/**
* Implements hook_civicrm_postInstall().
*
* @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
*/
function paperlesstrans_civicrm_postInstall() {
  _paperlesstrans_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function paperlesstrans_civicrm_uninstall() {
  _paperlesstrans_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function paperlesstrans_civicrm_enable() {
  _paperlesstrans_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function paperlesstrans_civicrm_disable() {
  _paperlesstrans_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed
 *   Based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function paperlesstrans_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _paperlesstrans_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function paperlesstrans_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'com.paperlesstrans.civicrm',
    'name' => 'Paperless Transactions Credit Card',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'Paperless Transactions Credit Card',
      'title' => 'Paperless Transactions Credit Card',
      'description' => 'Paperless Transactions Payment Processor',
      'class_name' => 'Payment_PaperlessTransCC',
      'billing_mode' => 'form',
      'user_name_label' => 'Terminal ID',
      'password_label' => 'Terminal Key',
      'url_site_default' => 'https://svc.paperlesstrans.com:9999/?wsdl',
      'url_recur_default' => 'https://svc.paperlesstrans.com:9999/?wsdl',
      'url_site_test_default' => 'http://svc.paperlesstrans.com:8888/?wsdl',
      'url_recur_test_default' => 'http://svc.paperlesstrans.com:8888/?wsdl',
      'is_recur' => 1,
      'payment_type' => 1,
    ),
  );

  $entities[] = array(
    'module' => 'com.paperlesstrans.civicrm',
    'name' => 'Paperless Transactions ACH/EFT',
    'entity' => 'PaymentProcessorType',
    'params' => array(
      'version' => 3,
      'name' => 'Paperless Transactions ACH/EFT',
      'title' => 'Paperless Transactions ACH/EFT',
      'description' => 'Paperless Transactions Payment Processor',
      'class_name' => 'Payment_PaperlessTransACH',
      'billing_mode' => 'form',
      'user_name_label' => 'Terminal ID',
      'password_label' => 'Terminal Key',
      'url_site_default' => 'https://svc.paperlesstrans.com:9999/?wsdl',
      'url_recur_default' => 'https://svc.paperlesstrans.com:9999/?wsdl',
      'url_site_test_default' => 'http://svc.paperlesstrans.com:8888/?wsdl',
      'url_recur_test_default' => 'http://svc.paperlesstrans.com:8888/?wsdl',
      'is_recur' => 1,
      'payment_type' => 2,
      'payment_instrument_id' => '2',
    ),
  );

  _paperlesstrans_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * @param array $caseTypes
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function paperlesstrans_civicrm_caseTypes(&$caseTypes) {
  _paperlesstrans_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function paperlesstrans_civicrm_angularModules(&$angularModules) {
_paperlesstrans_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function paperlesstrans_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _paperlesstrans_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function paperlesstrans_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contribute_Form_Contribution_Main' &&
    _paperlesstrans_paperlessEnabled($form)
  ) {
    _paperlesstrans_buildForm_Contrib_front($form);
  }

  if ($formName == 'CRM_Contribute_Form_Contribution_Confirm' &&
    _paperlesstrans_paperlessSelected($form)
  ) {
    $templatePath = realpath(dirname(__FILE__)."/templates");
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => "{$templatePath}/CRM/Contribute/Form/Contribution/Confirm.paperless.tpl"
    ));
    $session    = CRM_Core_Session::singleton();
    $futureDate = $session->get("future_receive_date_{$form->_params['qfKey']}");

    // override civi's receive date
    // 1. this makes contribution receive-date and recur start-date created by
    //    civi, set to future date, and hook doesn't require working it out.
    // 2. Also thankyou template displays correct date.
    // 3. Receipt will use same dates.
    $futureDate = $futureDate ? $futureDate : date('YmdHis');
    $form->_params['receive_date'] = $futureDate;
    $form->assign('receive_date', $futureDate);
  }
}

function paperlesstrans_civicrm_postProcess($formName, &$form) {
  if (($formName == 'CRM_Contribute_Form_Contribution_Main') &&
    _paperlesstrans_paperlessEnabled($form)
  ) {
    //_paperless_debug('paperlesstrans_civicrm_postProcess $form', $form);
    $session = CRM_Core_Session::singleton();
    CRM_Core_Error::debug_var('Main $form->_submitValues', $form->_submitValues);
    if (!empty($form->_submitValues['future_receive_date'])) {
      $session->set("future_receive_date_{$form->_submitValues['qfKey']}", $form->_submitValues['future_receive_date']);
    }
    else {
      $session->set("future_receive_date_{$form->_submitValues['qfKey']}", NULL);
    }
  }
}

function paperlesstrans_civicrm_navigationMenu(&$params) {
  $pages = array(
    'settings_page' => array(
      'label' => 'Paperless Payments Settings',
      'name' => 'Paperless Payments Settings',
      'url' => 'civicrm/admin/contribute/paperlesssettings',
      'parent' => array('Administer', 'CiviContribute'),
      'permission' => 'access CiviContribute,administer CiviCRM',
      'operator' => 'AND',
      'separator' => NULL,
      'active' => 1,
    ),
  );
  foreach ($pages as $item) {
    // Check that our item doesn't already exist.
    $menu_item_search = array('url' => $item['url']);
    $menu_items = array();
    CRM_Core_BAO_Navigation::retrieve($menu_item_search, $menu_items);
    if (empty($menu_items)) {
      $path = implode('/', $item['parent']);
      unset($item['parent']);
      _paperlesstrans_civix_insert_navigation_menu($params, $path, $item);
    }
  }
}

function _paperlesstrans_buildForm_Contrib_front(&$form) {
  $settings = CRM_Core_BAO_Setting::getItem('Paperless Payments Extension', 'paperless_settings');
  //_paperless_debug('_paperlesstrans_buildForm_Contrib_front $settings', $settings);


  if (!empty($settings['enable_public_future_start'])) {
    $allow_days = empty($settings['days']) ? array('-1') : $settings['days'];
    $start_dates = _paperlesstrans_get_future_monthly_start_dates(time(), $allow_days);
    CRM_Core_Error::debug_var('$start_dates', $start_dates);
    $form->add('select', 'future_receive_date', ts('Contribution Transaction Date'), $start_dates);

    CRM_Core_Region::instance('price-set-1')->add(array(
      'template' => 'CRM/Paperlesstrans/BillingBlockFutureStart.tpl',
    ));
  }
}

/**
 * @param $form
 *
 * @return bool
 *
 * helper to determine if the contrib page has a paperless processor enabled
 */
function _paperlesstrans_paperlessEnabled($form) {
  foreach ($form->_paymentProcessors as $processor) {
    if (in_array($processor['class_name'], array(
        'Payment_PaperlessTransACH',
        'Payment_PaperlessTransCC',
      )) && $processor['is_active']
    ) {
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * @param $form
 *
 * @return bool
 *
 * helper to determine if the contrib page has a paperless processor selected
 */
function _paperlesstrans_paperlessSelected($form) {
  $pp = civicrm_api(
    'PaymentProcessor', 
    'getsingle', 
    array(
      'id'      => $form->_params['payment_processor_id'], 
      'version' => 3
    )
  );
  return in_array($pp['class_name'], array('Payment_PaperlessTransACH', 'Payment_PaperlessTransCC')); 
}

/**
 * Function _paperlesstrans_get_future_start_dates
 *
 * @string $start_date a timestamp, only return dates after this.
 * @array $allow_days an array of allowable days of the month.
 */
function _paperlesstrans_get_future_monthly_start_dates($start_date, $allow_days) {
  // Future date options.
  $start_dates = array();
  // special handling for today - it means immediately or now.
  $today = date('YmdHis');
  // If not set, only allow for the first 28 days of the month.
  if (max($allow_days) <= 0) {
    $allow_days = range(1,28);
  }
  for ($j = 0; $j < count($allow_days); $j++) {
    // So I don't get into an infinite loop somehow ..
    $i = 0;
    $dp = getdate($start_date);
    while (($i++ < 60) && !in_array($dp['mday'], $allow_days)) {
      $start_date += (24 * 60 * 60);
      $dp = getdate($start_date);
    }
    $key = date('YmdHis', $start_date);
    if ($key == $today) { // special handling
      $display = ts('Now');
      $key = ''; // date('YmdHis');
    }
    else {
      $display = strftime('%B %e, %Y', $start_date);
    }
    $start_dates[$key] = $display;
    $start_date += (24 * 60 * 60);
  }
  return $start_dates;
}

/**
 * @param $msg
 * @param $var
 * @param $force boolean
 *
 * wrapper for CiviCRM debugging to:
 *
 * 1. only log to file if system debug is enabled
 * 2. log to separate log file with paperless prefix
 * 3. allow forced logging (log even when debugging is disabled)
 */
function _paperless_debug($msg, $var, $force = FALSE) {
  if (Civi::settings()->get('debug_enabled') || $force) {
    CRM_Core_Error::debug_var($msg, $var, TRUE, TRUE, 'paperless');
  }
}
