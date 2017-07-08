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
      'database' => 'test__commerce__dev',
    ],
  ],
];
 
$settings['hash_salt'] = file_get_contents('../../project/specific/commerce.my/hash_salt.txt');

$config_directories = [
  CONFIG_SYNC_DIRECTORY => '../../project/specific/commerce.my/config/sync',
];

$settings['install_profile'] = 'minimal';

$settings['file_public_path'] = 'sites/commerce.my/files';

$settings['file_private_path'] = '../../project/specific/commerce.my/private';

$settings['file_temporary_path'] = '../../project/specific/commerce.my/temporary';

/**
 * Session write interval:
 */

$config['locale.settings']['translation']['path'] = '../../project/specific/all/translations';

$config['field_ui.settings']['field_prefix'] = 'my_';

$config['views.settings']['ui']['exposed_filter_any_label'] = 'new_any';

/**
 * Fast 404 pages:
 */

if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
