<?php

$databases = [
  'default' => [
    'default' => [
      'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
      'driver' => 'mysql',
      'username' => '',
      'password' => '',
      'host' => '127.0.0.1',
      'port' => 3306,
      'collation' => 'utf8mb4_general_ci',
      'prefix' => '',
      'database' => 'test__default__dev',
    ],
  ],
];
 
$settings['hash_salt'] = file_get_contents('../sites/default.my/hash_salt.txt');

$config_directories = [
  CONFIG_SYNC_DIRECTORY => '../sites/default.my/config/sync',
];

$settings['install_profile'] = 'standard';

$settings['file_public_path'] = 'sites/default.my/files';

$settings['file_private_path'] = '../sites/default.my/private';

$settings['file_temporary_path'] = '../sites/default.my/temporary';

/**
 * Session write interval:
 */

$config['locale.settings']['translation']['path'] = '../sites/all/translations';

$config['field_ui.settings']['field_prefix'] = 'standard_';

$config['views.settings']['ui']['exposed_filter_any_label'] = 'new_any';

/**
 * Fast 404 pages:
 */

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
