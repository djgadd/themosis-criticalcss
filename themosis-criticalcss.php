<?php

/**
 * Plugin Name: Themosis Critical CSS
 * Plugin URI: https://keltiecochrane.com
 * Description: A plugin that generates critical CSS on demand.
 * Version: 0.1.3
 * Author: Daniel Gadd @ Keltie Cochrane Ltd.
 * Author URI: https://keltiecochrane.com
 * Text Domain: themosis-criticalcss.
 * Domain Path: /languages
 */

use Detection\MobileDetect;
use Themosis\Facades\Action;
use Themosis\Facades\Filter;

defined('DS') ? DS : define('DS', DIRECTORY_SEPARATOR);

// TODO: don't output critical css in plugin, need to hook into batcache to try auto inject it

// Load the WP CLI
if (defined('WP_CLI') && WP_CLI) {
  require_once(__DIR__.DS.'src'.DS.'KeltieCochrane'.DS.'CriticalCss'.DS.'CriticalCssCli.php');
}

class ThemosisCriticalCss
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
  protected static $browser;

  /**
   * @var \WP_Theme
   */
  protected $WP_Theme;

  /**
   * Setup our filters and define some bits and pieces
   * @return void
   */
  public function __construct()
  {
    $this->WP_Theme = wp_get_theme();
    Filter::add('style_loader_tag', [$this, 'filter_modifyStyleTags'], 10, 1);
    Action::add('wp_head', [$this, 'action_outputLoadCssToHead']);
    Action::add('wp_head', [$this, 'action_outputLoadCssToHead']);
  }

  /**
   * Modifies style tags to make them preload without blocking the page. We don't
   * modify them if the cookie is a hit as we should already have a cached copy.
   * @method filter_modifyStyleTags
   * @param string $html
   * @return string
   */
  public function filter_modifyStyleTags(string $html) : string
  {
    if (!$this->cookieIsHit()) {
      $return = preg_replace("/rel='stylesheet'/i" , 'rel=\'preload\' onload=\'this.rel="stylesheet"\' as=\'style\'', $html);

      // Add a the original tag in a noscript tag for compatibility
      $return .= '<noscript>' . $html . '</noscript>';

      return $return;
    }

    return $html;
  }

  /**
   * Outputs loadCSS JS into the head
   * @method action_outputLoadCssToHead
   * @return void
   */
  public function action_outputLoadCssToHead()
  {
    // Use loadCSS polyfill, apply a filter to allow this to be disabled
    if (apply_filters('themosis-criticalcss_useLoadCSS', true)) {
      require_once __DIR__.DS.'loadCSS.php';
    }
  }

  /**
   * Outputs critical css into the head
   * @method action_outputCriticalCss
   * @return void
   */
  public function action_outputCriticalCss()
  {
    global $wp;
    $console = defined('WP_CLI') && WP_CLI;

    if (!$console && !$this->cookieIsHit()) {
      static::setupBrowser();

      if (static::isMobile()) {
        echo container('criticalcss.storage')->css('mobile:'.$url);
      }
      elseif (static::isTablet()) {
        echo container('criticalcss.storage')->css('tablet:'.$url);
      }
      else {
        echo container('criticalcss.storage')->css('desktop:'.$url);
      }

      $this->setCookie();
    }
  }

  /**
   * Determines if the cookie matches our theme version
   * @method cookieIsHit
   * @return bool
   */
  protected function cookieIsHit() : bool
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
   * Sets the cookie to the current version of the theme
   * @param int $expiresOffset
   * @return void
   */
  protected function setCookie(int $expiresOffset = null)
  {
    $expires = apply_filters('themosis-criticalcss_cookieExpires', time() + ($expiresOffset ?: static::EXPIRES_OFFSET));
    setcookie(static::COOKIE_NAME, $this->WP_Theme->get('Version'), $expires);
  }

  /**
   * Sets up a static instance of \Detect\MobileDetect
   * @return void
   */
  protected static function getBrowser()
  {
    if (empty(static::$browser)) {
      static::$browser = new MobileDetect;
    }
  }

  /**
   * Determines if the browser is a mobile device
   * @return bool
   */
  public static function isMobile() : bool
  {
    static::setupBrowser();
    return static::$browser->isMobile() && !static::$browser->isTablet();
  }

  /**
   * Determines if the browser is a tablet device
   * @return bool
   */
  public static function isTablet() : bool
  {
    static::setupBrowser();
    return static::$browser->isTablet();
  }

  /**
   * Determines if the browser is a desktop device
   * @return bool
   */
  public static function isDesktop() : bool
  {
    static::setupBrowser();
    return !static::$browser->isMobile();
  }
}

new ThemosisCriticalCss;
