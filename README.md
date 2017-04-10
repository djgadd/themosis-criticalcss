Themosis Critical CSS
=====================

A WordPress plugin for Themosis that implements
[kalfheim/critical-css](https://github.com/kalfheim/critical-css) to generate
critical CSS in WordPress. You'll need to have wp-cli setup to run it.

It will generate CSS for mobile, tablet and desktop devices, which will be served
as appropriate by browser sniffing. It will automatically modify style tags to
set them to `rel="preload"` and uses loadCSS to polyfill browsers that don't
support preloading.

This plugin will set a cookie to determine users that have/haven't been served
the CSS previously (to avoid inflated page loads for users that have a cached
copy of your CSS.) You may need to add a notice or override this functionality as
is appropriate to local laws regarding cookies.

Install
-------

From your projects base path run: -

`npm install critical --save`

Require the package in composer: -

`composer require keltiecochrane/themosis-criticalcss`

Copy the `config/criticalcss.php` file into your config folder.

Activate the plugin in WordPress.

Add the provder to your config/providers.php file: -

`KeltieCochrane\CriticalCss\CriticalCssServiceProvider::class,`

Use
---

You'll need to have wp-cli installed, to generate CSS run: -

`wp critical-css generate`

To clear generated CSS run: -

`wp critical-css clear`

Support
-------
This plugin is provided as is, though we'll endeavour to help where we can.

Contributing
------------
Any contributions would be encouraged and much appreciated, you can contribute by: -

* Reporting bugs
* Suggesting features
* Sending pull requests
