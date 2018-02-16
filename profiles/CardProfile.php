<?php

$type = $_REQUEST['type'];
$date = $_REQUEST['date'];
$email = $_REQUEST['email'];
$amount = $_REQUEST['amount'];
$credit_card_number = $_REQUEST['credit_card_number'];
$credit_card_exp_date_M = $_REQUEST['credit_card_exp_date_M'];
$credit_card_exp_date_Y = $_REQUEST['credit_card_exp_date_Y'];
$cvv2 = $_REQUEST['cvv2'];
$billing_first_name = $_REQUEST['billing_first_name'];
$billing_last_name = $_REQUEST['billing_last_name'];
$billing_street_address = $_REQUEST['billing_street_address'];
$city = $_REQUEST['city'];
$state = $_REQUEST['state'];
$zip = $_REQUEST['zip'];
$mode = $_REQUEST['mode'];
$username = $_REQUEST['username'];
$password = $_REQUEST['password'];

echo "Type=".$type."\n";
echo "Date=".$date."\n";
echo "Amount=".$amount."\n";

if ($mode == "Live")
{
  $testMode = "False";

  $client = new SoapClient("https://svc.paperlesstrans.com:9999/?wsdl");
  $params =( array( "req" => array(
        "Token" =>  array(  "TerminalID"  => $username,
        "TerminalKey"   =>      $password),
        "TestMode"      =>      $testMode,
        "ListingName"   =>      $billing_first_name." ".$billing_last_name,
        "Card"          =>      array(  "CardNumber"  => $credit_card_number,
                                        "ExpirationMonth" => $credit_card_exp_date_M,  
                                        "ExpirationYear"=>      $credit_card_exp_date_Y,
                                        "SecurityCode"  =>      $cvv2,
                                        "NameOnAccount" => $billing_first_name." ".$billing_last_name,
                                        "Address"       => array( "Street"  =>  $billing_street_address,
                                                                  "City"    =>      $city,
                                                                  "State"         =>      $state,
                                                                  "Zip"           =>      $zip,
                                                                  "Country"       =>      "US"),
                                        "Identification"=> array( "IDType"  =>  "4" )))));
    
  $run = $client->__call( "CreateCardProfile", array("parameters" => $params) );
  if ($run->CreateCardProfileResult->ResponseCode == 0) 
  {
    echo "Transaction_ID=".$run->CreateCardProfileResult->TransactionID."\n";
    echo "Profile_ID=".$run->CreateCardProfileResult->ProfileNumber."\n";
  }
  else
  {
    echo "Error Message=".$run->CreateCardProfileResult->Message."\n";
  }

  echo "TestMode=".$testMode."\n";
}

else
{
  $testMode = "True";
  $client = new SoapClient("http://svc.paperlesstrans.com:8888/?wsdl");
  $params =( array( "req" => array(
        "Token" =>  array(  "TerminalID"  => "18304896-329f-4b2e-a6e4-b39157dafeda",
        "TerminalKey"   =>      "390489817"),
        "TestMode"      =>      $testMode,
        "ListingName"   =>      $billing_first_name." ".$billing_last_name,
        "Card"          =>      array(  "CardNumber"  => $credit_card_number,
                                        "ExpirationMonth" => $credit_card_exp_date_M,  
                                        "ExpirationYear"=>      $credit_card_exp_date_Y,
                                        "SecurityCode"  =>      $cvv2,
                                        "NameOnAccount" => $billing_first_name." ".$billing_last_name,
                                        "Address"       => array( "Street"  =>  $billing_street_address,
                                                                  "City"          =>      $city,
                                                                  "State"         =>      $state,
                                                                  "Zip"           =>      $zip,
                                                                  "Country"       =>      "US"),
                                        "Identification"=> array( "IDType"  =>  "4" )))));
  $run = $client->__call( "CreateCardProfile", array("parameters" => $params) );
  if ($run->CreateCardProfileResult->ResponseCode == 0) 
  {
    echo "Transaction_ID=".$run->CreateCardProfileResult->TransactionID."\n";
    echo "Profile_ID=".$run->CreateCardProfileResult->ProfileNumber."\n";
  } 
  else 
  {
    echo "Error Message=".$run->CreateCardProfileResult->Message."\n";
  }

  echo "TestMode=".$testMode."\n";
}

echo "email=".$email."\n";

