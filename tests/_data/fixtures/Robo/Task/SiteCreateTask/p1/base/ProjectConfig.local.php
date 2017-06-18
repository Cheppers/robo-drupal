<?php

/**
 * @var \Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig $projectConfig
 */

$projectConfig->baseHostPort = 1080;
$projectConfig->databaseServers['my']->connectionLocal['username'] = 'my_user';
$projectConfig->databaseServers['my']->connectionLocal['password'] = 'my_pass';
$projectConfig->databaseServers['my']->connectionLocal['port'] = 3311;
