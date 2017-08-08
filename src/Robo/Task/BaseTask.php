<?php

namespace Sweetchuck\Robo\Drupal\Robo\Task;

use Sweetchuck\AssetJar\AssetJarAware;
use Sweetchuck\AssetJar\AssetJarAwareInterface;
use Robo\Task\BaseTask as RoboBaseTask;
use Robo\TaskInfo;

abstract class BaseTask extends RoboBaseTask implements AssetJarAwareInterface
{
    use AssetJarAware;

    /**
     * @var string
     */
    protected $taskName = '';

    public function getTaskName(): string
    {
        return $this->taskName ?: TaskInfo::formatTaskName($this);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskContext($context = null)
    {
        if (!$context) {
            $context = [];
        }

        if (empty($context['name'])) {
            $context['name'] = $this->getTaskName();
        }

        return parent::getTaskContext($context);
    }
}
