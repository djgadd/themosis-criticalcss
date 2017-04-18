<?php

namespace KeltieCochrane\CriticalCss;

use Alfheim\CriticalCss\BladeUtils;
use Themosis\Foundation\ServiceProvider;
use KeltieCochrane\CriticalCss\Storage\ThemosisStorage;
use KeltieCochrane\CriticalCss\HtmlFetchers\ThemosisHtmlFetcher;

class CriticalCssServiceProvider extends ServiceProvider
{
  /**
   * Defer loading unless we need it, saves us a little bit of overhead if the
   * current request isn't trying to log anything.
   *
   * @var bool
   */
  protected $defer = true;

  /**
   * {@inheritdoc}
   */
  public function register()
  {
    $this->registerAppBindings();
  }

  /**
   * Register Application bindings.
   *
   * @return void
   */
  protected function registerAppBindings()
  {
    $this->app->singleton('criticalcss.storage', function ($app) {
      return new ThemosisStorage(
        $app['config']->get('criticalcss.storage'),
        app('files'),
        $app['config']->get('criticalcss.pretend')
      );
    });

    $this->app->singleton('criticalcss.htmlfetcher', function ($app) {
      return new ThemosisHtmlFetcher;
    });

    $this->app->singleton('criticalcss.cssgenerator', function ($app) {
      // We're passing an empty array for the CSS files because we're dynamically
      // setting files based on $wp_styles queue. This means we can pick up
      // plugins too, which isn't a use case for KC but may be for others.
      $generator = new CriticalGenerator(
        [],
        $app->make('criticalcss.htmlfetcher'),
        $app->make('criticalcss.storage')
      );

      $generator->setCriticalBin(
        $app['config']->get('criticalcss.critical_bin')
      );

      $generator->setOptions(
        $app['config']->get('criticalcss.width'),
        $app['config']->get('criticalcss.height'),
        $app['config']->get('criticalcss.ignore'),
        $app['config']->get('criticalcss.timeout', 30000)
      );

      return $generator;
    });

    $this->app->singleton('criticalcss.browser', function ($app) {
      return new Browser;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function provides()
  {
    return [
      'criticalcss.storage',
      'criticalcss.htmlfetcher',
      'criticalcss.cssgenerator',
      'criticalcss.browser',
    ];
  }
}
