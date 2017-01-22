<?php

namespace Cheppers\Robo\Drupal\ProjectType\Incubator;

use Cheppers\Robo\Drupal\ProjectType\Base as Base;
use function Stringy\create as s;

class ProjectConfig extends Base\ProjectConfig
{
    /**
     * @var string
     */
    public $releaseDir = 'release';

    /**
     * @var string
     */
    public $releaseGitRemote = 'upstream';

    /**
     * @var string
     */
    public $releaseGitBranchRemote = 'production';

    /**
     * @var string
     */
    public $releaseGitBranchLocal = 'production';

    /**
     * @var \Cheppers\Robo\Drupal\Config\SiteConfig
     */
    public $siteDefaults = [];

    /**
     * One of: development (default), production.
     *
     * @var string
     */
    public $compassEnvironment = 'production';

    /**
     * @var bool
     */
    public $autodetectManagedDrupalExtensions = true;

    /**
     * @var \Cheppers\Robo\Drupal\Config\DrupalExtensionConfig[]
     */
    public $managedDrupalExtensions = [];
}
