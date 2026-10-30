<?php
/**
 * @license AGPL-3.0-or-later
 */

namespace OCA\TestApp\DTO;

class UnscopedDTO extends \OCA\RotDrop\Toolkit\DTO\AbstractDTO
{
  public string $unscoped;
}

class LegacyDTO extends \OCA\TestApp\Toolkit\DTO\AbstractDTO
{
  public string $legacy;
}

class ScopedDTO extends \OCA\TestApp\Wrapped\OCA\RotDrop\Toolkit\DTO\AbstractDTO
{
  public string $scoped;
}

class UnrelatedDTO
{
  public string $unrelated;
}
