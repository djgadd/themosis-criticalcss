<?php

namespace KeltieCochrane\CriticalCss;

use WP_CLI;
use WP_Query;
use Throwable;
use WP_CLI_Command;
use Themosis\Facades\Config;
use Alfheim\CriticalCss\HtmlFetchers\HtmlFetchingException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CriticalCssCli extends WP_CLI_Command
{
  /**
   * Generates critical css
   * @subcommand generate
   */
  public function generate ()
  {
    $this->clearCss();

    WP_CLI::line('Generating critical CSS...');

    try {
      container('criticalcss.storage')->validateStoragePath();
      $viewports = Config::get('criticalcss.viewports');
      $cssGenerator = container('criticalcss.cssgenerator');
      $urls = $this->getUrls();

      foreach ($urls as $url) {
        try {
          foreach ($viewports as $alias => $viewport) {
            WP_CLI::line(sprintf('Processing [%s] (%s)', $url, $alias));
            $cssGenerator->setOptions($viewport['width'], $viewport['height']);
            $cssGenerator->generate($url, "{$alias}:{$url}");
          }
        }
        catch (HtmlFetchingException $e) {
          WP_CLI::warning($e->getMessage());
        }
        catch (NotFoundHttpException $e) {
          WP_CLI::warning(sprintf('404 not found [%s]', $url));
        }
      }
    }
    catch (Throwable $e) {
      var_dump([
        'l' => $e->getLine(),
        'f' => $e->getFile(),
      ]);
      WP_CLI::error($e->getMessage());
      return;
    }
  }

  /**
   * Clears critical css
   * @subcommand clear
   */
  public function clearCss ()
  {
    try {
      WP_CLI::line('Clearing critical CSS...');
      container('criticalcss.storage')->clearCss();
    }
    catch (Throwable $e) {
      WP_CLI::error($e->getMessage());
      return;
    }
  }

  /**
  * Returns an array of all URLs on the site
  * @return  array
  */
  protected function getUrls () : array
  {
    $urls = array_merge([get_home_url()], $this->getPostUrls(), $this->getArchiveUrls(), $this->getTermUrls());

    // Remove duplicates
    $urls = array_unique(array_map(function ($url) {
      return trailingslashit($url);
    }, $urls));

    return $urls;
  }

  /**
  * Returns an array of all post URLs on the site
  * @return  array
  */
  protected function getPostUrls () : array
  {
    return array_map(function ($post) {
      return get_permalink($post);
    }, (new WP_Query([
      'posts_per_page' => -1,
      'post_type' => 'any',
      'post_status' => 'publish',
    ]))->posts);
  }

  protected function getArchiveUrls () : array
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
  * Returns an array of all term URLs on the site
  * @return  array
  */
  protected function getTermUrls () : array
  {
    // Get an array of public taxonomies
    $taxonomies = get_taxonomies([
      'public' => true,
    ]);

    // Return the URLs of all the public terms we have
    return array_map(function ($term) {
      return get_term_link($term);
    }, get_terms([
      'taxonomy' => $taxonomies,
    ]));
  }
}

WP_CLI::add_command('critical-css', CriticalCssCli::class);
