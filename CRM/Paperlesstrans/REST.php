<?php

class CRM_Paperlesstrans_REST {
  public $ch;
  public $url;
  //public function __construct() {
  //  if ($this->ch) {
  //    // close if already exist to create new obj
  //    curl_close($this->ch);
  //  }
  //  $this->ch = curl_init();
  //}  
  public function __destruct() {
    curl_close($this->ch);
  }
  public function init($url) {
    if ($this->ch) {
      // close if already exist to create new obj
      curl_close($this->ch);
    }
    $this->ch  = curl_init();
    $this->url = $url;
  }
  public function call($params) {
    curl_setopt_array($this->ch, array(
      CURLOPT_URL            => $this->url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING       => "",
      CURLOPT_MAXREDIRS      => 10,
      CURLOPT_TIMEOUT        => 30,
      CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST  => "POST",
      CURLOPT_POSTFIELDS     => json_encode($params),
      CURLOPT_HTTPHEADER     => array(
        "Cache-Control: no-cache",
        "Content-Type: application/json",
        "TerminalKey: 926fde32e9cf47c7862c7e0a5409",//FIXME
        "TestFlag: true"//FIXME
      ),
    ));
    $response = curl_exec($this->ch);
    if(curl_error($this->ch)) {
      throw new Exception("API call to {$this->url} failed: " . curl_error($this->ch));
    }
    $result = json_decode($response, true);
    CRM_Core_Error::debug_var('curl url', $this->url);
    CRM_Core_Error::debug_var('curl post $params', $params);
    CRM_Core_Error::debug_var('curl result', $result);
    return $result;
  }
  public function createProfile($params) {
    $this->init("http://api.paperlesstrans.com/profiles/create");
    return $this->call($params);
  }
  public function captureTransaction($params) {
    $this->init("http://api.paperlesstrans.com/transactions/capture");
    return $this->call($params);
  }
  //public function isError() {
  //  return curl_error($this->ch);
  //}
}


