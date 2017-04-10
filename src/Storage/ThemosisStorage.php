<?php
namespace KeltieCochrane\CriticalCss\Storage;

use Illuminate\Filesystem\Filesystem;
use Alfheim\CriticalCss\Storage\StorageInterface;

/**
 * Read and write to the filesystem using the FilesystemManager in Laravel.
 */
class ThemosisStorage implements StorageInterface
{
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
   * @param  string $storage
   * @param  bool $pretend
   *
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
    $path = sprintf('%s/%s.css', $this->storage, md5($uri));

    if (!$this->files->exists($path)) {
      return sprintf(
        '/* Critical-path CSS for URI [%s] not found at [%s]. '.
        'Check the config and run `wp critical-css generate`. */',
        $uri,
        $path
      );
    }

    return $this->files->get($path);
  }

  /**
   * Wrap the critical-path CSS inside a '<style>' HTML element and return
   * the HTML.
   *
   * @param  string $uri
   *
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
    $ok = $this->files->put(
      $this->storage.'/'.md5($uri).'.css',
      $css
    );

    if (!$ok) {
      throw new CssWriteException(
        sprintf(
          'Unable to write the critical-path CSS for the URI [%s] to [%s].',
          $uri,
          $css
        )
      );
    }

    return $ok;
  }

  /**
   * Clear the storage.
   *
   * @return bool
   */
  public function clearCss()
  {
    return $this->files->deleteDirectory($this->storage, true);
  }
}
