<?php

namespace Sweetchuck\Robo\Drupal\ProjectType\Incubator;

use Sweetchuck\Robo\Drupal\ProjectType\Base as Base;

class Scripts extends Base\Scripts
{
    /**
     * {@inheritdoc}
     */
    protected static $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Sweetchuck\Robo\Drupal\ProjectType\Incubator\ProjectConfig
     */
    protected static $projectConfig = null;
}
