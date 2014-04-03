<?php

abstract class SAM
{
  public static $apiKey;
  public static $apiSecret;
  public static $apiBase = 'https://app.samdesk.io';
  public static $apiVersion = null;
  public static $verifySslCerts = false; // TODO: set this up
  const VERSION = '0.0.1';

  public static function getApiKey()
  {
    return self::$apiKey;
  }

  public static function setApiKey($apiKey)
  {
    self::$apiKey = $apiKey;
  }

  public static function getApiSecret()
  {
    return self::$apiSecret;
  }

  public static function setApiSecret($apiSecret)
  {
    self::$apiSecret = $apiSecret;
  }

  public static function getApiVersion()
  {
    return self::$apiVersion;
  }

  public static function setApiVersion($apiVersion)
  {
    self::$apiVersion = $apiVersion;
  }

  public static function getVerifySslCerts() {
    return self::$verifySslCerts;
  }

  public static function setVerifySslCerts($verify) {
    self::$verifySslCerts = $verify;
  }
}
