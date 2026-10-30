<?php
/**
 * @license AGPL-3.0-or-later
 */

namespace Tests;

use OCA\RotDrop\DevScripts\PhpToTypeScript\Collectors\DTOCollector;
use OCA\RotDrop\DevScripts\PhpToTypeScript\TransformerConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Spatie\TypeScriptTransformer\Transformers\DtoTransformer;

require_once __DIR__ . '/Stubs/Toolkit.php';
require_once __DIR__ . '/Fixtures/ToolkitDtos.php';

class DTOCollectorTest extends TestCase
{
  public function test_it_preserves_unscoped_collection_without_a_wrapper(): void
  {
    $config = TransformerConfig::create()
      ->appNamespace('OCA\\TestApp')
      ->transformers([DtoTransformer::class]);
    $collector = new DTOCollector($config);

    foreach (['UnscopedDTO'] as $name) {
      self::assertNotNull($collector->getTransformedType(new ReflectionClass('OCA\\TestApp\\DTO\\' . $name)));
    }
    foreach (['ScopedDTO', 'LegacyDTO', 'UnrelatedDTO'] as $name) {
      self::assertNull($collector->getTransformedType(new ReflectionClass('OCA\\TestApp\\DTO\\' . $name)));
    }
  }
}
