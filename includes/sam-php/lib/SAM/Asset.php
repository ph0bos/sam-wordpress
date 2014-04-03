<?php

class SAM_Asset extends SAM_ApiResource
{
  public static function constructFrom($values, $apiKey=null, $apiSecret=null)
  {
    $class = get_class();
    return self::scopedConstructFrom($class, $values, $apiKey, $apiSecret);
  }

  public static function retrieve($storyId, $id, $apiKey=null, $apiSecret=null)
  {
    $requestor = new SAM_ApiRequestor($apiKey, $apiSecret);
    $base = SAM_Asset::getClassUrl($storyId);
    $id = SAM_ApiRequestor::utf8($id);
    $extn = urlencode($id);
    list($response, $apiKey, $apiSecret) = $requestor->request('get', "$base/$extn");
    return SAM_Util::convertToSAMObject($response, $apiKey, $apiSecret);
  }

  public static function all($storyId, $params=null, $apiKey=null, $apiSecret=null)
  {
    $requestor = new SAM_ApiRequestor($apiKey, $apiSecret);
    $base = SAM_Asset::getClassUrl($storyId);
    list($response, $apiKey, $apiSecret) = $requestor->request('get', $base, $params);
    return SAM_Util::convertToSAMObject($response, $apiKey, $apiSecret);
  }

  // TODO: handle nested objects better
  private static function getClassUrl($storyId) 
  {
    $base = self::classUrl('SAM_Story');
    $storyId = SAM_ApiRequestor::utf8($storyId);
    $storyExtn = urlencode($storyId);
    return "$base/$storyExtn/assets";
  }
}