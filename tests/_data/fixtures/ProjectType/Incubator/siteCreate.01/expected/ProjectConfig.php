<?php

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Cheppers\Robo\Drupal\Utils;

return call_user_func(function () {
  $projectConfig = new ProjectConfig();
  $projectConfig->id = 'test';
  $projectConfig->phpVariants = [];

  $projectConfig->phpVariants['70106-dev'] = new PhpVariantConfig();
  $projectConfig->phpVariants['70106-dev']->binDir = '/foo/php/70106/bin';

  $projectConfig->phpVariants['50630-dev'] = new PhpVariantConfig();
  $projectConfig->phpVariants['50630-dev']->binDir = '/foo/php/50630/bin';

  $projectConfig->databaseServers = [];
  $projectConfig->databaseServers['my'] = new DatabaseServerConfig(['driver' => 'mysql']);
  $projectConfig->databaseServers['sl'] = new DatabaseServerConfig(['driver' => 'sqlite']);

  $projectConfig->sites = [];

  $projectConfig->sites['default'] = new SiteConfig();
  $projectConfig->sites['default']->id = 'default';
  $projectConfig->sites['default']->installProfileName = 'standard';
  $projectConfig->sites['default']->urls = [
    '70106-dev.my.default.test.localhost' => 'default.my',
    '50630-dev.my.default.test.localhost' => 'default.my',
    '70106-dev.sl.default.test.localhost' => 'default.sl',
    '50630-dev.sl.default.test.localhost' => 'default.sl',
  ];

  $projectConfig->populateDefaultValues();
  if (file_exists(__DIR__ . '/' . Utils::$projectConfigLocalFileName)) {
    include __DIR__ . '/' . Utils::$projectConfigLocalFileName;
  }

  return $projectConfig;
});
