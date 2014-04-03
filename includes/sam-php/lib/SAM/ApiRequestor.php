<?php

class SAM_ApiRequestor
{
  public $apiKey;

  public function __construct($apiKey=null, $apiSecret=null)
  {
    $this->_apiKey = $apiKey;
    $this->_apiSecret = $apiSecret;
  }

  public static function apiUrl($url='')
  {
    $apiBase = SAM::$apiBase;
    return "$apiBase$url";
  }

  public static function utf8($value)
  {
    if (is_string($value) && mb_detect_encoding($value, "UTF-8", TRUE) != "UTF-8")
      return utf8_encode($value);
    else
      return $value;
  }

  private static function _encodeObjects($d)
  {
    if ($d instanceof SAM_ApiResource) {
      return self::utf8($d->id);
    } else if ($d === true) {
      return 'true';
    } else if ($d === false) {
      return 'false';
    } else if (is_array($d)) {
      $res = array();
      foreach ($d as $k => $v)
      	$res[$k] = self::_encodeObjects($v);
      return $res;
    } else {
      return self::utf8($d);
    }
  }

  public static function encode($arr, $prefix=null)
  {
    if (!is_array($arr))
      return $arr;

    $r = array();
    foreach ($arr as $k => $v) {
      if (is_null($v))
        continue;

      if ($prefix && $k && !is_int($k))
        $k = $prefix."[".$k."]";
      else if ($prefix)
        $k = $prefix."[]";

      if (is_array($v)) {
        $r[] = self::encode($v, $k, true);
      } else {
        $r[] = urlencode($k)."=".urlencode($v);
      }
    }

    return implode("&", $r);
  }

  public function request($meth, $url, $params=null)
  {
    if (!$params)
      $params = array();
    list($rbody, $rcode, $myApiKey, $myApiSecret) = $this->_requestRaw($meth, $url, $params);
    $resp = $this->_interpretResponse($rbody, $rcode);
    return array($resp, $myApiKey, $myApiSecret);
  }

  public function handleApiError($rbody, $rcode, $resp)
  {
    if (!is_array($resp) || !isset($resp['error']))
      throw new SAM_ApiError("Invalid response object from API: $rbody (HTTP response code was $rcode)", $rcode, $rbody, $resp);
    $error = $resp['error'];
    switch ($rcode) {
    case 400:
    case 404:
      throw new SAM_InvalidRequestError(isset($error['message']) ? $error['message'] : null,
                                           isset($error['param']) ? $error['param'] : null,
                                           $rcode, $rbody, $resp);
    case 401:
      throw new SAM_AuthenticationError(isset($error['message']) ? $error['message'] : null, $rcode, $rbody, $resp);
    default:
      throw new SAM_ApiError(isset($error['message']) ? $error['message'] : null, $rcode, $rbody, $resp);
    }
  }

  private function _requestRaw($meth, $url, $params)
  {
    $myApiKey = $this->_apiKey;
    if (!$myApiKey)
      $myApiKey = SAM::$apiKey;

    if (!$myApiKey)
      throw new SAM_AuthenticationError('No API key provided.  (HINT: set your API key using "SAM::setApiKey(<API-KEY>)".');

    $myApiSecret = $this->_apiSecret;
    if (!$myApiSecret)
      $myApiSecret = SAM::$apiSecret;

    if (!$myApiSecret)
      throw new SAM_AuthenticationError('No API secret provided.  (HINT: set your API secret using "SAM::setApiSecret(<API-SECRET>)".');

    $absUrl = $this->apiUrl($url);
    $params = self::_encodeObjects($params);
    $langVersion = phpversion();
    $uname = php_uname();
    $ua = array('bindings_version' => SAM::VERSION,
		'lang' => 'php',
		'lang_version' => $langVersion,
		'publisher' => 'SAM',
		'uname' => $uname);
    $headers = array('X-SAM-Client-User-Agent: ' . json_encode($ua),
		     'User-Agent: SAM/v1 PhpBindings/' . SAM::VERSION,
                     'Authorization: Basic ' . base64_encode($myApiKey . ':' . $myApiSecret));
    if (SAM::$apiVersion)
      $headers[] = 'SAM-Version: ' . SAM::$apiVersion;
    list($rbody, $rcode) = $this->_curlRequest($meth, $absUrl, $headers, $params);
    return array($rbody, $rcode, $myApiKey, $myApiSecret);
  }

  private function _interpretResponse($rbody, $rcode)
  {
    try {
      $resp = json_decode($rbody, true);
    } catch (Exception $e) {
      throw new SAM_ApiError("Invalid response body from API: $rbody (HTTP response code was $rcode)", $rcode, $rbody);
    }

    if ($rcode < 200 || $rcode >= 300) {
      $this->handleApiError($rbody, $rcode, $resp);
    }
    return $resp;
  }

  private function _curlRequest($meth, $absUrl, $headers, $params)
  {
    $curl = curl_init();
    $meth = strtolower($meth);
    $opts = array();
    if ($meth == 'get') {
      $opts[CURLOPT_HTTPGET] = 1;
      if (count($params) > 0) {
	$encoded = self::encode($params);
	$absUrl = "$absUrl?$encoded";
      }
    } else if ($meth == 'post') {
      $opts[CURLOPT_POST] = 1;
      $opts[CURLOPT_POSTFIELDS] = self::encode($params);
    } else if ($meth == 'delete')  {
      $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
      if (count($params) > 0) {
	$encoded = self::encode($params);
	$absUrl = "$absUrl?$encoded";
      }
    } else {
      throw new SAM_ApiError("Unrecognized method $meth");
    }

    $absUrl = self::utf8($absUrl);
    $opts[CURLOPT_URL] = $absUrl;
    $opts[CURLOPT_RETURNTRANSFER] = true;
    $opts[CURLOPT_CONNECTTIMEOUT] = 30;
    $opts[CURLOPT_TIMEOUT] = 80;
    $opts[CURLOPT_RETURNTRANSFER] = true;
    $opts[CURLOPT_HTTPHEADER] = $headers;
    if (!SAM::$verifySslCerts)
      $opts[CURLOPT_SSL_VERIFYPEER] = false;

    curl_setopt_array($curl, $opts);
    $rbody = curl_exec($curl);

    $errno = curl_errno($curl);
    if ($errno == CURLE_SSL_CACERT ||
	$errno == CURLE_SSL_PEER_CERTIFICATE ||
	$errno == 77 // CURLE_SSL_CACERT_BADFILE (constant not defined in PHP though)
	) {
      array_push($headers, 'X-SAM-Client-Info: {"ca":"using SAM-supplied CA bundle"}');
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_CAINFO,
                  dirname(__FILE__) . '/../data/ca-certificates.crt');
      $rbody = curl_exec($curl);
    }

    if ($rbody === false) {
      $errno = curl_errno($curl);
      $message = curl_error($curl);
      curl_close($curl);
      $this->handleCurlError($errno, $message);
    }

    $rcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return array($rbody, $rcode);
  }

  public function handleCurlError($errno, $message)
  {
    $apiBase = SAM::$apiBase;
    switch ($errno) {
    case CURLE_COULDNT_CONNECT:
    case CURLE_COULDNT_RESOLVE_HOST:
    case CURLE_OPERATION_TIMEOUTED:
      $msg = "Could not connect to SAM ($apiBase).  Please check your internet connection and try again.  If this problem persists, let us know at james@samdesk.io.";
      break;
    case CURLE_SSL_CACERT:
    case CURLE_SSL_PEER_CERTIFICATE:
      $msg = "Could not verify SAM's SSL certificate.  Please make sure that your network is not intercepting certificates.  (Try going to $apiBase in your browser.)  If this problem persists, let us know at james@samdesk.io.";
      break;
    default:
      $msg = "Unexpected error communicating with SAM.  If this problem persists, let us know at james@samdesk.io.";
    }

    $msg .= "\n\n(Network error [errno $errno]: $message)";
    throw new SAM_ApiConnectionError($msg);
  }
}
