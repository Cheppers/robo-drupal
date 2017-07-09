<?php

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Cheppers\Robo\Drupal\Utils;

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
