<?php

class CRM_Core_Payment_PaperlessTrans extends CRM_Core_Payment {

  protected $_mode = NULL;
  protected $_params = array();
  protected $_resultFunctionsMap = array();
  protected $_frequencyMap = array();
  protected $_reqParams = array();
  protected $_islive = NULL;
  protected $_isTestString = 'False';
  protected $_ptDateFormat = 'm/d/Y';
  protected $_transactionType = NULL;

  public $_ppDebugLevel = 5;

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   */
  static private $_singleton = NULL;

  /**
   * Constructor.
   *
   * @param string $mode
   *   the mode of operation: live or test.
   *
   * @param array $paymentProcessor
   */
  public function __construct($mode, &$paymentProcessor) {
    $this->_mode = $mode;
    //$this->_islive = ($mode == 'live' ? TRUE : FALSE);
    // Final version because Paperless wants a string.
    //$this->_isTestString = ($mode == 'test' ? 'True' : 'False');
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName = ts('PaperlessTrans');
    // Array of the result function names by Soap request function name.
    //$this->_resultFunctionsMap = self::_mapResultFunctions();

    // Get merchant data from config.
    $config = CRM_Core_Config::singleton();
  }

  /**
   * This function checks to see if we have the right config values.
   *
   * @return string
   *   the error message if any
   */
  public function checkConfig() {
    $error = array();
    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('APILogin is not set for this payment processor');
    }

    if (empty($this->_paymentProcessor['password'])) {
      $error[] = ts('Key is not set for this payment processor');
    }

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  /**
   * Set a field to the specified value.  Value must be a scalar (int,
   * float, string, or boolean).
   *
   * @param string $field
   * @param mixed $value
   *
   * @return bool
   *   false if value is not a scalar, true if successful
   */
  public function _setParam($field, $value) {
    if (!is_scalar($value)) {
      return FALSE;
    }
    else {
      $this->_params[$field] = $value;
    }
  }

  /**
   * Get the value of a field if set.
   *
   * @param string $field
   *   The field.
   *
   * @return mixed
   *   value of the field, or empty string if the field is
   *   not set
   */
  public function _getParam($field) {
    if (isset($this->_params[$field])) {
      return $this->_params[$field];
    }
    else {
      return '';
    }
  }

  public function _restTransaction($transaction_type = '', $params = array()) {
    $this->_ppDebug('_restTransaction $transaction_type', $transaction_type);
    $this->_ppDebug('_restTransaction $this->_reqParams', $this->_reqParams);

    // Don't want to assume anything here. Must be passed.
    if (empty($transaction_type)) {
      return self::error(2, 'No $transaction_type passed to _restTransaction!');
    }

    $this->_ppDebug('restTrxn $this->_reqParams', $this->_reqParams);
    $rest = new CRM_Paperlesstrans_REST();
    try {
      $postProfile = array('source' => $this->_reqParams['source']);
      $result = $rest->createProfile($postProfile);
    } catch (Exception $e) {
      return self::error(9001, $e->getMessage());
    }
    if(!empty($result['profile'])) {
      $profile  = $result['profile']['profileNumber'];
      $result['profile_number'] = $profile;

      // if not a future trxn, fire immediate trxn
      if (empty($params['future_receive_date'])) {
        $postData = array(
          'amount' => $this->_reqParams['amount'],
          'source' => array(
            'profileNumber' => $profile
          )
        );
        try {
          $result = $rest->captureTransaction($postData);
        } catch (Exception $e) {
          return self::error(9001, $e->getMessage());
        }
        if($result['isApproved'] == "true") {
          $result['trxn_id'] = $result['transaction']['authorizationNumber'] . '-' . $result['referenceId'];
          $result['profile_number'] = $profile;
        } else {
          // Transaction was declined, or failed for other reason.
          $error_message = 'There was an error with the transaction. Please check logs: ';
          $this->_ppDebug($error_message, $result);
          return self::error(9001, $result['message']);
        }
      }
    }

    //$result = $this->_callRestTransaction($params);
    if (is_a($result, 'CRM_Core_Error')) {
      $error_message = 'There was an error with the transaction. Please check logs: ';
      $this->_ppDebug($error_message, $result);
      return $result;
    }
    if (!empty($result['trxn_id'])) {
      $this->_setParam('trxn_id', $result['trxn_id']);
    }
    if (!empty($result['profile_number'])) {
      $this->_setParam('pt_profile_number', $result['profile_number']);
    }
    $this->_ppDebug('_restTransaction $result', $result, FALSE, 2);
    return $result;
  }

  /**
   * Build the default array to send to PaperlessTrans.
   *
   * @return array
   *   The scaffolding for the REST transaction parameters.
   */
  public function _buildRequestDefaults() {
    $defaults = array(
      'amount' => array(
        'value'    => $this->_getParam('amount'),
        'currency' => $this->_getParam('currencyID'),
      ),
    );
    return $defaults;
  }

  ///**
  // * Prepare the fields for recurring subscription requests.
  // *
  // * Paperless's frequency map:
  // * - '52' => 'Weekly'
  // * - '26' => 'Semi-Weekly'
  // * - '24' => 'Bi-Monthly'
  // * - '12' => 'Monthly'
  // * - '4'  => 'Quarterl'
  // * - '2'  => 'Bi-Annualy'
  // * - '1'  => 'Annually'
  // *
  // * frequency_interval option is disallowed
  // *
  // * @param string $profile_number
  // *   May not be used.  The ProfileNumber from PaperlessTrans.
  // *
  // * @return array
  // *   The array of additional SOAP request params.
  // */
  //public function _processRecurFields($profile_number = '') {
  //  $full_name = $this->_getParam('billing_first_name') . ' ' . $this->_getParam('billing_last_name');

  //  // Example: I chose once every 2 months for 10 months:
  //  // [frequency_interval] => 2
  //  // [frequency_unit] => month
  //  // [installments] => 10
  //  $frequency_unit = $this->_getParam('frequency_unit');

  //  // Map CiviCRM's frequency name to Paperless's.
  //  if (empty($this->_frequencyMap[$frequency_unit])) {
  //    $error_message = 'Could not determine recurring frequency. Please try another setting.';
  //    $this->_ppDebug($error_message, $frequency_unit);

  //    return FALSE;
  //  }
  //  $frequency = $this->_frequencyMap[$frequency_unit];

  //  $startDate = (!empty($this->_getParam('future_receive_date'))) ?
  //    date($this->_ptDateFormat, strtotime($this->_getParam('future_receive_date'))) :
  //    date($this->_ptDateFormat);

  //  // Set up the soap call parameters for recurring.
  //  $params = array(
  //    'req' => array(
  //      // This is for updating existing subscriptions.
  //      //'ProfileNumber' =>  $profile_number,
  //      'ListingName' => $full_name,
  //      'Frequency' => $frequency,
  //      'StartDateTime' => $startDate,
  //      'Memo' => 'CiviCRM recurring charge.',
  //    ),
  //  );

  //  // If they set a limit to the number of installments (end date).
  //  if (!empty($this->_getParam('installments'))) {
  //    $installments = $this->_getParam('installments');

  //    //TODO rethink this; if we expose future date w/o recur checkbox, should just set these values
  //    if ($installments == 1) {
  //      //if installments = 1, offset end date by a single week to avoid errors
  //      $endTime = strtotime("+1 week", strtotime($startDate));

  //      //also set Frequency to week (52)
  //      $params['req']['Frequency'] = 52;
  //    }
  //    else {
  //      // This is subtracted by 1 because CiviCRM reports status to the user as:
  //      // "X installments (including this initial contribution)".
  //      $installments--;
  //      $endTime = strtotime("+{$installments} {$frequency_unit}", strtotime($startDate));
  //    }

  //    $endDate = date($this->_ptDateFormat, $endTime);

  //    // Now set the soap call parameter.
  //    $params['req']['EndingDateTime'] = $endDate;
  //  }

  //  return $params;
  //}

  /**
   * Prepare the fields for recurring subscription update requests.
   *
   * @param array $additional_params
   *   Array of values from the form.
   *
   * @return array
   *   The array of additional SOAP request params.
   */
  public function _processUpdateFields($additional_params = array()) {
    $full_name = $this->_getParam('first_name') . ' ' . $this->_getParam('last_name');
    $frequency_unit = $additional_params['frequency_unit'];
    $original_time = strtotime($additional_params['start_date']);

    // Map CiviCRM's frequency name to Paperless's.
    if (empty($this->_frequencyMap[$frequency_unit])) {
      $error_message = 'Could not determine recurring frequency.  Please try another setting.';
      CRM_Core_Error::debug_log_message($error_message);
      echo $error_message . '<p>';
      return FALSE;
    }
    $frequency = $this->_frequencyMap[$frequency_unit];

    // Set up the soap call parameters for recurring.
    $params = array(
      'req' => array(
        // This is for updating existing subscriptions.
        'ProfileNumber' =>  $additional_params['profile_number'],
        'ListingName' =>  $full_name,
        'Frequency'   =>  $frequency,
        'StartDateTime' =>  date($this->_ptDateFormat, $original_time),
        'Memo'      =>  'CiviCRM recurring charge.',
      ),
    );

    // If they set a limit to the number of installments (end date).
    if (!empty($additional_params['installments'])) {
      $installments = $additional_params['installments'];
      // This is subtracted by 1 because CiviCRM reports status to the user as:
      // "X installments (including this initial contribution)".
      $installments--;
      $endTime = strtotime("+{$installments} {$frequency_unit}", $original_time);
      $endDate = date($this->_ptDateFormat, $endTime);
      // Now set the soap call parameter.
      $params['req']['EndingDateTime'] = $endDate;
    }

    return $params;
  }

  /**
   * Map the transaction_type to the property name on the result.
   *
   * @return array
   *   Array of transaction_type => resultPropertyName.
   */
  public function _mapResultFunctions() {
    $map = array(
      'CreateACHProfile' => 'CreateACHProfileResult',
      'CreateCardProfile' => 'CreateCardProfileResult',
      'ProcessACH' => 'ProcessACHResult',
      'AuthorizeCard' => 'AuthorizeCardResult',
      'processCard' => 'ProcessCardResult',
      'RefundCardTransaction' => 'RefundCardTransactionResult',
      'SettleCardAuthorization' => 'SettleCardAuthorizationResult',
      'SetupCardSchedule' => 'SetupCardScheduleResult',
      'SetupACHSchedule' => 'SetupACHScheduleResult',
      'UpdateCardSchedule' => 'UpdateCardScheduleResult',
      'UpdateACHSchedule' => 'UpdateACHScheduleResult',
    );

    return $map;
  }

  /**
   * Paperless's recurring subscription frequency map:
   * - '52' => 'Weekly'
   * - '26' => 'Semi-Weekly'
   * - '24' => 'Bi-Monthly'
   * - '12' => 'Monthly'
   * - '4'  => 'Quarterl'
   * - '2'  => 'Bi-Annualy'
   * - '1'  => 'Annually'
   *
   * @return array
   *   Array of CiviCRM => Paperless frequency values.
   */
  public function _mapFrequency() {
    $map = array(
      'week' => '52',
      //'Semi-Weekly' => '26',
      //'Bi-Monthly' => '24',
      'month' => '12',
      //'Quarterly' => '4',
      //'Bi-Annually' => '2',
      'year' => '1',
    );

    return $map;
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
   * 4. set debug level constant and set level when function called
   */
  function _ppDebug($msg, $var, $force = FALSE, $level = 1) {
    if (Civi::settings()->get('debug_enabled') || $force) {
      if ($this->_ppDebugLevel >= $level) {
        //CRM_Core_Error::debug_var($msg, $var, TRUE, TRUE, 'paperless');
        CRM_Core_Error::debug_var($msg, $var, TRUE, TRUE);
      }
    }
  }

  /**
   * @param null $errorCode
   * @param null $errorMessage
   *
   * @return object
   */
  public function &error($errorCode = NULL, $errorMessage = NULL) {
    $e = CRM_Core_Error::singleton();
    if ($errorCode) {
      $e->push($errorCode, 0, array(), $errorMessage);
    }
    else {
      $e->push(9001, 0, array(), 'Unknown System Error.');
    }
    return $e;
  }

/**
   * The first payment date is configurable when setting up back office recurring payments.
   *
   * @return bool
   */

   public function supportsFutureRecurStartDate() {
    return TRUE;
  } 

  /**
   * Submit a payment.
   *
   * @param array $params
   *   Assoc array of input parameters for this transaction.
   *
   * @return array
   *   The result in a nice formatted array (or an error object).
   *
   * one-time future transaction is collected as non-recurring but processed as
   * recurring with a future date
   */
  public function doDirectPayment(&$params) {
    CRM_Core_Error::backtrace('backtrace', true);
    $this->_ppDebug('doDirectPayment $params', $params);
    $this->_ppDebug('doDirectPayment $this', $this);
    $this->_ppDebug('doDirectPayment $_REQUEST', $_REQUEST, FALSE, 3);

    //get receive date from session as it's not getting passed from the form object params
    $session = CRM_Core_Session::singleton();
    $params['future_receive_date'] = $session->get("future_receive_date_{$params['qfKey']}");

    //set future_receive_date in class storage
    if (!empty($params['future_receive_date'])) {
      $this->_setParam('future_receive_date', $params['future_receive_date']);
    }

    if ($this->_getParam('currencyID') != 'USD') {
      $error_message = 'Only USD is supported in PaperlessTrans.';
      $this->_ppDebug($error_message, $this->_getParam('currencyID'));

      return self::error(2, $error_message);
    }

    // Transaction type.
    $transaction_type = $this->_transactionType;

    //determine if one-time future
    $oneTimeFuture = FALSE;
    if (!empty($params['future_receive_date']) && empty($params['is_recur'])) {
      $oneTimeFuture = TRUE;

      $this->_setParam('frequency_unit', 'week');
      $this->_setParam('installments', 1);

      $params['frequency_unit'] = 'week';
      $params['installments'] = 1;

      //one time future will not have a contribution recur ID, but we need
      //to create one for downstream processing
      $params['contributionRecurID'] = $this->_createRecurringContrib($params);
      $this->_setParam('contributionRecurID', $params['contributionRecurID']);
    }

    //// Recurring payments or one-time future
    //if ((!empty($params['is_recur']) && !empty($params['contributionRecurID'])) ||
    //  $oneTimeFuture
    //) {
    //  //$transaction_type = $this->_transactionTypeRecur;
    //  //$this->doRecurPayment();
    //}

    // Run the SOAP transaction.
    //$result = self::_soapTransaction($transaction_type, $this->_reqParams);
    $result = $this->_restTransaction($transaction_type, $params);
    $this->_ppDebug('doDirectPayment $result', $result, FALSE, 2);

    // Handle errors.
    if (is_a($result, 'CRM_Core_Error')) {
      $error_message = 'There was an error with the transaction. Please check logs: ';
      $this->_ppDebug($error_message, $result);

      return $result;
    }

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    if (!empty($result['trxn_id'])) {
      $params['trxn_id'] = $result['trxn_id'];
      // Set contribution status to success.
      $params['contribution_status_id'] = array_search('Completed', $contributionStatus);
      $params['payment_status_id'] = array_search('Completed', $contributionStatus);
    } else if (!empty($params['future_receive_date'])) {
      // future date selected
      // Set contribution status to pending.
      $params['contribution_status_id'] = array_search('Pending', $contributionStatus);
      $params['payment_status_id'] = array_search('Pending', $contributionStatus);

      //Note: since we override receive_date in buildForm hook, any initial
      //contribution or recur will have receive-date or start-date set
      //correctly. Otherwise we 'll have to set the dates at this point.
      }
    } else {
      return self::error(2, 'Neither Trxn nor Future date is present. Something wrong.');
    }

    // Store paperless profile
    if (!empty($params['contributionRecurID']) && !empty($this->_getParam('pt_profile_number'))) {
      $this->_ppDebug('store profilenumbers $params', $params, FALSE, 2);

      $is_ach = $this->_transactionType == 'ProcessACH' ? 1 : 0;
      $query_params = array(
        1 => array($this->_getParam('pt_profile_number'), 'String'),
        2 => array($_SERVER['REMOTE_ADDR'], 'String'),
        3 => array($is_ach, 'Integer'),
        4 => array($params['contactID'], 'Integer'),
        5 => array($this->_getParam('email'), 'String'),
        6 => array($params['contributionRecurID'], 'Integer'),
      );
      $this->_ppDebug('store profilenumbers $query_params', $query_params, FALSE, 2);

      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_paperlesstrans_profilenumbers
        (profile_number, ip, is_ach, cid, email, recur_id)
        VALUES (%1, %2, %3, %4, %5, %6)", $query_params);
    } else if (!empty($params['future_receive_date'])) {
      // if its future, we should 've profile_number. Throw error otherwise
      return self::error(2, 'Expected profile number or recur ID missing. Something wrong.');
    }

    $this->_ppDebug('doDirectPayment final $params', $params, FALSE, 2);
    return $params;
  }

  ///**
  // * Create a recurring billing subscription.
  // */
  //public function doRecurPayment() {
  //  // Create a Credit Card Customer Profile.
  //  //$profile_number = $this->_createCCProfile();

  //  // @TODO Create Profile, then get ProfileNumber.
  //  // A profile is created with scheduled payments.  Do we need to look up and
  //  // match for new schedules created by the same person/profile?
  //  $recurParams = self::_processRecurFields();
  //  $this->_ppDebug('$recurParams', $recurParams);

  //  // Merge the defaults with current processParams.
  //  $currentParams = $this->_reqParams;
  //  $this->_ppDebug('$currentParams', $currentParams);
  //  $this->_reqParams = array_merge_recursive($currentParams, $recurParams);
  //}


  /**
   * Prepare recurring billing subscription update vars.
   *
   * @param string $message
   * @param array $params
   *
   * @return bool|object
   */
  public function updateSubscriptionBillingInfoPrep(&$message = '', $params = array()) {
    // Since the recurring contrib ID is not passed in any other variable,
    // we must pull it out of entry URL :(.
    if (empty($params['contribution_recur_id'])) {
      parse_str(htmlspecialchars_decode($params['entryURL']), $urlParams);
      // If we couldn't get the recurring contribution ID.
      if (empty($urlParams['crid'])) {
        $error_message = 'Could not determine recurring contribution ID.  Please report this issue.';
        CRM_Core_Error::debug_log_message($error_message);
        echo $error_message . '<p>';
        return self::error(2, $error_message);
      }
      $crid = $urlParams['crid'];
    }
    else {
      $crid = $params['contribution_recur_id'];
    }
    // Query their recurring contribution values.
    $query_params = array(1 => array($crid, 'Integer'));
    $query = "SELECT cr.frequency_unit, cr.installments, cr.start_date, pt.profile_number
      FROM civicrm_contribution_recur cr
      LEFT JOIN civicrm_paperlesstrans_profilenumbers pt ON cr.id = pt.recur_id
      WHERE cr.id = %1";

    $query_result = CRM_Core_DAO::executeQuery($query, $query_params);

    while ($query_result->fetch()) {
      $additional_req_params = array(
        'profile_number' =>  $query_result->profile_number,
        'frequency_unit' => $query_result->frequency_unit,
        'installments' => $query_result->installments,
        'start_date' => $query_result->start_date,
      );
    }

    if (empty($additional_req_params['profile_number'])) {
      $error_message = 'Could not find subscription profileNumber in database.';
      CRM_Core_Error::debug_log_message($error_message);
      echo $error_message . '<p>';
      return self::error(2, $error_message);
    }

    return $additional_req_params;
  }


  function _createRecurringContrib($params) {
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    $recurParams = array(
      'contact_id' => $params['contactID'],
      'amount' => $params['amount'],
      'currency' => $params['currencyID'],
      'frequency_unit' => $params['frequency_unit'],
      'frequency_interval' => 1, //hardcoded as paperless does not support other intervals
      'installments' => CRM_Utils_Array::value('installments', $params, NULL),
      'start_date' => CRM_Utils_Array::value('future_receive_date', $params, date('YmdHis')),
      //'trxn_id' => $params['trxn_id'],
      'invoice_id' => $params['invoiceID'],
      'contribution_status_id' => array_search('In Progress', $contributionStatus),
      'is_test' => FALSE,
      'cycle_day' => 1,
      'auto_renew' => FALSE,
      'payment_processor_id' => $params['payment_processor_id'],
      'financial_type_id' => $params['financialTypeID'],
      'payment_instrument_id' => $params['payment_instrument_id'],
      //'is_email_receipt' => '',
      'contribution_type_id' => $params['financialTypeID'],
    );
    $this->_ppDebug('_createRecurringContrib $recurParams', $recurParams, FALSE, 2);

    try {
      $recur = civicrm_api3('ContributionRecur', 'create', $recurParams);
      $this->_ppDebug('_createRecurringContrib $recur', $recur, FALSE, 2);

      return $recur['id'];
    }
    catch (CiviCRM_API3_Exception $e) {
      $this->_ppDebug('_createRecurringContrib $e', $e);
    }

    return NULL;
  }
}
