<?php

$databases = [
  'default' => [
    'default' => [
      'driver' => 'sqlite',
      'database' => '../sites/default.sl/db/default__default.sqlite',
    ],
  ],
];
 
$settings['hash_salt'] = file_get_contents('../sites/default.sl/hash_salt.txt');

$config_directories = [
  CONFIG_SYNC_DIRECTORY => '../sites/default.sl/config/sync',
];

$settings['install_profile'] = 'standard';

$settings['file_public_path'] = 'sites/default.sl/files';

$settings['file_private_path'] = '../sites/default.sl/private';

$settings['file_temporary_path'] = '../sites/default.sl/temporary';

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
