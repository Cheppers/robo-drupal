<?php

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Cheppers\Robo\Drupal\Utils;

return call_user_func(function () {
  $projectConfig = new ProjectConfig();
  $projectConfig->id = 'test';
  $projectConfig->gitExecutable = 'git';
  $projectConfig->environment = 'dev';
  $projectConfig->drupalRootDir = 'drupal_root';
  $projectConfig->publicHtmlDir = 'public_html';
  $projectConfig->phpVariants = [];

  $projectConfig->phpVariants['709-dev'] = new PhpVariantConfig();
  $projectConfig->phpVariants['709-dev']->id = '709-dev';
  $projectConfig->phpVariants['709-dev']->version = '7.0.9';
  $projectConfig->phpVariants['709-dev']->binDir = '/foo/php/709/bin';

  $projectConfig->databaseServers = [];
  $projectConfig->databaseServers['my56'] = new DatabaseServerConfig();

  $projectConfig->sites = [];

  $projectConfig->sites['okay'] = new SiteConfig();
  $projectConfig->sites['okay']->id = 'okay';
  $projectConfig->sites['okay']->installProfileName = 'standard';
  $projectConfig->sites['okay']->urls = [
    '709-dev.my56.okay.test.localhost:1080' => 'okay.my56',
  ];

  $projectConfig->populateDefaultValues();
  if (file_exists(__DIR__ . '/' . Utils::$projectConfigLocalFileName)) {
    include __DIR__ . '/' . Utils::$projectConfigLocalFileName;
  }

  return $projectConfig;
});
