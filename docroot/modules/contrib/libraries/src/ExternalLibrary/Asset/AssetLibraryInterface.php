<?php

namespace Drupal\libraries\ExternalLibrary\Asset;

use Drupal\libraries\ExternalLibrary\LibraryInterface;
use Drupal\libraries\ExternalLibrary\LibraryManagerInterface;

/**
 * Provides an interface for library with assets.
 *
 * @see \Drupal\libraries\ExternalLibrary\Asset\AssetLibraryTrait
 *
 * @todo Explain
 */
interface AssetLibraryInterface extends LibraryInterface {

  /**
   * Returns a core asset library array structure for this library.
   *
   * @param \Drupal\libraries\ExternalLibrary\LibraryManagerInterface $library_manager
   *   The library manager that can be used to fetch dependencies.
   *
   * @return array
   *
   * @see libraries_library_info_build()
   * @see \Drupal\libraries\ExternalLibrary\Asset\SingleAssetLibraryTrait
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\InvalidLibraryDependencyException
   *
   * @todo Document the return value.
   * @todo Reconsider passing the library manager.
   */
  public function getAttachableAssetLibraries(LibraryManagerInterface $library_manager);

}
