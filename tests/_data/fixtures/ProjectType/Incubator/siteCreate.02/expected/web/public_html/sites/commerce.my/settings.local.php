<?php

$settings['extension_discovery_scan_tests'] = FALSE;

$config['system.performance']['js']['preprocess'] = FALSE;
$config['system.performance']['css.gzip'] = TRUE;
$config['system.performance']['js.gzip'] = TRUE;
$config['system.performance']['response.gzip'] = TRUE;

$settings['file_chmod_directory'] = 0755;
$settings['file_chmod_file'] = 0644;

$config['system.logging']['error_level'] = 'verbose';

$config['devel.settings']['error_handlers'] = [4 => 4];
$config['devel.settings']['dumper'] = 'kint';

$config['views.settings']['ui']['show']['advanced_column'] = TRUE;
$config['views.settings']['ui']['show']['sql_query']['enabled'] = TRUE;

$settings['trusted_host_patterns'] = [
  '^70106\\-dev\\.my\\.commerce\\.test\\.localhost$',
  '^50630\\-dev\\.my\\.commerce\\.test\\.localhost$',
];
