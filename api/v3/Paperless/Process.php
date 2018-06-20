<?php
use CRM_Paperlesstrans_ExtensionUtil as E;

/**
 * Paperless.Process API cycles through recurring contributions and uses REST
 * for carrying out paperless transactions for any payments due.
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_paperless_Process($params) {
  _ppDebug('processrecur $params', $params);

  $limit = CRM_Utils_Array::value('limit', $params, 10);
  $count = 0;

  // Use civicrm_paperlesstrans_profilenumbers table to fetch recur records
  // along with their profile numbers.
  $sql = "
    SELECT pp.*
    FROM   civicrm_paperlesstrans_profilenumbers pp
    INNER JOIN civicrm_contribution_recur cr ON pp.recur_id = cr.id
    WHERE cr.end_date IS NULL
    AND cr.payment_processor_id IS NOT NULL
    AND cr.id IN (8)
    ";// TODO: also check cr.contribution-status-id
  $dao = CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch() && ($count <= $limit)) {
    _ppDebug('processrecur $count', $count);
    _ppDebug('processrecur $dao', $dao);

    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($dao->payment_processor_id);
    _ppDebug('processrecur $paymentProcessor', $paymentProcessor);

    if (in_array($paymentProcessor['class_name'], 
      array('Payment_PaperlessTransACH', 'Payment_PaperlessTransCC'))
    ) {
      $payment = new CRM_Paperlesstrans_CiviPayment($dao->recur_id);
      CRM_Core_Error::debug_var('$$payment->isPaymentDue()', $payment->isPaymentDue());
      if ($payment->isPaymentDue()) {
        $rest = new CRM_Paperlesstrans_REST();
        $postData = array(
          'amount' => array(
            'value'    => $payment->recur->amount,
            'currency' => $payment->recur->currency,
          ),
          'source' => array(
            'profileNumber' => $dao->profile_number
          )
        );
        try {
          CRM_Core_Error::debug_var('$postData', $postData);
          $result = $rest->captureTransaction($postData);
          CRM_Core_Error::debug_var('$result', $result);
        } catch (Exception $e) {
          CRM_Core_Error::debug_var('$dao', $dao);
          CRM_Core_Error::debug_var('$postData', $postData);
          CRM_Core_Error::debug_var('$e', $e);
        }

        if($result['isApproved'] == "true") {
          $trxnID = $result['transaction']['authorizationNumber'] . '-' . $result['referenceId'];
          $date   = new DateTime($trxn->DateTimeStamp);
          try {
            $resultRT = civicrm_api3('Contribution', 'repeattransaction', [
              'contribution_recur_id' => $dao->recur_id,
              //'original_contribution_id' => $previous_completed_contribution_id,
              'contribution_status_id' => "Completed",
              'receive_date' => $date->format('Y-m-d H:i:s'),
              'trxn_id' => $trxnID,
              'total_amount' => payment->recur->amount,
              //'fee_amount' => $fee, //TODO $trxn doesn't appear to include the fee_amount
              //'invoice_id' => $new_invoice_id - contribution.repeattransaction doesn't support it currently
              'is_email_receipt' => 1,
            ]);
            _ppDebug('processrecur $resultRT', $resultRT);
          }
          catch (CiviCRM_API3_Exception $e) {
            _ppDebug('processrecur repeattransaction $e', $e);
          }
        } else {
          // Transaction was declined, or failed for other reason.
          $error_message = 'There was an error with the transaction. Please check logs: ';
          $this->_ppDebug($error_message, $result);
        }
      }
      $payment->free();
    }
  }
}

function _ppDebug($msg, $var, $force = FALSE) {
  //if (Civi::settings()->get('debug_enabled') || $force) {
    //CRM_Core_Error::debug_var($msg, $var, TRUE, TRUE, 'paperless');
    CRM_Core_Error::debug_var($msg, $var);
  //}
}
