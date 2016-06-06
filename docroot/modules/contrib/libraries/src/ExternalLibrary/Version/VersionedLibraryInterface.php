<?php

namespace Drupal\libraries\ExternalLibrary\Version;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\libraries\ExternalLibrary\LibraryInterface;

/**
 * Provides an interface for versioned libraries.
 *
 * Version detection and negotiation is a key aspect of Libraries API's
 * functionality so every type of library should implement this interface.
 */
interface VersionedLibraryInterface extends LibraryInterface {

  /**
   * Gets the version of the library.
   *
   * @return string
   *   The version string, for example 1.0, 2.1.4, or 3.0.0-alpha5.
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\UnknownLibraryVersionException
   *
   * @see \Drupal\libraries\ExternalLibrary\Version\VersionedLibraryInterface::setVersion()
   */
  public function getVersion();

  /**
   * Sets the version of the library.
   *
   * @param string $version
   *   The version of the library.
   *
   * @reutrn $this
   *
   * @see \Drupal\libraries\ExternalLibrary\Version\VersionedLibraryInterface::getVersion()
   */
  public function setVersion($version);

  /**
   * Gets the version detector of this library using the detector factory.
   *
   * Because determining the installation version of a library is not specific
   * to any library or even any library type, this logic is offloaded to
   * separate detector objects.
   *
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $detector_factory
   *
   * @return \Drupal\libraries\ExternalLibrary\Version\VersionDetectorInterface
   *
   * @see \Drupal\libraries\ExternalLibrary\Version\VersionDetectorInterface
   */
  public function getVersionDetector(FactoryInterface $detector_factory);

}
