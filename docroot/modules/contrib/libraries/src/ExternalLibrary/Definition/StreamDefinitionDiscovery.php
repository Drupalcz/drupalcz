<?php

namespace Drupal\libraries\ExternalLibrary\Definition;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\libraries\ExternalLibrary\Exception\LibraryDefinitionNotFoundException;

/**
 * Provides a stream-based implementation of a libraries definition discovery.
 *
 * Given a library ID of 'example', it reads the library definition from the URI
 * 'library-definitions://example.yml'. See LibraryDefinitionsStream for more
 * information.
 *
 * Using a stream wrapper has the benefit of being able to swap out the specific
 * storage implementation without any other part of the code needing to change.
 * For example, the specific directory which holds the library definitions on
 * disk can be changed, multiple directories can be layered as though they were
 * one, or the library definitions can even be read from a remote location
 * without any part of the code other than the stream wrapper implementation
 * itself needing to change.
 *
 * @see \Drupal\libraries\StreamWrapper\LibraryDefinitionsStream
 *
 * @ingroup libraries
 */
class StreamDefinitionDiscovery implements DefinitionDiscoveryInterface {

  /**
   * The serializer for the library definition files.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The scheme of the stream to use for library definitions.
   *
   * @var string
   */
  protected $scheme = 'library-definitions';

  /**
   * Constructs a stream-based library definition discovery.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   The serializer for the library definition files.
   */
  public function __construct(SerializationInterface $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * Checks whether a library definition exists for the given ID.
   *
   * @param string $id
   *   The library ID to check for.
   *
   * @return bool
   *  TRUE if the library definition exists; FALSE otherwise.
   */
  public function hasDefinition($id) {
    return file_exists($this->getFileUri($id));
  }

  /**
   * Returns the library definition for the given ID.
   *
   * @param string $id
   *   The library ID to retrieve the definition for.
   *
   * @return array
   *   The library definition array parsed from the definition JSON file.
   *
   * @throws \Drupal\libraries\ExternalLibrary\Exception\LibraryDefinitionNotFoundException
   */
  public function getDefinition($id) {
    if (!$this->hasDefinition($id)) {
      throw new LibraryDefinitionNotFoundException($id);
    }
    return $this->serializer->decode(file_get_contents($this->getFileUri($id)));
  }

  /**
   * Returns the file URI of the library definition file for a given library ID.
   *
   * @param $id
   *   The ID of the external library.
   *
   * @return string
   *   The file URI of the file the library definition resides in.
   */
  protected function getFileUri($id) {
    $filename = $id . '.' . $this->serializer->getFileExtension();
    return "$this->scheme://$filename";
  }

}
