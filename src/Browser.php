<?php

namespace KeltieCochrane\CriticalCss;

use BadMethodCallException;
use Detection\MobileDetect;

class Browser
{
  /**
   * @var int
   */
  const EXPIRES_OFFSET = 60 * 60 * 24 * 28;

  /**
   * @var string
   */
  const COOKIE_NAME = 'themosis-criticalcss';

  /**
   * @var \Detection\MobileDetect
   */
  protected static $mobileDetect;

  /**
   * Gets the mobile detect instance for us
   *
   * @return \Detection\MobileDetect
   */
  protected static function getMobileDetectInstance() : MobileDetect
  {
    if (is_null(static::$mobileDetect)) {
      static::$mobileDetect = new MobileDetect;
    }

    return static::$mobileDetect;
  }

  /**
   * Determines if the browser is a mobile device
   *
   * @return bool
   */
  public static function isMobile() : bool
  {
    $mobileDetect = static::getMobileDetectInstance();
    return $mobileDetect->isMobile() && !$mobileDetect->isTablet();
  }

  /**
   * Determines if the browser is a tablet device
   *
   * @return bool
   */
  public static function isTablet() : bool
  {
    $mobileDetect = static::getMobileDetectInstance();
    return $mobileDetect->isTablet();
  }

  /**
   * Determines if the browser is a desktop device
   *
   * @return bool
   */
  public static function isDesktop() : bool
  {
    $mobileDetect = static::getMobileDetectInstance();
    return !$mobileDetect->isMobile();
  }

  /**
   * Determines if this is a request inside WpCli
   *
   * @return bool
   */
  public static function isWpCli() : bool
  {
    return defined('WP_CLI') && WP_CLI;
  }

  /**
   * Determines if the cookie hit
   *
   * @param string $version
   * @return bool
   */
  public static function isCookieHit($version = null) : bool
  {
    $cookieExists = isset($_COOKIE['themosis-criticalcss']);

    if (is_null($version)) {
      $version = wp_get_theme()->get('Version');
    }

    if ($cookieExists && $_COOKIE[static::COOKIE_NAME] === $version) {
      return true;
    }
    else {
      return false;
    }
  }

  /**
   * Determines if the cookie missed
   *
   * @param string $version
   * @return bool
   */
  public static function isCookieMiss(string $version = null) : bool
  {
    return !static::isCookieHit($version);
  }

  /**
   * Sets the cookie to the current version of the theme
   *
   * @param int $expiresOffset
   * @param string $version
   * @return void
   */
  public function setCookie(int $expiresOffset = null, string $version = null)
  {
    if (is_null($version)) {
      $version = wp_get_theme()->get('Version');
    }

    $cookieName = apply_filters('themosis-criticalcss_cookieName', static::COOKIE_NAME);
    $version = apply_filters('themosis-criticalcss_cssVersion', $version);
    $expires = apply_filters('themosis-criticalcss_cookieExpires', time() + ($expiresOffset ?: static::EXPIRES_OFFSET));
    setcookie($cookieName, $version, $expires);
  }

  /**
   * Magically maps our calls to the the static methods
   *
   * @method isMobile
   * @method isTablet
   * @method isDesktop
   * @param string $method
   * @param array $args
   * @return mixed
   * @throws \BadMethodCallException
   */
  public function __call(string $method, array $args = [])
  {
    if (method_exists(get_class(), $method)) {
      return call_user_func_array([get_class(), $method], $args);
    }

    throw new BadMethodCallException(sprintf('%s method doesn\'t exist', $method));
  }
}
