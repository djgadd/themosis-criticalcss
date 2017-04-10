<?php

namespace KeltieCochrane\CriticalCss\HtmlFetchers;

use Closure;
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
  protected $app = null;

  /**
   * Create a new instance.
   * @return void
   */
  public function __construct()
  {
    $this->app = container();
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($uri)
  {
    $response = $this->call($uri);

    if (!$response->isOk()) {
      throw new HtmlFetchingException(sprintf('Invalid response from URI [%s].', $uri));
    }

    return $this->stripCss($response->getContent());
  }

  /**
   * Remove any existing inlined critical-path CSS that has been generated
   * previously. Old '<style>' tags should be tagged with a `data-inline`
   * attribute.
   * @param  string $html
   * @return string
   */
  protected function stripCss($html)
  {
    return preg_replace('/\<style data-inlined\>.*\<\/style\>/s', '', $html);
  }

  /**
   * Call the given URI and return a Response.
   * @param  string $uri
   * @return \Illuminate\Http\Response
   */
  protected function call($uri)
  {
    // Create a request
    $request = Request::create($uri, 'GET');

    // Override some globals from our request
    $_SERVER = array_replace($_SERVER, $request->server->all());
    $_GET = array_replace($_GET, $request->query->all());

    // 'Reboot' WordPress' Http stack for the request
    // $wp->main();
    wp();

    return $this->app['router']->dispatch($request);
  }
}
