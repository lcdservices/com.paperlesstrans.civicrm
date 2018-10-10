<?php

class CRM_Paperlesstrans_CiviPayment {

  var $recurId      = NULL;
  var $recur        = NULL;
  var $logMessage   = FALSE;

  /**
   * @param int $recurId
   */
  public function __construct($recurId) {
    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    if ($recur->find(TRUE)) {
      $this->recur   = $recur;
      $this->recurId = $recur->id;
      $this->getPaymentCount();
    }
  }

  public function isPaymentDue() {
    $result = FALSE;
    if ($this->recurId) {
      if ($this->recur->end_date || $this->recur->cancel_date) {
        $this->setMessage('Payment not due. Recur ended or cancelled.');
        return FALSE;
      }
      if ($this->recur->installments && ($this->paymentCount >= $this->recur->installments)) {
        $this->setMessage("Payment not due. Number of installments already reached - {$this->paymentCount}/{$this->recur->installments}.");
        //TODO: update recur with end date
        return FALSE;
      }
      $now      = strtotime(CRM_Utils_Date::currentDBDate());
      $nextTime = strtotime($this->recur->start_date);
      $startTime = $nextTime;

      // It's possible that any payment was delayed, and even though it's
      // time for payment, it won't be considered, if we base on last payment
      // receive date. E.g 1st Jan, 1st Feb, 15th Mar, 1st Apr.
      // To counter such problems, we consider recur start date as starting point
      for ($i = 1; $i <= $this->paymentCount; $i++) {
        $interval = $this->recur->frequency_interval * $i;
        $offset   = "+{$interval} {$this->recur->frequency_unit}";
        $nextTime = strtotime($offset, $startTime);
        if ($this->recur->frequency_unit == 'month') {
          while (((date('m', $startTime) + $interval) % 12) != (date('m', $nextTime) % 12)) {
            $nextTime = strtotime('-1 day', $nextTime);
          }
        }
      }
      $readableNextTime = date('l dS \o\f F Y h:i:s A', $nextTime);
      $result = ($now >= $nextTime);
      if (!$result) {
        $this->setMessage("Payment not due yet. Due on {$readableNextTime}");
      }
      return $result;
    }
    return FALSE;
  }

  public function getPaymentCount() {
    // fixme: status generalize
    $query = "
      SELECT count(c.id)
      FROM  civicrm_contribution c
      INNER JOIN civicrm_contribution_recur r on c.contribution_recur_id = r.id
      WHERE r.id = %1 AND c.contribution_status_id = 1";
    $this->paymentCount = CRM_Core_DAO::singleValueQuery($query, array(1 => array($this->recur->id, 'Positive')));
    return $this->paymentCount;
  }

  public function getPendingPaymentId() {
    // fixme: status generalize
    $query = "
      SELECT c.id
      FROM   civicrm_contribution c
      JOIN   civicrm_contribution_recur r on c.contribution_recur_id = r.id
      WHERE  r.id = %1 AND c.contribution_status_id = 2
      ORDER BY c.receive_date DESC
      LIMIT 1";
    return CRM_Core_DAO::singleValueQuery($query, array(1 => array($this->recur->id, 'Positive')));
  }

  function addOrdinalNumberSuffix($num) {
    if (!in_array(($num % 100),array(11,12,13))){
      switch ($num % 10) {
        // Handle 1st, 2nd, 3rd
        case 1:  return $num.'st';
        case 2:  return $num.'nd';
        case 3:  return $num.'rd';
      }
    }
    return $num.'th';
  }

  public function enableMessage() {
    $this->logMessage = TRUE;
  }

  public function setMessage($msg) {
    if ($this->logMessage) {
      $query = "
        UPDATE civicrm_paperlesstrans_profilenumbers
        SET    processed_date = NOW(), message = %2
        WHERE  recur_id = %1";
      return CRM_Core_DAO::singleValueQuery($query, array(
        1 => array($this->recur->id, 'Positive'),
        2 => array($msg, 'String'),
      ));
    }
    return FALSE;
  }
}
