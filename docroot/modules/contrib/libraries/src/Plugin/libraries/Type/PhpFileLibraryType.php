<?php

namespace Drupal\libraries\Plugin\libraries\Type;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\libraries\ExternalLibrary\LibraryInterface;
use Drupal\libraries\ExternalLibrary\Type\LibraryCreationListenerInterface;
use Drupal\libraries\ExternalLibrary\Type\LibraryLoadingListenerInterface;
use Drupal\libraries\ExternalLibrary\Type\LibraryTypeInterface;
use Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLibrary;
use Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLoaderInterface;
use Drupal\libraries\ExternalLibrary\Utility\IdAccessorTrait;
use Drupal\libraries\ExternalLibrary\Version\VersionedLibraryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @LibraryType("php_file")
 */
class PhpFileLibraryType implements
  LibraryTypeInterface,
  LibraryCreationListenerInterface,
  LibraryLoadingListenerInterface,
  ContainerFactoryPluginInterface
{

  use IdAccessorTrait;

  /**
   * The locator factory.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $locatorFactory;

  /**
   * The version detector factory.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $detectorFactory;

  /**
   * The PHP file loader.
   *
   * @var \Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLoaderInterface
   */
  protected $phpFileLoader;

  /**
   * Constructs the PHP file library type.
   *
   * @param string $plugin_id
   *   The plugin ID taken from the class annotation.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $locator_factory
   *   The locator factory.
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $detector_factory
   *   The version detector factory.
   * @param \Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLoaderInterface $php_file_loader
   *   The PHP file loader.
   */
  public function __construct($plugin_id, FactoryInterface $locator_factory, FactoryInterface $detector_factory, PhpFileLoaderInterface $php_file_loader) {
    $this->id = $plugin_id;
    $this->locatorFactory = $locator_factory;
    $this->detectorFactory = $detector_factory;
    $this->phpFileLoader = $php_file_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $container->get('plugin.manager.libraries.locator'),
      $container->get('plugin.manager.libraries.version_detector'),
      $container->get('libraries.php_file_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraryClass() {
    return PhpFileLibrary::class;
  }

  /**
   * {@inheritdoc}
   */
  public function onLibraryCreate(LibraryInterface $library) {
    /** @var \Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLibraryInterface|\Drupal\libraries\ExternalLibrary\Version\VersionedLibraryInterface $library */
    $library->getLocator($this->locatorFactory)->locate($library);
    if ($library instanceof VersionedLibraryInterface) {
      $library->getVersionDetector($this->detectorFactory)->detectVersion($library);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onLibraryLoad(LibraryInterface $library) {
    /** @var \Drupal\libraries\ExternalLibrary\PhpFile\PhpFileLibraryInterface $library */
    // @todo Prevent loading a library multiple times.
    foreach ($library->getPhpFiles() as $file) {
      $this->phpFileLoader->load($file);
    }
  }

}
