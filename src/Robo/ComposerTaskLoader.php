<?php

namespace Sweetchuck\Robo\Drupal\Robo;

use Robo\Collection\CollectionBuilder;

trait ComposerTaskLoader
{
    /**
     * @return \Sweetchuck\Robo\Drupal\Robo\Task\SiteCreateTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskComposerPackagePaths(array $options = []): CollectionBuilder
    {
        return $this->task(Task\ComposerPackagePathsTask::class, $options);
    }
}
