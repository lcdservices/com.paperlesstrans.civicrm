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
  $limit   = CRM_Utils_Array::value('limit', $params, 25);
  $success = 0;
  $failed  = 0;
  $total   = 0;
  $messages = array();

  // Use civicrm_paperlesstrans_profilenumbers table to fetch recur records
  // along with their profile numbers.
  $sql = "
    SELECT pl.*, cr.payment_processor_id
    FROM   civicrm_paperlesstrans_profilenumbers pl
    INNER JOIN civicrm_contribution_recur cr ON pl.recur_id = cr.id
    INNER JOIN civicrm_payment_processor pp ON cr.payment_processor_id = pp.id
    WHERE pp.class_name IN ('Payment_PaperlessTransACH', 'Payment_PaperlessTransCC')
    ";
    //AND cr.id IN (20)
  $dao = CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch() && ($total <= $limit)) {
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($dao->payment_processor_id);
    //_paperless_process_ppDebug('processrecur $paymentProcessor', $paymentProcessor);

    $payment = new CRM_Paperlesstrans_CiviPayment($dao->recur_id);
    if ($payment->isPaymentDue()) {
      $rest = new CRM_Paperlesstrans_REST($paymentProcessor);
      $postData = array(
        'amount' => array(
          'value'    => $payment->recur->amount,
          'currency' => $payment->recur->currency,
        ),
        'source' => array(
          'profileNumber' => $dao->profile_number
        )
      );
      _paperless_process_ppDebug('paperless process post date', $postData);
      try {
        $plResult = $rest->captureTransaction($postData);
        _paperless_process_ppDebug('plResult', $plResult);
      } catch (Exception $e) {
        $failed++;
        $messages[] = $e->getMessage();
        _paperless_process_ppDebug('plResult exception e', $e);
      }

      if(!empty($plResult['isApproved'])) {
        $trxnID = $plResult['transaction']['authorizationNumber'] . '-' . $plResult['referenceId'];
        $date   = new DateTime();
        try {
          // check if there is a pending payment that needs updating
          $contributionID = ($payment->paymentCount <= 0) ? $payment->getPendingPaymentId() : NULL;
          _paperless_process_ppDebug('pending $contributionID', $contributionID);
          if ($contributionID) {
            $result = civicrm_api3('contribution', 'completetransaction', array(
              'id'                   => $contributionID,
              'trxn_id'              => $trxnID,
              'payment_processor_id' => $payment->recur->payment_processor_id,
              'is_transactional'     => FALSE,
              'total_amount'         => $payment->recur->amount,
              'receive_date'         => $date->format('Y-m-d H:i:s'),
              //'fee_amount'           => CRM_Utils_Array::value('fee_amount', $result),
              //'card_type_id'         => CRM_Utils_Array::value('card_type_id', $result),
              //'pan_truncation'       => CRM_Utils_Array::value('pan_truncation', $result),
            ));
          } else {
            $result = civicrm_api3('Contribution', 'repeattransaction', [
              'contribution_recur_id' => $dao->recur_id,
              //'original_contribution_id' => $previous_completed_contribution_id,
              'contribution_status_id' => "Completed",
              'receive_date' => $date->format('Y-m-d H:i:s'),
              'trxn_id' => $trxnID,
              'total_amount' => $payment->recur->amount,
              //'fee_amount' => $fee, //TODO $trxn doesn't appear to include the fee_amount
              //'invoice_id' => $new_invoice_id - contribution.repeattransaction doesn't support it currently
              'is_email_receipt' => 1,
            ]);
          }
          if (!empty($result['id'])) {
            $success++;
            $oCount = $payment->addOrdinalNumberSuffix($payment->paymentCount + 1);
            $msg    = "{$oCount} Payment (Conitrbution ID: {$result['id']}, Amt: {$result['values'][$result['id']]['total_amount']}) " . ($contributionID ? "updated" : "created") . ", for recurr ID {$payment->recur->id} (started on: {$payment->recur->start_date})";
            $messages[] = $msg;
          }
          _paperless_process_ppDebug('paperless process civi trxn result', $result);
          _paperless_process_ppDebug('paperless process recur object', $payment->recur);
        }
        catch (CiviCRM_API3_Exception $e) {
          $failed++;
          $oCount = $payment->addOrdinalNumberSuffix($payment->paymentCount + 1);
          $msg = "{$oCount} Payment for amount {$payment->recur->amount} failed, for recurr ID {$payment->recur->id} (started on: {$payment->recur->start_date}). Message: " . $e->getMessage() . " Note: Paperless trxn seems to gone through ok. " . serialize($plResult) ;
          $messages[] = $msg;
          _paperless_process_ppDebug('paperless process civi trxn exception e', $e);
          _paperless_process_ppDebug('msg', $msg);
        }
      } else {
        // Transaction was declined, or failed for other reason.
        $error_message = 'There was an error with the transaction. Please check logs: ';
        _paperless_process_ppDebug($error_message, $plResult);
      }
    }
    unset($payment);
    $total = $success + $failed;
  }
  $dao->free();

  $result = array(
    'total'    => $total, 
    'success'  => $success, 
    'failed'   => $failed, 
    'messages' => $messages
  );
  _paperless_process_ppDebug('Paperless.Process api call result', $result);
  return civicrm_api3_create_success($result, $params, 'Paperless', 'Process');
}

function _paperless_process_ppDebug($msg, $var, $force = FALSE) {
  if (Civi::settings()->get('debug_enabled') || $force) {
    //CRM_Core_Error::debug_var($msg, $var, TRUE, TRUE, 'paperless');
    CRM_Core_Error::debug_var($msg, $var);
  }
}
