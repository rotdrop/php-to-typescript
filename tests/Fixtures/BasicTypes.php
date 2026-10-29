<?php
/**
 * @license AGPL-3.0-or-later
 */

namespace Tests\Fixtures;

use OCA\RotDrop\DevScripts\PhpToTypeScript\Attributes\TypeScript;

#[TypeScript]
class BasicType
{
  public string $name;

  public ?int $count;
}

#[TypeScript]
enum BasicStatus: string
{
  case Ready = 'ready';
  case Archived = 'archived';
}
