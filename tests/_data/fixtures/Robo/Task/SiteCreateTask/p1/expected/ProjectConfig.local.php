<?php

/** @var \Cheppers\Robo\Drupal\Config\ProjectIncubatorConfig $projectConfig */
global $projectConfig;

$projectConfig->baseHostPort = 1080;
$projectConfig->databaseServers['my56']->connectionLocal['username'] = 'my56_user';
$projectConfig->databaseServers['my56']->connectionLocal['password'] = 'my56_pass';
$projectConfig->databaseServers['my56']->connectionLocal['port'] = 3311;
