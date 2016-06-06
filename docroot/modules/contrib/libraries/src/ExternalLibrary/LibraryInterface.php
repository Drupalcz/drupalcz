<?php

namespace Drupal\libraries\ExternalLibrary;

/**
 * Provides an interface for different types of external libraries.
 */
interface LibraryInterface {

  /**
   * Returns the ID of the library.
   *
   * @return string
   *   The library ID. This must be unique among all known libraries.
   *
   * @todo Define what constitutes a "known" library.
   */
  public function getId();

  /**
   * Creates an instance of the library from its definition.
   *
   * @param string $id
   *   The library ID.
   * @param array $definition
   *   The library definition array.
   *
   * @return static
   *
   * @todo Consider passing in some stuff that might be useful.
   */
  public static function create($id, array $definition);

}
