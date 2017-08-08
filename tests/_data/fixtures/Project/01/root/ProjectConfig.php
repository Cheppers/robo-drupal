<?php

use Sweetchuck\Robo\Drupal\Config\DatabaseServerConfig;
use Sweetchuck\Robo\Drupal\Config\PhpVariantConfig;
use Sweetchuck\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Sweetchuck\Robo\Drupal\Utils;

return call_user_func(function () {
  $projectConfig = new ProjectConfig();
  $projectConfig->id = 'test';
  $projectConfig->phpVariants = [];

  $projectConfig->phpVariants['70106-dev'] = new PhpVariantConfig();
  $projectConfig->phpVariants['70106-dev']->binDir = PHP_BINDIR;

  $projectConfig->databaseServers = [];
  $projectConfig->databaseServers['sl'] = new DatabaseServerConfig(['driver' => 'sqlite']);

  $projectConfig->sites = [];

  $projectConfig->populateDefaultValues();
  if (file_exists(__DIR__ . '/' . Utils::$projectConfigLocalFileName)) {
    include __DIR__ . '/' . Utils::$projectConfigLocalFileName;
  }

  return $projectConfig;
});
