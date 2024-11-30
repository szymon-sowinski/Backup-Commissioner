<?php

namespace LS\Helpers;

class EncryptionUtility {

  private static $password_shuffled = "";
  private static $iv = "";
  private static $method = 'aes-256-cbc';

  static function EncryptSHA256(string $plainText, string $key = "(censored)") {
    self::HelperEncryptDecryptStream($key);
    return base64_encode(openssl_encrypt($plainText, self::$method, self::$password_shuffled, OPENSSL_RAW_DATA, self::$iv));
  }

  static function DecryptSHA256(string $encrypted, string $key = "(censored)") {
    self::HelperEncryptDecryptStream($key);
    return openssl_decrypt(base64_decode($encrypted), self::$method, self::$password_shuffled, OPENSSL_RAW_DATA, self::$iv);
  }

  private static function HelperEncryptDecryptStream(string $key = "(censored)") {
    self::$password_shuffled = substr(hash('sha256', $key, true), 0, 32);
    self::$iv = chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0) . chr(0x0);
  }

}
