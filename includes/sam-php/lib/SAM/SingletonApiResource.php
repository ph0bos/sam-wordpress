<?php

abstract class SAM_SingletonApiResource extends SAM_ApiResource
{
  protected static function _scopedSingletonRetrieve($class, $apiKey=null, $apiSecret=null)
  {
    $instance = new $class(null, $apiKey, $apiSecret);
    $instance->refresh();
    return $instance;
  }

  public static function classUrl($class)
  {
    $base = self::className($class);
    return "/api/v1/${base}";
  }

  public function instanceUrl()
  {
    $class = get_class($this);
    $base = self::classUrl($class);
    return "$base";
  }
}
