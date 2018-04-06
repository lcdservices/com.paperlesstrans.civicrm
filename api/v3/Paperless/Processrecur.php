<?php

//NOTES:
//1. There is a provision to store processor_id in recur table, doing so will make 
//   paperless_profile table obsolete.
//2. First payment of recur in civi has trxn-id of schedule, and not of first trxn. 
//   However first trxn has a custom field (field_1) with invoice ID which should 
//   with invoice ID of initiated first payment in civi.
//
//FIXME:
//- performance - use a limit for num of records to process per cron run. Or fetch
//  only "most likely to have updates" records.   
//- see if invoice needs to be different than trxn for ipn created records

/**
 * Paperless.Processrecur API uses paperless report api to fetch transactions  
 * related to recurring contributions initiated by civi, and import
 * them in civi. 
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function civicrm_api3_paperless_processrecur($params) {
  _ppDebug('processrecur $params', $params);

  $limit = CRM_Utils_Array::value('limit', $params, 10);
  $count = 0;

  // Use civicrm_paperlesstrans_profilenumbers table to fetch recur records
  // along with their profile numbers.
  $sql = "
    SELECT pp.profile_number, pp.recur_id, cr.* 
    FROM civicrm_paperlesstrans_profilenumbers pp
    INNER JOIN civicrm_contribution_recur cr 
      ON pp.recur_id = cr.id
    WHERE cr.end_date IS NULL
      AND cr.payment_processor_id IS NOT NULL
  ";// TODO: also check cr.contribution-status-id
  $dao = CRM_Core_DAO::executeQuery($sql);

  while ($dao->fetch() && ($count <= $limit)) {
    _ppDebug('processrecur $count', $count);
    _ppDebug('processrecur $dao', $dao);

    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($dao->payment_processor_id);
    _ppDebug('processrecur $paymentProcessor', $paymentProcessor);

    //enable trace when system debugging enabled
    $client = new SoapClient($paymentProcessor["url_site"], array(
      'trace' => (Civi::settings()->get('debug_enabled')) ? 1 : 0,
      'exceptions' => 0
    ));
    _ppDebug('processrecur $client', $client);

    $paramsTrans = array(
      "token" => array(  
        "TerminalID"  => $paymentProcessor["user_name"],
        "TerminalKey" => $paymentProcessor["password"]
      ),
      "fromDateTime" => CRM_Utils_Date::customFormat($dao->start_date, '%m/%d/%Y 00:00'),
      "toDateTime" => date('m/d/Y 23:59'),
      "profileNumber" => $dao->profile_number,
      "includeTests" => 1,
    );

    $run = $client->__call( "SearchTransactionsByProfile", array("parameters" => $paramsTrans) );
    _ppDebug('processrecur $run', $run);

    // Note: TransactionObject is an object with only 1 transaction if there are
    // no more than 1 payment, which should already exist in civi. And it's an 
    // array if there are more than 1 payments, which is what we are interested in.
    if (!empty($run->SearchTransactionsByProfileResult->TransactionObject) && 
      is_array($run->SearchTransactionsByProfileResult->TransactionObject)
    ) {
      foreach ($run->SearchTransactionsByProfileResult->TransactionObject as $trxn) {
        if ($count >= $limit) break;

        _ppDebug('processrecur $trxn', $trxn);

        if (!empty($trxn->CustomFields->Field_1) && substr($trxn->CustomFields->Field_1, 0, 10) == 'InvoiceID:') {
          // this should exist in civi as first payment for recur
          _ppDebug('Skipping first payment', $trxn);
        }
        else if ($trxn->ID && $trxn->ResponseCode == 'Success') {
          // check if $trxn->ID is present in civi
          //TODO possibly mismatched; $trxn->ID stores the Civi invoice ID, but retrieves the PP transaction ID
          try {
            $result = civicrm_api3('contribution', 'get', [
              'return' => ['id', 'contribution_recur_id'],
              'trxn_id' => $trxn->ID,
            ]);
            _ppDebug('processrecur $result', $result);
          }
          catch (CiviCRM_API3_Exception $e) {
            _ppDebug('processrecur contribution get $e', $e);
          }

          if (!empty($result['id'])) {
            $contributionID = $result['id'];

            // skip if contribution is already in the system.
            CRM_Core_Error::debug_log_message("Trxn ID {$trxn->ID} already processed. Trxn Recur ID: {$dao->recur_id}. Civi Contribution ID {$contributionID}. Civi Recur ID: {$result['values'][$contributionID]['contribution_recur_id']}", FALSE, 'paperless');
            continue;
          }

          $date = new DateTime($trxn->DateTimeStamp);
          try {
            $resultRT = civicrm_api3('Contribution', 'repeattransaction', [
              'contribution_recur_id' => $dao->recur_id,
              //'original_contribution_id' => $previous_completed_contribution_id,
              'contribution_status_id' => "Completed",
              'receive_date' => $date->format('Y-m-d H:i:s'),
              'trxn_id' => $trxn->ID,
              'total_amount' => $trxn->Amount,
              //'fee_amount' => $fee, //TODO $trxn doesn't appear to include the fee_amount
              //'invoice_id' => $new_invoice_id - contribution.repeattransaction doesn't support it currently
              'is_email_receipt' => 1,
            ]);
            _ppDebug('processrecur $resultRT', $resultRT);
          }
          catch (CiviCRM_API3_Exception $e) {
            _ppDebug('processrecur repeattransaction $e', $e);
          }

          //TODO are these supposed to be the same value?
          // Update invoice_id manually. repeattransaction doesn't return the new contrib id either
          $queryParams = array(
            1 => array($trxn->ID, 'String'),
            2 => array($trxn->ID, 'String'),
          );
          _ppDebug('processrecur $queryParams', $queryParams);

          CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution
            SET invoice_id = %1
            WHERE trxn_id = %2
          ", $queryParams);
          CRM_Core_Error::debug_log_message("New transaction (ID: {$trxn->ID}) added to recurring contribution (ID: {$dao->recur_id}).", FALSE, 'paperless');

          $count++;
        }
      }
    }
  }

  return civicrm_api3_create_success(array('count' => $count), $params, 'Paperless', 'Processrecur');
}

function _ppDebug($msg, $var, $force = FALSE) {
  if (Civi::settings()->get('debug_enabled') || $force) {
    CRM_Core_Error::debug_var($msg, $var, TRUE, TRUE, 'paperless');
  }
}
