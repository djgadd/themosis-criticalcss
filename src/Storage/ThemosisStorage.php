<?php
namespace KeltieCochrane\CriticalCss\Storage;

use Illuminate\Filesystem\Filesystem;
use Alfheim\CriticalCss\Storage\StorageInterface;
use Alfheim\CriticalCss\Storage\CssWriteException;

/**
 * Read and write to the filesystem using the FilesystemManager in Laravel.
 */
class ThemosisStorage implements StorageInterface
{
  /**
   * @var array
   */
  protected static $paths = [];

  /**
   * @var string
   */
  protected $storage;

  /**
   * @var \Illuminate\Filesystem\Filesystem
   */
  protected $files;

  /**
   * @var bool
   */
  protected $pretend;

  /**
   * Create a new instance.
   *
   * @param string $storage
   * @param \Illuminate\Filesystem\Filesystem $files
   * @param bool $pretend
   * @return void
   */
  public function __construct($storage, Filesystem $files, $pretend)
  {
    $this->storage = themosis_path('storage') . $storage;
    $this->files = $files;
    $this->pretend = $pretend;
  }

  /**
   * Validate that the storage directory exists. If it does not, create it.
   *
   * @return bool
   */
  public function validateStoragePath()
  {
    if (!$this->files->exists($this->storage)) {
      return $this->files->makeDirectory($this->storage);
    }

    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function readCss($uri)
  {
    if (!$this->hasCriticalCss($uri)) {
      app('log')->warning(sprintf('Missing critical CSS [%s]', $uri));
      return sprintf(
        '/* Critical-path CSS for URI [%s] not found at [%s]. '.
        'Check the config and run `wp critical-css generate`. */',
        $uri,
        $this->getCssPath($uri)
      );
    }

    return $this->files->get($this->getCssPath($uri));
  }

  /**
   * Wrap the critical-path CSS inside a '<style>' HTML element and return
   * the HTML.
   *
   * @param string $uri
   * @return string
   */
  public function css($uri)
  {
    if ($this->pretend) {
      return '';
    }

    return '<style data-inlined>'.$this->readCss($uri).'</style>';
  }

  /**
   * {@inheritdoc}
   */
  public function writeCss($uri, $css)
  {
    if (!$this->files->put($this->getCssPath($uri), $css)) {
      throw new CssWriteException(sprintf(
        'Unable to write the critical-path CSS for the URI [%s] to [%s].',
        $uri,
        $this->getCssPath($uri)
      ));
    }

    return true;
  }

  /**
   * Clear the storage.
   *
   * @return bool
   */
  public function clearCss($uri = null)
  {
    if ($uri) {
      return $this->files->delete($this->getCssPath($uri));
    }

    return $this->files->deleteDirectory($this->storage, true);
  }

  /**
   * Return the css path for the url
   *
   * @param string $uri
   * @return string
   */
  protected function getCssPath($uri) : string
  {
    $key = md5($uri);

    if (!array_key_exists($key, static::$paths)) {
      static::$paths[$key] = sprintf('%s/%s.css', $this->storage, md5($uri));
    }

    return static::$paths[$key];
  }

  /**
   * Determines whether the given URI has critical CSS
   *
   * @param string $uri
   * @return string
   */
  public function hasCriticalCss(string $uri) : bool
  {
    foreach (array_keys(app('config')->get('criticalcss.viewports')) as $alias) {
      if (!$this->files->exists($this->getCssPath(sprintf('%s:%s', $alias, $uri)))) {
        return false;
      }
    }

    return true;
  }
}
