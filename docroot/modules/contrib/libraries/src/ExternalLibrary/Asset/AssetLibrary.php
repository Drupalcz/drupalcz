<?php

namespace Drupal\libraries\ExternalLibrary\Asset;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\libraries\ExternalLibrary\Dependency\DependentLibraryInterface;
use Drupal\libraries\ExternalLibrary\LibraryBase;
use Drupal\libraries\ExternalLibrary\Local\LocalLibraryInterface;
use Drupal\libraries\ExternalLibrary\Local\LocalLibraryTrait;
use Drupal\libraries\ExternalLibrary\Remote\RemoteLibraryInterface;
use Drupal\libraries\ExternalLibrary\Remote\RemoteLibraryTrait;
use Drupal\libraries\ExternalLibrary\Version\VersionedLibraryInterface;

/**
 * Provides a base asset library implementation.
 */
class AssetLibrary extends LibraryBase implements
  AssetLibraryInterface,
  VersionedLibraryInterface,
  DependentLibraryInterface,
  LocalLibraryInterface,
  RemoteLibraryInterface
{

  use
    LocalLibraryTrait,
    RemoteLibraryTrait,
    SingleAssetLibraryTrait,
    LocalRemoteAssetTrait
  ;

  /**
   * Construct an external library.
   *
   * @param string $id
   *   The library ID.
   * @param array $definition
   *   The library definition array.
   */
  public function __construct($id, array $definition) {
    parent::__construct($id, $definition);
    $this->remoteUrl = $definition['remote_url'];
    $this->cssAssets = $definition['css'];
    $this->jsAssets = $definition['js'];
  }

  /**
   * {@inheritdoc}
   */
  protected static function definitionDefaults() {
    return parent::definitionDefaults() + [
      'remote_url' => '',
      'css' => [],
      'js' => [],
    ];
  }

  /**
   * Gets the locator of this library using the locator factory.
   *
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $locator_factory
   *
   * @return \Drupal\libraries\ExternalLibrary\Local\LocatorInterface
   *
   * @see \Drupal\libraries\ExternalLibrary\Local\LocalLibraryInterface::getLocator()
   */
  public function getLocator(FactoryInterface $locator_factory) {
    return $locator_factory->createInstance('stream', ['scheme' => 'asset']);
  }

}
