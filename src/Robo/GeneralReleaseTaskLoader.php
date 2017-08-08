<?php

namespace Sweetchuck\Robo\Drupal\Robo;

use Robo\Collection\CollectionBuilder;

trait GeneralReleaseTaskLoader
{
    /**
     * @return \Sweetchuck\Robo\Drupal\Robo\Task\Release\GeneralReleaseTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskGeneralRelease(array $options = []): CollectionBuilder
    {
        return $this->task(Task\Release\GeneralReleaseTask::class, $options);
    }
}
