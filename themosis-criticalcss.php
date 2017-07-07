<?php

/**
 * Plugin Name: Themosis Critical CSS
 * Plugin URI: https://keltiecochrane.com
 * Description: A plugin that generates critical CSS on demand.
 * Version: 0.2.5
 * Author: Daniel Gadd @ Keltie Cochrane Ltd.
 * Author URI: https://keltiecochrane.com
 * Text Domain: themosis-criticalcss.
 * Domain Path: /languages
 */

use Themosis\Facades\Action;
use KeltieCochrane\CriticalCss\CriticalCss;
use KeltieCochrane\CriticalCss\CriticalCssCli;
use KeltieCochrane\CriticalCss\CriticalCssServiceProvider;

Action::add('after_setup_theme', function () {
  // Register our provider
  app()->register(CriticalCssServiceProvider::class);

  // Create an instance of criticalcss
  new CriticalCss;

  // Register CLI commands
  if (app('criticalcss.browser')->isWpCli()) {
    WP_CLI::add_command('critical-css', CriticalCssCli::class);
  }
});
