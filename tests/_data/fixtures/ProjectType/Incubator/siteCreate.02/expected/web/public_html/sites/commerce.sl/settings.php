<?php

$databases = [
  'default' => [
    'default' => [
      'driver' => 'sqlite',
      'database' => '../../project/specific/commerce.sl/db/default__default.sqlite',
    ],
  ],
];
 
$settings['hash_salt'] = file_get_contents('../../project/specific/commerce.sl/hash_salt.txt');

$config_directories = [
  CONFIG_SYNC_DIRECTORY => '../../project/specific/commerce.sl/config/sync',
];

$settings['install_profile'] = 'minimal';

$settings['file_public_path'] = 'sites/commerce.sl/files';

$settings['file_private_path'] = '../../project/specific/commerce.sl/private';

$settings['file_temporary_path'] = '../../project/specific/commerce.sl/temporary';

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
