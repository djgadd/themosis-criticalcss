<?php

namespace KeltieCochrane\CriticalCss;

use WP_CLI;
use WP_Query;
use Throwable;
use WP_CLI_Command;
use Alfheim\CriticalCss\HtmlFetchers\HtmlFetchingException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CriticalCssCli extends WP_CLI_Command
{
  /**
   * Generates critical css
   *
   * ## OPTIONS
   *
   * [--uri=<uri>]
   * : Optionally specify the URI to generate CSS for
   *
   * ## EXAMPLES
   *
   * wp critical-css generate
   * wp critical-css generate --uri=http://domain.tld/
   *
   * @subcommand generate
   */
  public function generate($args = [], $assocArgs = [])
  {
    try {
      $this->log('info', 'Generating critical CSS...');

      // If we've been asked to generate for a specific URI
      if (array_key_exists('uri', $assocArgs)) {
        // Validate the storage directory
        app('criticalcss.storage')->validateStoragePath();

        // Sanitize the uri
        if ($uri = $this->sanitizeUri($assocArgs['uri'])) {
          $this->clearCss($uri);
          $this->generateCss(app('config')->get('criticalcss.viewports'), $uri);
        }
        else {
          $this->log('error', '[%s] is not a valid URI for this website (%s)', $uri, WP_HOME);
        }
      }

      // Otherwise generate everything
      else {
        // Clear everything
        $this->clearCss();

        // Validate the storage directory
        app('criticalcss.storage')->validateStoragePath();

        // Get all of the URIs and process them
        foreach ($this->getUris() as $uri) {
          $this->generateCss(app('config')->get('criticalcss.viewports'), $uri);
        }
      }

      $this->log('success', 'Successfully generated critical CSS');
      return;
    }
    catch (Throwable $e) {
      $this->log('error', $e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
    }
  }

  /**
   * Clears critical css
   *
   * @subcommand clear
   */
  public function clear()
  {
    try {
      $this->log('info', 'Clearing all critical CSS');
      $this->clearCss();
      $this->log('success', 'Cleared all critical CSS');
      return;
    }
    catch (Throwable $e) {
      $this->log('error', $e->getMessage(), ['line' => $e->getLine(), 'file' => $e->getFile()]);
    }
  }

  /**
   * Generates critical CSS for a given URI
   *
   * @param array $viewports
   * @param string $uri
   * @return void
   */
  protected function generateCss(array $viewports, string $uri)
  {
    try {
      $this->validateViewports(app('config')->get('criticalcss.viewports'));

      foreach ($viewports as $alias => $viewport) {
        $this->log('info', sprintf('Processing [%s] (%s)', $uri, $alias));
        app('criticalcss.cssgenerator')->setOptions($viewport['width'], $viewport['height']);
        app('criticalcss.cssgenerator')->generate($uri, "{$alias}:{$uri}");
      }
    }
    catch (HtmlFetchingException $e) {
      $this->log('warning', sprintf('[%s] HtmlFetchingException: %s', $uri, $e->getMessage()));
    }
    catch (NotFoundHttpException $e) {
      $this->log('warning', sprintf('[%s] NotFoundHttpException: %s', $uri, $e->getMessage()));
      $this->log('warning', $e->getMessage());
    }
  }

  /**
   * Clears CSS for a given URI, if none specified clears everything
   *
   * @param string|null $uri
   * @return bool
   */
  protected function clearCss(string $uri = null)
  {
    return app('criticalcss.storage')->clearCss($uri);
  }

  /**
   * Logs a message to WP_CLI console and our error logs
   *
   * @param string $type
   * @param string $msg
   * @param array $context
   * @return void
   */
  protected function log(string $type, string $msg, array $context = [])
  {
    switch ($type) {
      case 'debug':
        app('log')->debug($msg, $context);
        break;

      case 'info':
        app('log')->info($msg, $context);
        WP_CLI::line($msg);
        break;

      case 'warning':
        app('log')->warning($msg, $context);
        WP_CLI::warning($msg);
        break;

      case 'error':
        app('log')->error($msg, $context);
        WP_CLI::error($msg);
        break;

      case 'success':
        app('log')->info($msg, $context);
        WP_CLI::success($msg);
        break;

      default:
        throw new \Exception(sprintf('[%s] is not a valid type of log method'));
    }
  }

  /**
   * Ensures we've got the viewports we need in the config
   *
   * @param array $viewports
   * @return void
   */
  protected function validateViewports(array $viewports)
  {
    // Check we've got "mobile", "tablet", and "desktop"
    if (count(array_intersect(array_keys($viewports), ['mobile', 'tablet', 'desktop'])) < 3) {
      $this->log('error', 'Check your config, missing viewports. You must define "mobile", "tablet" and "desktop"');
    }

    // Check we have widths and heights
    foreach ($viewports as $alias => $viewport) {
      if (count(array_intersect(array_keys($viewport), ['width', 'height'])) < 2) {
        $this->log('error', sprintf('Check your config, [%s] is missing "width" or "height"', $alias));
      }
    }
  }

  /**
   * Validates a URI to make sure it's for this website then returns a sanitized
   * version.
   *
   * @param string $url
   * @return mixed
   */
  protected function sanitizeUri(string $uri)
  {
    // Check to make sure that the URI is for this site
    if (substr($uri, 0, strlen(WP_HOME)) === WP_HOME) {
      return trailingslashit($uri);
    }

    return false;
  }

  /**
  * Returns an array of all Uris on the site
  * @return array
  */
  protected function getUris() : array
  {
    $uris = array_merge([get_home_url()], $this->getPostUris(), $this->getArchiveUris(), $this->getTermUris());

    // Remove duplicates
    $uris = array_unique(array_map(function ($uri) {
      return trailingslashit($uri);
    }, $uris));

    return $uris;
  }

  /**
  * Returns an array of all post Uris on the site
  *
  * @return array
  */
  protected function getPostUris() : array
  {
    return array_map(function ($post) {
      return get_permalink($post);
    }, (new WP_Query([
      'posts_per_page' => -1,
      'post_type' => 'any',
      'post_status' => 'publish',
    ]))->posts);
  }

  /**
   * Returns an array of all archive Uris on the site
   *
   * @return array
   */
  protected function getArchiveUris() : array
  {
    // Get an array of public post types
    $types = get_post_types([
      'public' => true,
    ]);

    // Remove the attachment type
    unset($types['attachment']);

    $archives = array_map(function ($type) {
      return get_post_type_archive_link($type);
    }, $types);

    // Filter out the ones without archives
    return array_filter($archives);
  }

  /**
  * Returns an array of all term Uris on the site
  *
  * @return array
  */
  protected function getTermUris() : array
  {
    // Get an array of public taxonomies
    $taxonomies = get_taxonomies([
      'public' => true,
    ]);

    // Return the Uris of all the public terms we have
    return array_map(function ($term) {
      return get_term_link($term);
    }, get_terms([
      'taxonomy' => $taxonomies,
    ]));
  }
}
