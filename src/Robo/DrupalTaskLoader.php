<?php

namespace Cheppers\Robo\Drupal\Robo;

use Robo\Collection\CollectionBuilder;

trait DrupalTaskLoader
{
    /**
     * @return \Cheppers\Robo\Drupal\Robo\Task\SiteCreateTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskDrupalSiteCreate(array $options = []): CollectionBuilder
    {
        return $this->task(Task\SiteCreateTask::class, $options);
    }

    /**
     * @return \Cheppers\Robo\Drupal\Robo\Task\RebuildSitesPhpTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskDrupalRebuildSitesPhp(array $options = []): CollectionBuilder
    {
        return $this->task(Task\RebuildSitesPhpTask::class, $options);
    }
}
