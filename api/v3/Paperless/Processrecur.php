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
  $limit = CRM_Utils_Array::value('limit', $params, 10);
  $count = 0;

  // Use civicrm_paperlesstrans_profilenumbers table to fetch recur records
  // along with their profile numbers.
  $sql = "SELECT pp.profile_number, pp.recur_id, cr.* 
    FROM civicrm_paperlesstrans_profilenumbers pp
    INNER JOIN civicrm_contribution_recur cr on pp.recur_id = cr.id
    WHERE cr.end_date is null";// fixme: also check cr.contribution-status-id
  $dao = CRM_Core_DAO::executeQuery($sql);
  while ($dao->fetch() && ($count <= $limit)) {
    $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($dao->payment_processor_id);
    //fixme: turn off trace for production
    $client = new SoapClient($paymentProcessor["url_site"], array(
      'trace'      => 1, 
      'exceptions' => 0
    ));
    $params = array( 
      "token" => array(  
        "TerminalID"  =>  $paymentProcessor["user_name"],
        "TerminalKey" =>  $paymentProcessor["password"]
      ),
      "fromDateTime"  =>  CRM_Utils_Date::customFormat($dao->start_date, '%m/%d/%Y 00:00'),
      "toDateTime"    =>  date('m/d/Y 23:59'),
      "profileNumber" =>  $dao->profile_number,
      "includeTests"  =>  1,
    );

    $run = $client->__call( "SearchTransactionsByProfile", array("parameters" => $params) ); 
    // Note: TransactionObject is an object with only 1 transaction if there are
    // no more than 1 payment, which should already exist in civi. And it's an 
    // array if there are more than 1 payments, which is what we interested in.
    if (!empty($run->SearchTransactionsByProfileResult->TransactionObject) && 
      is_array($run->SearchTransactionsByProfileResult->TransactionObject)) 
    {
      foreach ($run->SearchTransactionsByProfileResult->TransactionObject as $trxn) {
        if ($count >= $limit) break;

        if (!empty($trxn->CustomFields->Field_1) && substr($name, 0, 11) == 'Invoice ID:') {
          // this should exist in civi as first payment for recur
          CRM_Core_Error::debug_log_message("Skipping first payment. {$trxn}");
        } else if ($trxn->ID && $trxn->ResponseCode == 'Success') {
          // check if $trxn->ID is present in civi
          $result = civicrm_api3('contribution', 'get', array(
            'return' => array('id', 'contribution_recur_id'),
            'trxn_id' => $trxn->ID,
          ));
          if (!empty($result['id'])) {
            $contributionID = $result['id'];
            // skip if contribution is already in the system.
            CRM_Core_Error::debug_log_message("Trxn ID {$trxn->ID} already processed. Trxn Recur ID: {$dao->recur_id}. Civi Contribution ID {$contributionID}. Civi Recur ID: {$result['values'][$contributionID]['contribution_recur_id']}");
            continue;
          }

          $date = new DateTime($trxn->DateTimeStamp);
          $result = civicrm_api3('Contribution', 'repeattransaction', array(
            'contribution_recur_id'    => $dao->recur_id,
            //'original_contribution_id' => $previous_completed_contribution_id,
            'contribution_status_id'   => "Completed",
            'receive_date' => $date->format('Y-m-d H:i:s'),
            'trxn_id'      => $trxn->ID,
            'total_amount' => $trxn->Amount,
            'fee_amount'   => $fee,
            //'invoice_id' => $new_invoice_id - contribution.repeattransaction doesn't support it currently
            'is_email_receipt' => 1,
          ));

          // Update invoice_id manually. repeattransaction doesn't return the new contrib id either
          $queryParams = array(
            1 => array($trxn->ID, 'String'),
            2 => array($trxn->ID, 'String'),
          );
          CRM_Core_DAO::executeQuery("UPDATE civicrm_contribution
            SET invoice_id = %1
            WHERE trxn_id = %2",
            $queryParams
          );
          CRM_Core_Error::debug_log_message("New transaction (ID: {$trxn->ID}) added to recurring contribution (ID: {$dao->recur_id}).");
          $count++;
        }
      }
    }
  }
  return civicrm_api3_create_success($result, $params, 'Paperless', 'Processrecur');
}
