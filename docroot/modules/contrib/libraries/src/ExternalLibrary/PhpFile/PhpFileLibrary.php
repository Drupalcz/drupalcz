<?php

namespace Drupal\libraries\ExternalLibrary\PhpFile;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\libraries\ExternalLibrary\Exception\LibraryNotInstalledException;
use Drupal\libraries\ExternalLibrary\LibraryBase;
use Drupal\libraries\ExternalLibrary\Local\LocalLibraryTrait;

/**
 * Provides a base PHP file library implementation.
 */
class PhpFileLibrary extends LibraryBase implements PhpFileLibraryInterface {

  use LocalLibraryTrait;

  /**
   * An array of PHP files for this library.
   *
   * @var array
   */
  protected $files = [];

  /**
   * Constructs a PHP file library.
   *
   * @param string $id
   *   The library ID.
   * @param array $definition
   *   The library definition array.
   */
  public function __construct($id, array $definition) {
    parent::__construct($id, $definition);
    $this->files = $definition['files'];
  }

  /**
   * {@inheritdoc}
   */
  protected static function definitionDefaults() {
    return parent::definitionDefaults() + [
      'files' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPhpFiles() {
    if (!$this->isInstalled()) {
      throw new LibraryNotInstalledException($this);
    }

    $processed_files = [];
    foreach ($this->files as $file) {
      $processed_files[] = $this->getLocalPath() . DIRECTORY_SEPARATOR . $file;
    }
    return $processed_files;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocator(FactoryInterface $locator_factory) {
    return $locator_factory->createInstance('stream', ['scheme' => 'php-file']);
  }

}
