<?php

class SAM_Account extends SAM_SingletonApiResource
{
  public static function constructFrom($values, $apiKey=null, $apiSecret=null)
  {
    $class = get_class();
    return self::scopedConstructFrom($class, $values, $apiKey, $apiSecret);
  }

  public static function retrieve($apiKey=null, $apiSecret=null)
  {
    $class = get_class();
    return self::_scopedSingletonRetrieve($class, $apiKey, $apiSecret);
  }
}
