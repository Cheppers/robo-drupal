<?php

namespace Cheppers\Robo\Drupal\ProjectType\Customer;

use Cheppers\Robo\Drupal\ProjectType\Base as Base;

class Scripts extends Base\Scripts
{
    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Customer\ProjectConfig
     */
    protected static $projectConfig = null;

    /**
     * {@inheritdoc}
     */
    protected static $baseNamespace = '\Cheppers\Robo\Drupal\ProjectType\Customer';
}
