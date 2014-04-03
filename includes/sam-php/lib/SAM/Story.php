<?php

class SAM_Story extends SAM_ApiResource
{
  public static function constructFrom($values, $apiKey=null, $apiSecret=null)
  {
    $class = get_class();
    return self::scopedConstructFrom($class, $values, $apiKey, $apiSecret);
  }

  public static function retrieve($id, $apiKey=null, $apiSecret=null)
  {
    $class = get_class();
    return self::_scopedRetrieve($class, $id, $apiKey, $apiSecret);
  }

  public static function all($params=null, $apiKey=null, $apiSecret=null)
  {
    $class = get_class();
    return self::_scopedAll($class, $params, $apiKey, $apiSecret);
  }
}