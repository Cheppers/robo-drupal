<?php

use Sweetchuck\Robo\Drupal\Config\DatabaseServerConfig;
use Sweetchuck\Robo\Drupal\Config\PhpVariantConfig;
use Sweetchuck\Robo\Drupal\Config\SiteConfig;
use Sweetchuck\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Sweetchuck\Robo\Drupal\Utils;

return call_user_func(function () {
  $projectConfig = new ProjectConfig();
  $projectConfig->id = 'test';
  $projectConfig->gitExecutable = 'git';
  $projectConfig->environment = 'dev';
  $projectConfig->drupalRootDir = 'drupal_root';
  $projectConfig->phpVariants = [];

  $projectConfig->phpVariants['70106-dev'] = new PhpVariantConfig();
  $projectConfig->phpVariants['70106-dev']->binDir = '/foo/php/70106/bin';

  $projectConfig->phpVariants['50630-dev'] = new PhpVariantConfig();
  $projectConfig->phpVariants['50630-dev']->binDir = '/foo/php/50630/bin';

  $projectConfig->databaseServers = [];
  $projectConfig->databaseServers['my'] = new DatabaseServerConfig(['driver' => 'mysql']);
  $projectConfig->databaseServers['sl'] = new DatabaseServerConfig(['driver' => 'sqlite']);

  $projectConfig->sites = [];

  $projectConfig->sites['okay'] = new SiteConfig();
  $projectConfig->sites['okay']->id = 'okay';
  $projectConfig->sites['okay']->installProfileName = 'development';
  $projectConfig->sites['okay']->urls = [
    '70106-dev.my.okay.test.localhost:1080' => 'okay.my',
    '50630-dev.my.okay.test.localhost:1080' => 'okay.my',
    '70106-dev.sl.okay.test.localhost:1080' => 'okay.sl',
    '50630-dev.sl.okay.test.localhost:1080' => 'okay.sl',
  ];

  $projectConfig->populateDefaultValues();
  if (file_exists(__DIR__ . '/' . Utils::$projectConfigLocalFileName)) {
    include __DIR__ . '/' . Utils::$projectConfigLocalFileName;
  }

  return $projectConfig;
});
