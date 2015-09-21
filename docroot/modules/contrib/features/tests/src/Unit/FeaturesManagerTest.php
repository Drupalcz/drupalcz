<?php

/**
 * @file
 * Contains \Drupal\Tests\features\Unit\FeaturesManagerTest.
 */

namespace Drupal\Tests\features\Unit;

use Drupal\features\FeaturesManager;
use Drupal\features\FeaturesManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass Drupal\features\FeaturesManager
 * @group features
 */
class FeaturesManagerTest extends UnitTestCase {

  /**
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $entity_type = $this->getMock('\Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getConfigPrefix')
      ->willReturn('custom');
    $entity_manager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getDefinition')
      ->willReturn($entity_type);
    $config_factory = $this->getMock('\Drupal\Core\Config\ConfigFactoryInterface');
    $storage = $this->getMock('Drupal\Core\Config\StorageInterface');
    $config_manager = $this->getMock('Drupal\Core\Config\ConfigManagerInterface');
    $module_handler = $this->getMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->featuresManager = new FeaturesManager($entity_manager, $config_factory, $storage, $config_manager, $module_handler);
  }

  /**
   * @covers ::getActiveStorage
   */
  public function testGetActiveStorage() {
    $this->assertInstanceOf('\Drupal\Core\Config\StorageInterface', $this->featuresManager->getActiveStorage());
  }

  /**
   * @covers ::getExtensionStorage
   */
  public function testGetExtensionStorage() {
    $this->assertInstanceOf('\Drupal\features\FeaturesInstallStorage', $this->featuresManager->getExtensionStorage());
  }

  /**
   * @covers ::getFullName
   * @dataProvider providerTestGetFullName
   */
  public function testGetFullName($type, $name, $expected) {
    $this->assertEquals($this->featuresManager->getFullName($type, $name), $expected);
  }

  /**
   * Data provider for ::testGetFullName().
   */
  public function providerTestGetFullName() {
    return [
      [NULL, 'name', 'name'],
      [FeaturesManagerInterface::SYSTEM_SIMPLE_CONFIG, 'name', 'name'],
      ['custom', 'name', 'custom.name'],
    ];
  }

  /**
   * @covers ::getPackage
   * @covers ::getPackages
   * @covers ::reset
   * @covers ::setPackages
   */
  public function testPackages() {
    $packages = ['foo' => 'bar'];
    $this->featuresManager->setPackages($packages);
    $this->assertEquals($packages, $this->featuresManager->getPackages());
    $this->assertEquals('bar', $this->featuresManager->getPackage('foo'));
    $this->featuresManager->reset();
    $this->assertArrayEquals([], $this->featuresManager->getPackages());
    $this->assertNull($this->featuresManager->getPackage('foo'));
  }

  /**
   * @covers ::setConfigCollection
   * @covers ::getConfigCollection
   */
  public function testConfigCollection() {
    $config = ['config' => 'collection'];
    $this->featuresManager->setConfigCollection($config);
    $this->assertArrayEquals($config, $this->featuresManager->getConfigCollection());
  }

  /**
   * @covers ::savePackage
   */
  public function testSavePackage() {
    // @todo
  }

}
