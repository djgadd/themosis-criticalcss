<?php

namespace KeltieCochrane\CriticalCss;

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
  protected $browser;

  /**
   * @var \WP_Theme
   */
  protected $WP_Theme;

  /**
   * Beep boop
   *
   * @return void
   */
  public function __construct()
  {
    $this->browser = new MobileDetect;
    $this->WP_Theme = wp_get_theme();
  }

  /**
   * Determines if the browser is a mobile device
   *
   * @return bool
   */
  public function isMobile() : bool
  {
    $isMobile = $this->browser->isMobile() && !$this->browser->isTablet();
    return apply_filters('themosis-criticalcss_isMobile', $isMobile);
  }

  /**
   * Determines if the browser is a tablet device
   *
   * @return bool
   */
  public function isTablet() : bool
  {
    $isTablet = $this->browser->isTablet();
    return apply_filters('themosis-criticalcss_isTablet', $isTablet);
  }

  /**
   * Determines if the browser is a desktop device
   *
   * @return bool
   */
  public function isDesktop() : bool
  {
    $isDesktop = !$this->browser->isMobile();
    return apply_filters('themosis-criticalcss_isDesktop', $isDesktop);
  }

  /**
   * Determines if this is a request inside WpCli
   *
   * @return bool
   */
  public function isWpCli() : bool
  {
    return defined('WP_CLI') && WP_CLI;
  }

  /**
   * Determines if the cookie hit
   *
   * @return bool
   */
  public function isCookieHit() : bool
  {
    $cookieExists = isset($_COOKIE['themosis-criticalcss']);

    if ($cookieExists && $_COOKIE[static::COOKIE_NAME] === $this->WP_Theme->get('Version')) {
      return apply_filters('themosis-criticalcss_cookieIsHit', true);
    }
    else {
      return apply_filters('themosis-criticalcss_cookieIsHit', false);
    }
  }

  /**
   * Determines if the cookie missed
   *
   * @return bool
   */
  public function isCookieMiss() : bool
  {
    return !$this->isCookieHit();
  }

  /**
   * Sets the cookie to the current version of the theme
   *
   * @param int $expiresOffset
   * @return void
   */
  public function setCookie(int $expiresOffset = null)
  {
    $cookieName = apply_filters('themosis-criticalcss_cookieName', static::COOKIE_NAME);
    $version = apply_filters('themosis-criticalcss_cssVersion', $this->WP_Theme->get('Version'));
    $expires = apply_filters('themosis-criticalcss_cookieExpires', time() + ($expiresOffset ?: static::EXPIRES_OFFSET));
    setcookie($cookieName, $version, $expires);
  }
}
