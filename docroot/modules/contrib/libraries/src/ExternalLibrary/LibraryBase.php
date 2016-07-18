<?php

namespace Drupal\libraries\ExternalLibrary;

use Drupal\libraries\ExternalLibrary\Dependency\DependentLibraryInterface;
use Drupal\libraries\ExternalLibrary\Dependency\DependentLibraryTrait;
use Drupal\libraries\ExternalLibrary\Utility\IdAccessorTrait;
use Drupal\libraries\ExternalLibrary\Version\VersionedLibraryInterface;
use Drupal\libraries\ExternalLibrary\Version\VersionedLibraryTrait;

/**
 * Provides a base external library implementation.
 */
abstract class LibraryBase implements
  LibraryInterface,
  DependentLibraryInterface,
  VersionedLibraryInterface
{

  use
    IdAccessorTrait,
    DependentLibraryTrait,
    VersionedLibraryTrait
  ;

  /**
   * Constructs a library.
   *
   * @param string $id
   *   The library ID.
   * @param array $definition
   *   The library definition array.
   */
  public function __construct($id, array $definition) {
    $this->id = (string) $id;
    $this->dependencies = $definition['dependencies'];
    $this->versionDetector = $definition['version_detector'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create($id, array $definition) {
    $definition += static::definitionDefaults();
    return new static($id, $definition);
  }

  /**
   * Gets library definition defaults.
   *
   * @return array
   *   An array of library definition defaults.
   */
  protected static function definitionDefaults() {
    return [
      'dependencies' => [],
      // @todo This fallback is not very elegant.
      'version_detector' => [
        'id' => 'static',
        'configuration' => ['version' => ''],
      ],
    ];
  }

}
