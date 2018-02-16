<?php
use CRM_Paperlesstrans_ExtensionUtil as E;

/**
 * Paperless.Futureprocess API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_paperless_Futureprocess_spec(&$spec) {
  //$spec['magicword']['api.required'] = 1;
}

/**
 * Paperless.Futureprocess API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_paperless_Futureprocess($params) {

$TestMode = $params["TestMode"];
$profile = $params["Profile_ID"];
$type = $params['Type'];
if($TestMode != "True")
{
    if($type == "Card")
{
      $result = civicrm_api3('PaymentProcessor', 'get', array(
      'sequential' => 1,
      'return' => array("user_name", "password"),
      'url_site' => "https://svc.paperlesstrans.com:9999/?wsdl",
      'payment_type' => 1,
    ));
    
   $id = $result["values"][0]["user_name"];
   $password = $result["values"][0]["password"];
   $client = new SoapClient("https://svc.paperlesstrans.com:9999/?wsdl");
$params =( array( "req" => array(
  "Token" => array(  "TerminalID" => $id,
  "TerminalKey"   =>      $password),
  "TestMode"      =>      "False",
  "Amount"        =>      $params['Amount'],
  "Currency"              =>      "USD",
  "CardPresent"   =>      "True",
  "ProfileNumber" =>  $profile))); 
  $run = $client->__call( "processCard", array("parameters" => $params) ); 
  if ($run->ProcessCardResult->ResponseCode == 0) 
  {
    echo "Transaction ID=".$run->ProcessCardResult->TransactionID."\n";
   if ($run->ProcessCardResult->IsApproved == "True") 
    {
      echo "Authorization ID=".$run->ProcessCardResult->AuthorizationNumber."\n";  
      CRM_Core_DAO::executeQuery("UPDATE civicrm_job SET is_active=0 where parameters LIKE '%".$profile."%'");
      }
  } 
  else 
  {
    echo "Error Message=".$run->ProcessCardResult->Message."\n";
   }
 }
else
{
    $result = civicrm_api3('PaymentProcessor', 'get', array(
      'sequential' => 1,
      'return' => array("user_name", "password"),
      'url_site' => "https://svc.paperlesstrans.com:9999/?wsdl",
      'payment_type' => 2,
    ));
   $id = $result['values'][0]['user_name'];
   $password = $result['values'][0]['password'];
   $client = new SoapClient("https://svc.paperlesstrans.com:9999/?wsdl");
$params =( array( "req" => array(
  "Token" => array(  "TerminalID" => $id,
  "TerminalKey"   =>      $password),
  "TestMode"      =>      "False",
  "Amount"        =>      $params['Amount'],
  "Currency"              =>      "USD",
  "CardPresent"   =>      "True",
  "ProfileNumber" =>  $profile))); 
  $run = $client->__call( "ProcessACH", array("parameters" => $params) );
  if ($run->ProcessACHResult->ResponseCode == 0) 
  {
    echo "Transaction ID=".$run->ProcessACHResult->TransactionID."\n";
    if ($run->ProcessACHResult->IsAccepted == "True") 
    {
      echo "Authorization ID=".$run->ProcessACHResult->AuthorizationNumber."\n";
      CRM_Core_DAO::executeQuery("UPDATE civicrm_job SET is_active=0 where parameters LIKE '%".$profile."%'");
    }
  } 
  else 
  {
    echo "Error Message=".$run->ProcessACHResult->Message."\n";
  }
 }
}
else
{

CRM_Core_DAO::executeQuery("SET SQL_SAFE_UPDATES = 0;");
$client = new SoapClient("http://svc.paperlesstrans.com:8888/?wsdl");
$params =( array( "req" => array(
  "Token" => array(  "TerminalID" => "18304896-329f-4b2e-a6e4-b39157dafeda",
  "TerminalKey"   =>      "390489817"),
  "TestMode"      =>      "True",
  "Amount"        =>      $params['Amount'],
  "Currency"              =>      "USD",
  "CardPresent"   =>      "True",
  "ProfileNumber" =>  $profile))); 
  
if($type == "Card")
{
  $run = $client->__call( "processCard", array("parameters" => $params) ); 
  if ($run->ProcessCardResult->ResponseCode == 0) 
  {
    echo "Transaction ID=".$run->ProcessCardResult->TransactionID."\n";
    if ($run->ProcessCardResult->IsApproved == "True") 
    {
      echo "Authorization ID=".$run->ProcessCardResult->AuthorizationNumber."\n";  
        
      CRM_Core_DAO::executeQuery("UPDATE civicrm_job SET is_active=0 where parameters LIKE '%".$profile."%'");
      }
  } 
  else 
  {
    echo "Error Message=".$run->ProcessCardResult->Message."\n";
    
  }
}
else
{
  $run = $client->__call( "ProcessACH", array("parameters" => $params) );
  if ($run->ProcessACHResult->ResponseCode == 0) 
  {
    echo "Transaction ID=".$run->ProcessACHResult->TransactionID."\n";
    if ($run->ProcessACHResult->IsAccepted == "True") 
    {
      echo "Authorization ID=".$run->ProcessACHResult->AuthorizationNumber."\n";
      CRM_Core_DAO::executeQuery("UPDATE civicrm_job SET is_active=0 where parameters LIKE '%".$profile."%'");
    }
  } 
  else 
  {
    echo "Error Message=".$run->ProcessACHResult->Message."\n";
  }
}
}

  return civicrm_api3_create_success("Hello World", $params, 'Paperless', 'Futureprocess');
}
?>


