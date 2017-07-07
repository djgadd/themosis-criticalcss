<?php

namespace KeltieCochrane\CriticalCss\HtmlFetchers;

use Closure;
use WP_Styles;
use Themosis\Facades\Action;
use Themosis\Foundation\Request;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Alfheim\CriticalCss\HtmlFetchers\HtmlFetcherInterface;
use Alfheim\CriticalCss\HtmlFetchers\HtmlFetchingException;

/**
 * This implementation fetches HTML for a given URI by mocking a Request and
 * letting a new instance of the Laravel Application handle it.
 */
class ThemosisHtmlFetcher implements HtmlFetcherInterface
{
  /**
  * @var \Illuminate\Contracts\Foundation\Application
  */
  protected $app;

  /**
   * @var array
   */
  protected $cache;

  /**
   * Create a new instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->app = app();
    $this->cache = [];
  }

  /**
   * @global $wp_styles
   * {@inheritdoc}
   */
  public function fetch($uri)
  {
    global $wp_styles;

    if (!array_key_exists($uri, $this->cache)) {
      $cssFiles = [];
      $response = $this->call($uri);

      if (!$response->isOk()) {
        app('log')->debug(sprintf('Invalid response (%s) for [%s]', $response->getStatusCode(), $uri));
        throw new HtmlFetchingException(sprintf('Invalid response from URI [%s].', $uri));
      }

      // Get css files that have been queued for the page
      if (is_a($wp_styles, WP_Styles::class)) {
        $cssFiles = array_map(function ($handle) use ($wp_styles) {
          $dirtyPath = str_replace(WP_HOME.'/content', WP_CONTENT_DIR, $wp_styles->registered[$handle]->src);

          // Do we really need to support windows? idk.
          return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirtyPath);
        }, $wp_styles->queue);
      }

      // Tell the generator what CSS files to use
      app('criticalcss.cssgenerator')->setCssFiles($cssFiles);

      // I don't think we actually need to do this because we shouldn't be outputting
      // styles when running in the console?
      $this->cache[$uri] = $response->getContent());
    }

    return $this->cache[$uri];
  }

  /**
   * Remove any existing inlined critical-path CSS that has been generated
   * previously. Old '<style>' tags should be tagged with a `data-inline`
   * attribute.
   *
   * @param  string $html
   * @return string
   */
  protected function stripCss($html)
  {
    return preg_replace('/\<style data-inlined\>.*\<\/style\>/s', '', $html);
  }

  /**
   * Call the given URI and return a Response.
   *
   * @param  string $uri
   * @return \Illuminate\Http\Response
   */
  protected function call($uri)
  {
    try {
      // Create a request
      $request = Request::create($uri, 'GET');

      // Override some globals from our request
      $_SERVER = array_replace($_SERVER, $request->server->all());
      $_GET = array_replace($_GET, $request->query->all());

      // "Reboot" WordPress' "Http stack" for the request
      wp();

      return $this->app['router']->dispatch($request);
    }
    // Log it then throw it again
    catch (\Throwable $e) {
      app('log')->error(sprintf('Error occurred when trying to call [%s]: %s', $uri, $e->getMessage()));
      throw $e;
    }
  }
}
