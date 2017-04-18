<?php

namespace KeltieCochrane\CriticalCss;

use Alfheim\CriticalCss\CssGenerators\CriticalGenerator as BaseGenerator;

class CriticalGenerator extends BaseGenerator
{
  /**
   * Allows us to use $wp_styles to figure out which style sheets we need to
   * generate CSS from dynamically.
   *
   * @param array $css
   * @return void
   */
  public function setCssFiles(array $css)
  {
    $this->css = $css;
  }
}
