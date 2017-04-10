<?php
/**
 * Some of the following configuration options are the same as the ones you'll
 * find in the Critical npm package.
 *
 * @see https://github.com/addyosmani/critical  For more info.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | CSS file(s)
    |--------------------------------------------------------------------------
    | CSS files to extract from. (The application's main CSS file(s).)
    |
    | The file is relative to the theme/dist folder
    */
    'css' => [
      'css/main.css',
    ],

    'viewports' => [
      // iPhone 6 Plus
      'mobile' => [
        'width' => 614,
        'height' => 736,
      ],
      // iPad
      'tablet' => [
        'width' => 768,
        'height' => 1024,
      ],
      // iPad Pro
      'dekstop' => [
        'width' => 1024,
        'height' => 1366,
      ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Rules
    |--------------------------------------------------------------------------
    | CSS rules to ignore. See filter-css for usage examples. You will also
    | find some commented-out examples below.
    | @see https://github.com/bezoerb/filter-css
    */
    'ignore' => [
        // Removes @font-face blocks
        // '@font-face',
        // Removes CSS selector
        // '.selector',
        // JS Regex, matches url(..) rules
        // '/url(/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Path
    |--------------------------------------------------------------------------
    |
    | The directory which the generated critical-path CSS is stored.
    */
    'storage' => 'critical-css',

    /*
    |--------------------------------------------------------------------------
    | Pretend Mode
    |--------------------------------------------------------------------------
    | When this option is enabled, no critical-path will be inlined. This
    | is very useful during development, as you don't want the inlined styles
    | interfering after you've updated your main stylesheets.
    |
    | Remember to run `php artisan view:clear` after re-disabling this.
    */
    'pretend' => (getenv('CRITICALCSS_PRETEND') === 'true' ? true : false),

    /*
    |--------------------------------------------------------------------------
    | Critical Path
    |--------------------------------------------------------------------------
    | Path to the Critical executable. If you have installed Critical in the
    | project only, the default should be used. However, if Critical is
    | installed globally, you may simply use 'critical'.
    */
    'critical_bin' => THEMOSIS_STORAGE.DS.'..'.DS.'node_modules/.bin/critical',

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    | Sets a maximum timeout, in milliseconds, for the CSS generation of a route.
    | This parameter is passed to the Critical executable.
    | Default value is 30000.
    */
    'timeout' => 30000,
];
