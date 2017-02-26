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

    /**
     * @var string
     */
    public $releaseDir = 'release';

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        parent::initPropertyMapping();
        $this->propertyMapping += ['releaseDir' => 'releaseDir'];

        return $this;
    }
}
