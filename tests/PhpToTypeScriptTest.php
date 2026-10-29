<?php
/**
 * @license AGPL-3.0-or-later
 */

namespace Tests;

use OCA\RotDrop\DevScripts\PhpToTypeScript\PhpToTypeScript;
use PHPUnit\Framework\TestCase;
use Spatie\TypeScriptTransformer\Collectors\EnumCollector;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/Fixtures/BasicTypes.php';

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
    $outputFile = $this->outputDir . '/php-types.d.ts';
    if (file_exists($outputFile)) {
      unlink($outputFile);
    }
    rmdir($this->outputDir);
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
}
