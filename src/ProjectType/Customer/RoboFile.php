<?php

namespace Cheppers\Robo\Drupal\ProjectType\Customer;

use Cheppers\Robo\Drupal\ProjectType\Base as Base;

class RoboFile extends Base\RoboFile
{
    /**
     * {@inheritdoc}
     */
    protected $projectConfigClass = ProjectConfig::class;

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Customer\ProjectConfig
     */
    protected $projectConfig = null;
}
