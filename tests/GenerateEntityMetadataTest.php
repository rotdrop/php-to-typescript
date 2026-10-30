<?php
/**
 * @license AGPL-3.0-or-later
 */

namespace Tests;

use OCA\RotDrop\DevScripts\PhpToTypeScript\GenerateEntityMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Console\Output\ConsoleOutput;

class GenerateEntityMetadataTest extends TestCase
{
  public function test_it_formats_metadata_without_toolkit_utilities(): void
  {
    $directory = sys_get_temp_dir() . '/php-to-typescript-metadata-' . bin2hex(random_bytes(8));
    mkdir($directory);
    $generator = new GenerateEntityMetadata(
      devScriptsFolder: __DIR__,
      phpNamespacePrefix: 'OCA\\TestApp',
      outputPrefix: $directory,
      output: new ConsoleOutput(),
    );
    (new ReflectionProperty($generator, 'entityNamespace'))->setValue($generator, 'Database\\Entities');
    (new ReflectionProperty($generator, 'entityMetaInfo'))->setValue($generator, ['Test99Entity' => []]);
    $path = $directory . '/Toolkit/Doctrine/ORM';

    try {
      self::assertStringContainsString(
        'declare namespace Toolkit.Doctrine.ORM.EntityMetadata',
        $generator->exportEntityMap(),
      );
      $generator->dumpTypeScriptData();
      $forwarder = file_get_contents($path . '/EntityMetadata.ts');
      self::assertStringContainsString('TEST_99_ENTITY_ENTITY_NAME', $forwarder);
      $metadata = file_get_contents($path . '/EntityMetadata/Test99EntityMetadata.ts');
      self::assertStringContainsString("../../../../Database/Entities.ts", $metadata);
    } finally {
      foreach ([$path . '/EntityMetadata.ts', $path . '/EntityMetadata/Test99EntityMetadata.ts'] as $file) {
        if (file_exists($file)) {
          unlink($file);
        }
      }
      foreach ([$path . '/EntityMetadata', $path, dirname($path), dirname($path, 2), $directory] as $folder) {
        if (is_dir($folder)) {
          rmdir($folder);
        }
      }
    }
  }
}
