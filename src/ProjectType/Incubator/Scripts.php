<?php

namespace Cheppers\Robo\Drupal\ProjectType\Incubator;

use Cheppers\Robo\Drupal\ProjectType\Base as Base;

class Scripts extends Base\Scripts
{
    /**
     * {@inheritdoc}
     */
    protected static $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig
     */
    protected static $projectConfig = null;
}
