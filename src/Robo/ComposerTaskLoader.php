<?php

namespace Cheppers\Robo\Drupal\Robo;

use Robo\Collection\CollectionBuilder;

/**
 * Class ComposerTaskLoader.
 *
 * @package Cheppers\Robo\Drupal\Robo
 */
trait ComposerTaskLoader
{
    /**
     * @param array $options
     *
     * @return \Cheppers\Robo\Drupal\Robo\Task\SiteCreateTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskComposerPackagePaths(array $options = []): CollectionBuilder
    {
        return $this->task(Task\ComposerPackagePathsTask::class, $options);
    }
}
