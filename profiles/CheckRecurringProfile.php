<?php

$type = $_REQUEST['type'];    
$dateFirst = $_REQUEST['dateFirst'];
$dateSecond = $_REQUEST['dateSecond'];
$email = $_REQUEST['email'];
$amount = $_REQUEST['amount'];
$account_holder = $_REQUEST['account_holder'];
$bank_account_number = $_REQUEST['bank_account_number'];
$bank_identification_number = $_REQUEST['bank_identification_number'];
$bank_name = $_REQUEST['bank_name'];
$billing_first_name = $_REQUEST['billing_first_name'];
$billing_last_name = $_REQUEST['billing_last_name'];
$billing_street_address = $_REQUEST['billing_street_address'];
$city = $_REQUEST['city'];
$state = $_REQUEST['state'];
$zip = $_REQUEST['zip'];
$frequency_unit =$_REQUEST['frequency_unit'];
$count = $_REQUEST['count'];
$mode = $_REQUEST['mode'];
$username = $_REQUEST['username'];
$password = $_REQUEST['password'];

echo "Type=".$type."\n";
echo "Amount=".$amount."\n";
echo "StartDate=".$dateFirst."\n";
echo "EndDate=".$dateSecond."\n";
echo "email=".$email."\n";
echo "Count=".$count."\n";
echo "Frequency_Unit=".$frequency_unit."\n";

if ($mode == "Live")
{
  $testMode = "False";
  
  $client = new SoapClient("https://svc.paperlesstrans.com:9999/?wsdl");
  $params =( array( "req" => array(
        "Token" =>  array(  "TerminalID"    => $username,
        "TerminalKey"   =>      $password),
        "TestMode"      =>      $testMode,
        "CheckNumber"   =>      "1234",
        "ListingName"   =>      $account_holder,
        "Check"         =>      array(  "RoutingNumber" => $bank_identification_number,
                                        "AccountNumber" => $bank_account_number,
                                        "NameOnAccount" => $account_holder,
                                        "Address"       => array( "Street"  =>  $billing_street_address,
                                                                  "City"          =>      $city,
                                                                  "State"         =>      $state,   
                                                                  "Zip"           =>      $zip,
                                                                  "Country"       =>      "US"),
                                      ),
        "Identification"=> array( "IDType"  =>  "4" )))); 
  $run = $client->__call( "CreateACHProfile", array("parameters" => $params) ); 
  if ($run->CreateACHProfileResult->ResponseCode == 0) 
  {
    echo "Transaction_ID=".$run->CreateACHProfileResult->TransactionID."\n";
    echo "Profile_ID=".$run->CreateACHProfileResult->ProfileNumber."\n";
  }
  else 
  {
    echo "Error Message=".$run->CreateACHProfileResult->Message."\n";
  }
     
  echo "TestMode=".$testMode."\n";
}

else

{
  $testMode = "True";
  $client = new SoapClient("http://svc.paperlesstrans.com:8888/?wsdl");
  $params =( array( "req" => array(
        "Token" =>  array(  "TerminalID"    => "18304896-329f-4b2e-a6e4-b39157dafeda",
        "TerminalKey"   =>      "390489817"),
        "TestMode"      =>      $testMode,
        "ListingName"   =>      $account_holder,
        "Check"         =>      array(  "RoutingNumber" => $bank_identification_number,
                                        "AccountNumber" => $bank_account_number,
                                        "NameOnAccount" => $account_holder,
                                        "Address"       => array( "Street"  =>  $billing_street_address,
                                                                  "City"          =>      $city,
                                                                  "State"         =>      $state,
                                                                  "Zip"           =>      $zip,
                                                                  "Country"       =>      "US"),
                              ),
        "Identification"=> array( "IDType"  =>  "4" )))); 
  $run = $client->__call( "CreateACHProfile", array("parameters" => $params) ); 
  if ($run->CreateACHProfileResult->ResponseCode == 0) 
  {
    echo "Transaction_ID=".$run->CreateACHProfileResult->TransactionID."\n";
    echo "Profile_ID=".$run->CreateACHProfileResult->ProfileNumber."\n";
  }
  else 
  {
    echo "Error Message=".$run->CreateACHProfileResult->Message."\n";
  }

  echo "TestMode=".$testMode."\n";
}
