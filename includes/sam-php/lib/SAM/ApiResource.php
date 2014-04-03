<?php

abstract class SAM_ApiResource extends SAM_Object
{
  protected static function _scopedRetrieve($class, $id, $apiKey=null, $apiSecret=null)
  {
    $instance = new $class($id, $apiKey, $apiSecret);
    $instance->refresh();
    return $instance;
  }

  public function refresh()
  {
    $requestor = new SAM_ApiRequestor($this->_apiKey, $this->_apiSecret);
    $url = $this->instanceUrl();

    list($response, $apiKey, $apiSecret) = $requestor->request('get', $url, $this->_retrieveOptions);
    $this->refreshFrom($response, $apiKey, $apiSecret);
    return $this;
   }

  public static function className($class)
  {
    // Useful for namespaces: Foo\SAM_Account
    if ($postfix = strrchr($class, '\\'))
      $class = substr($postfix, 1);
    if (substr($class, 0, strlen('SAM')) == 'SAM')
      $class = substr($class, strlen('SAM'));
    $class = str_replace('_', '', $class);
    $name = urlencode($class);
    $name = strtolower($name);
    return $name;
  }

  public static function classUrl($class)
  {
    $base = self::_scopedLsb($class, 'className', $class);
    if (SAM_Util::endsWith($base, 'y')) {
      $base = substr_replace($base, 'ie', -1);
    }
    return "/api/v1/${base}s";
  }

  public function instanceUrl()
  {
    $id = $this['id'];
    $class = get_class($this);
    if (!$id) {
      throw new SAM_InvalidRequestError("Could not determine which URL to request: $class instance has invalid ID: $id", null);
    }
    $id = SAM_ApiRequestor::utf8($id);
    $base = $this->_lsb('classUrl', $class);
    $extn = urlencode($id);
    return "$base/$extn";
  }

  private static function _validateCall($method, $params=null, $apiKey=null, $apiSecret=null)
  {
    if ($params && !is_array($params))
      throw new SAM_Error("You must pass an array as the first argument to SAM API method calls.");
    if ($apiKey && !is_string($apiKey))
      throw new SAM_Error('The second argument to SAM API method calls is an optional per-request apiKey, which must be a string.  (HINT: you can set a global apiKey by "SAM::setApiKey(<apiKey>)")');
    if ($apiSecret && !is_string($apiSecret))
      throw new SAM_Error('The third argument to SAM API method calls is an optional per-request apiSecret, which must be a string.  (HINT: you can set a global apiSecret by "SAM::setApiSecret(<apiSecret>)")');
    if ($apiKey && !$apiSecret)
      throw new SAM_Error('If you are providing an optional per-request apiKey as the second argument then you must also provide the matching apiSecret as the third argument.');
  }

  protected static function _scopedAll($class, $params=null, $apiKey=null, $apiSecret=null)
  {
    self::_validateCall('all', $params, $apiKey, $apiSecret);
    $requestor = new SAM_ApiRequestor($apiKey, $apiSecret);
    $url = self::_scopedLsb($class, 'classUrl', $class);
    list($response, $apiKey, $apiSecret) = $requestor->request('get', $url, $params);
    return SAM_Util::convertToSAMObject($response, $apiKey, $apiSecret);
  }

  protected static function _scopedCreate($class, $params=null, $apiKey=null, $apiSecret=null)
  {
    self::_validateCall('create', $params, $apiKey, $apiSecret);
    $requestor = new SAM_ApiRequestor($apiKey, $apiSecret);
    $url = self::_scopedLsb($class, 'classUrl', $class);
    list($response, $apiKey, $apiSecret) = $requestor->request('post', $url, $params);
    return SAM_Util::convertToSAMObject($response, $apiKey, $apiSecret);
  }

  protected function _scopedSave($class)
  {
    self::_validateCall('save');
    $requestor = new SAM_ApiRequestor($this->_apiKey, $this->_apiSecret);
    $params = $this->serializeParameters();

    if (count($params) > 0) {
      $url = $this->instanceUrl();
      list($response, $apiKey, $apiSecret) = $requestor->request('post', $url, $params);
      $this->refreshFrom($response, $apiKey, $apiSecret);
    }
    return $this;
  }

  protected function _scopedDelete($class, $params=null)
  {
    self::_validateCall('delete');
    $requestor = new SAM_ApiRequestor($this->_apiKey, $this->_apiSecret);
    $url = $this->instanceUrl();
    list($response, $apiKey, $apiSecret) = $requestor->request('delete', $url, $params);
    $this->refreshFrom($response, $apiKey, $apiSecret);
    return $this;
  }
}
