<?php

namespace Cheppers\Robo\Drupal\ProjectType\Customer;

use Cheppers\Robo\Drupal\ProjectType\Base as Base;

class ProjectConfig extends Base\ProjectConfig
{
    /**
     * {@inheritdoc}
     */
    public $defaultSiteId = 'default';

    /**
     * {@inheritdoc}
     */
    public $siteVariantDirPattern = '{siteBranch}';

    /**
     * {@inheritdoc}
     */
    public $siteVariantUrlPattern = '{siteBranch}.{baseHost}';
}
