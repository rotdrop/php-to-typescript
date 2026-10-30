<?php

use Composer\Autoload\ClassLoader;

foreach (ClassLoader::getRegisteredLoaders() as $vendorDirectory => $loader) {
  $ocpDirectory = $vendorDirectory . '/nextcloud/ocp/OCP';
  if (is_dir($ocpDirectory)) {
    $loader->addPsr4('OCP\\', $ocpDirectory);
  }
}
