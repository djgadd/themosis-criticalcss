<?php

namespace KeltieCochrane\CriticalCss;

use WP_Post;
use Exception;
use Themosis\Facades\Action;
use Themosis\Facades\Filter;

class CriticalCss
{
  /**
   * @var \KeltieCochrane\CriticalCss\Browser
   */
  protected $browser;

  /**
   * Setup our filters and define some bits and pieces
   *
   * @return void
   */
  public function __construct()
  {
    // We only want to do stuff if we're not pretending
    if (!app('config')->get('criticalcss.pretend', false)) {
      $this->browser = app('criticalcss.browser');

      if ($this->hasCss()) {
        Filter::add('style_loader_tag', [$this, 'filter_modifyStyleTags'], 10, 1);
        Action::add('wp_head', [$this, 'action_outputLoadCssToHead']);
        Action::add('wp_head', [$this, 'action_outputCriticalCss']);
      }

      $this->registerUpdateActions();
    }
  }

  /**
   * Registers the action hooks that are used to generate critical CSS at run
   * time
   *
   * @return void
   */
  protected function registerUpdateActions()
  {
    // Post updates
    Action::add('clean_post_cache', [$this, 'action_updatePost'], 15, 1);

    // Term updates
    Action::add('clean_term_cache', [$this, 'action_updateTerms'], 10, 3);

    // Comment updates
    Action::add('clean_comment_cache', [$this, 'action_updateComments'], 10, 1);
    Action::add('comment_post', [$this, 'action_updateComments'], 10, 1);
    Action::add('wp_set_comment_status', [$this, 'action_updateComments'], 10, 1);
    Action::add('edit_comment', [$this, 'action_updateComments'], 10, 1);

    // Globals
    Action::add('widget_update_callback', [$this, 'action_flushAll'], 10);
    Action::add('customize_save_after', [$this, 'action_flushAll'], 10);
    Action::add('switch_theme', [$this, 'action_flushAll'], 10);
    Action::add('wp_update_nav_menu', [$this, 'action_flushAll'], 10);
  }

  /**
   * Check to see if we have crtiical CSS to use
   *
   * @return bool
   */
  protected function hasCss() : bool
  {
    if ($this->browser->isMobile()) {
      return app('criticalcss.storage')->hasCriticalCss('mobile:'.$this->getCurrentUrl());
    }
    elseif ($this->browser->isTablet()) {
      return app('criticalcss.storage')->hasCriticalCss('tablet:'.$this->getCurrentUrl());
    }
    else {
      return app('criticalcss.storage')->hasCriticalCss('desktop:'.$this->getCurrentUrl());
    }
  }

  /**
   * Modifies style tags to make them preload without blocking the page. We don't
   * modify them if the cookie is a hit as we should already have a cached copy.
   *
   * @param string $html
   * @return string
   */
  public function filter_modifyStyleTags(string $html) : string
  {
    if ($this->browser->isCookieMiss()) {
      app('log')->debug('Modifying style tags');

      $return = preg_replace("/rel='stylesheet'/i" , 'rel=\'preload\' onload=\'this.rel="stylesheet"\' as=\'style\'', $html);

      // Add a the original tag in a noscript tag for compatibility
      $return .= '<noscript>' . $html . '</noscript>';

      return $return;
    }

    return $html;
  }

  /**
   * Outputs loadCSS JS into the head
   *
   * @return void
   */
  public function action_outputLoadCssToHead()
  {
    $useLoadCss = apply_filters('themosis-criticalcss_useLoadCSS', $this->browser->isCookieMiss());

    // Don't use a CDN because it's just another render blocker
    if (!$this->browser->isWpCli() && $useLoadCss) {
      app('log')->debug('Outputting LoadCSS');
      require_once dirname(__DIR__).DS.'loadCSS.php';
    }
  }

  /**
   * Outputs critical css into the head
   *
   * @return void
   */
  public function action_outputCriticalCss()
  {
    if (!$this->browser->isWpCli() && $this->browser->isCookieMiss()) {
      if ($this->browser->isMobile()) {
        app('log')->debug('Outputting mobile critical CSS');
        echo app('criticalcss.storage')->css('mobile:'.$this->getCurrentUrl());
      }
      elseif ($this->browser->isTablet()) {
        app('log')->debug('Outputting tablet critical CSS');
        echo app('criticalcss.storage')->css('tablet:'.$this->getCurrentUrl());
      }
      else {
        app('log')->debug('Outputting desktop critical CSS');
        echo app('criticalcss.storage')->css('desktop:'.$this->getCurrentUrl());
      }

      $this->browser->setCookie();
    }
  }

  /**
   * Triggered when a change happens
   *
   * @param null|string $uri
   * @return void
   */
  public function regenerate(string $uri = null)
  {
    // Need to get absolute path to wp-cli.phar
    $wp = dirname(themosis_path('storage')).DIRECTORY_SEPARATOR.'wp-cli.phar';
    $cmd = sprintf('php %s critical-css generate', $wp);

    if (!is_null($uri)) {
      $cmd = sprintf('%s --uri=%s', $cmd, $uri);
    }

    app('log')->debug(sprintf('Running: %s', $cmd));

    if (windows_os()) {
      pclose(sprintf('start /B %s', $cmd), 'r');
    }
    else {
      shell_exec(sprintf('%s > /dev/null 2>/dev/null &', $cmd));
    }
  }

  /**
   * Returns the current URL
   *
   * @global $wp
   * @return string
   */
  public function getCurrentUrl() : string
  {
    global $wp;
    return trailingslashit(home_url(add_query_arg(array(),$wp->request)));
  }

  /**
   * Regenerates CSS for a post when it's cache is cleared
   *
   * @param int $id
   * @return void
   */
  public function action_updatePost(int $id)
  {
    $post = get_post($id);

    // Assume the post has been deleted and do nothing
    if (is_null($post)) {
      return;
    }

    // We only generate critical CSS on published posts
    if ($post->post_status === 'publish') {
      app('log')->debug('Generating CSS for post');
      $this->regenerate(get_permalink($id));
    }
  }

  /**
   * Regenerates CSS for a term when it's cache is cleared
   *
   * @param array $ids
   * @param string $taxonomy
   * @param bool $clean
   * @return void
   */
  public function action_updateTerms(array $ids, string $taxonomy, bool $clean)
  {
    // Are we regenerating for all terms in taxonomy
    if ($clean) {
      app('log')->debug('Generating CSS for all terms in taxonomy');

      foreach (get_terms(compact('taxonomy')) as $term) {
        $this->regenerate(get_term_link($term, $taxonomy));
      }
    }

    // We're just cleaning this one term
    else {
      app('log')->debug('Generating CSS for term');

      foreach ($ids as $id) {
        if (is_wp_error($uri = get_term_link(intval($id), $taxonomy))) {
          throw new Exception($uri->get_error_message(), $uri->get_error_code());
        }

        $this->regenerate($uri);
      }
    }
  }

  /**
   * Regenerates CSS for a post when a comment changes
   *
   * @param int $id
   * @return void
   */
  public function action_updateComments(int $id)
  {
    app('log')->debug('Generating CSS for comment');
    $this->action_updatePost(get_comment($id)->comment_post_ID);
  }

  /**
   * Regenerates all critical CSS
   *
   * @return void
   */
  public function action_flushAll()
  {
    app('log')->debug('Flushing and regenerating all critical CSS');
    $this->regenerate();
  }
}
