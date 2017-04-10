<?php

namespace KeltieCochrane\CriticalCss;

use Themosis\Facades\Config;
use Alfheim\CriticalCss\BladeUtils;
use Themosis\Foundation\ServiceProvider;
use KeltieCochrane\CriticalCss\Storage\ThemosisStorage;
use KeltieCochrane\CriticalCss\HtmlFetchers\ThemosisHtmlFetcher;

use Alfheim\CriticalCss\CssGenerators\CriticalGenerator;

class CriticalCssServiceProvider extends ServiceProvider
{
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
        Config::get('criticalcss.storage'),
        container('files'),
        Config::get('criticalcss.pretend')
      );
    });

    $this->app->singleton('criticalcss.htmlfetcher', function ($app) {
      return new ThemosisHtmlFetcher();
    });

    $this->app->singleton('criticalcss.cssgenerator', function ($app) {
      $generator = new CriticalGenerator(
        array_map(function ($filename) {
          return themosis_path('theme').'dist'.DS.$filename;
        }, Config::get('criticalcss.css')),
        $app->make('criticalcss.htmlfetcher'),
        $app->make('criticalcss.storage')
      );

      $generator->setCriticalBin(
        Config::get('criticalcss.critical_bin')
      );

      $generator->setOptions(
        Config::get('criticalcss.width'),
        Config::get('criticalcss.height'),
        Config::get('criticalcss.ignore'),
        Config::get('criticalcss.timeout', 30000)
      );

      return $generator;
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
    ];
  }
}
