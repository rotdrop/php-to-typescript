<?php
/**
 * @license AGPL-3.0-or-later
 */

namespace Tests;

use OCA\RotDrop\DevScripts\PhpToTypeScript\PhpToTypeScript;
use OCA\RotDrop\DevScripts\PhpToTypeScript\Collectors\DTOCollector;
use PHPUnit\Framework\TestCase;
use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/Fixtures/BasicTypes.php';
require_once __DIR__ . '/Stubs/Toolkit.php';
require_once __DIR__ . '/Fixtures/ToolkitDtos.php';

class PhpToTypeScriptTest extends TestCase
{
  private string $outputDir;

  protected function setUp(): void
  {
    $this->outputDir = sys_get_temp_dir() . '/php-to-typescript-' . bin2hex(random_bytes(8));
    mkdir($this->outputDir);
  }

  protected function tearDown(): void
  {
    if (is_dir($this->outputDir)) {
      $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($this->outputDir, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST,
      );
      foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
      }
      rmdir($this->outputDir);
    }
  }

  public function test_it_generates_types_from_php_classes_and_enums(): void
  {
    $command = new PhpToTypeScript(
      devScriptsFolder: __DIR__,
      collectors: [EnumCollector::class],
    );
    $input = new ArrayInput([
      '--output-prefix' => $this->outputDir,
      '--source-dir' => [__DIR__ . '/Fixtures'],
      '--ns-prefix' => 'Tests\\Fixtures',
    ], $command->getDefinition());
    $output = new class extends ConsoleOutput {
      private string $buffer = '';

      protected function doWrite(string $message, bool $newline): void
      {
        $this->buffer .= $message . ($newline ? PHP_EOL : '');
      }

      public function fetch(): string
      {
        return $this->buffer;
      }
    };

    self::assertSame(0, $command->run($input, $output));

    $types = file_get_contents($this->outputDir . '/php-types.d.ts');
    self::assertIsString($types);
    self::assertStringContainsString('export type BasicType = {', $types);
    self::assertStringContainsString('name: string;', $types);
    self::assertStringContainsString('count?: number;', $types);
    self::assertStringContainsString("export type BasicStatus = 'ready' | 'archived';", $types);
  }

  public function test_it_requires_output_and_source_options(): void
  {
    $command = new PhpToTypeScript(devScriptsFolder: __DIR__);
    $input = new ArrayInput([], $command->getDefinition());
    $output = new BufferedOutput();

    self::assertSame(2, $command->run($input, $output));
    self::assertStringContainsString('The "--output-prefix" option is mandatory.', $output->fetch());
  }

  public function test_it_generates_modules_for_top_level_types_referencing_namespaced_types(): void
  {
    $outputFile = $this->outputDir . '/php-types.d.ts';
    file_put_contents($outputFile, <<<TS
declare namespace Example.Types {
export type Thing = string;
}
export type RootThing = Example.Types.Thing;
TS);

    $command = new PhpToTypeScript(devScriptsFolder: __DIR__);
    $generateModules = new \ReflectionMethod($command, 'generateTypeScriptModules');
    set_error_handler(static function (int $severity, string $message): bool {
      if ($severity === E_DEPRECATED) {
        throw new \ErrorException($message, 0, $severity);
      }
      return false;
    });
    try {
      $generateModules->invoke($command, $this->outputDir, $outputFile, new ConsoleOutput());
    } finally {
      restore_error_handler();
    }

    $rootModule = file_get_contents($this->outputDir . '/php-modules/ROOT.ts');
    self::assertIsString($rootModule);
    self::assertStringContainsString("import type * as Types from './Example/Types.ts';", $rootModule);
    self::assertStringContainsString('export type RootThing = Types.Thing;', $rootModule);
  }

  public function test_it_collects_only_scoped_toolkit_dtos_when_scoped(): void
  {
    $command = new PhpToTypeScript(
      devScriptsFolder: __DIR__,
      collectors: [DTOCollector::class],
    );
    $input = new ArrayInput([
      '--output-prefix' => $this->outputDir,
      '--source-dir' => [__DIR__ . '/Fixtures'],
      '--ns-prefix' => 'OCA\\TestApp',
      '--scoped-ns-prefix' => '\\Wrapped\\',
    ], $command->getDefinition());

    self::assertSame(0, $command->run($input, new ConsoleOutput()));

    $types = file_get_contents($this->outputDir . '/php-types.d.ts');
    self::assertStringContainsString('export type ScopedDTO = {', $types);
    foreach (['UnscopedDTO', 'LegacyDTO'] as $name) {
      self::assertStringNotContainsString("export type {$name}", $types);
    }
    self::assertStringNotContainsString('export type UnrelatedDTO', $types);
  }
}
