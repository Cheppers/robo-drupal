<?php

use Sweetchuck\Robo\Drupal\Config\DatabaseServerConfig;
use Sweetchuck\Robo\Drupal\Config\PhpVariantConfig;
use Sweetchuck\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Sweetchuck\Robo\Drupal\Utils;

return call_user_func(function () {
  $projectConfig = new ProjectConfig();
  $projectConfig->id = 'test';
  $projectConfig->drupalRootDir = 'web/public_html';
  $projectConfig->outerSitesSubDir = 'project/specific';

  $projectConfig->phpVariants = [];

  $projectConfig->phpVariants['70106-dev'] = new PhpVariantConfig();
  $projectConfig->phpVariants['70106-dev']->binDir = '/foo/php/70106/bin';

  $projectConfig->phpVariants['50630-dev'] = new PhpVariantConfig();
  $projectConfig->phpVariants['50630-dev']->binDir = '/foo/php/50630/bin';

  $projectConfig->databaseServers = [];
  $projectConfig->databaseServers['my'] = new DatabaseServerConfig(['driver' => 'mysql']);
  $projectConfig->databaseServers['sl'] = new DatabaseServerConfig(['driver' => 'sqlite']);

  $projectConfig->sites = [];

  $projectConfig->populateDefaultValues();
  if (file_exists(__DIR__ . '/' . Utils::$projectConfigLocalFileName)) {
    include __DIR__ . '/' . Utils::$projectConfigLocalFileName;
  }

  return $projectConfig;
});
