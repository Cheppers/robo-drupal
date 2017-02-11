<?php

namespace Cheppers\Robo\Drupal\Robo;

use Robo\Collection\CollectionBuilder;

trait DrupalCoreTestsTaskLoader
{

    /**
     * @return \Cheppers\Robo\Drupal\Robo\Task\CoreTests\CleanTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskDrupalCoreTestsClean(array $options = []): CollectionBuilder
    {
        return $this->task(Task\CoreTests\CleanTask::class, $options);
    }

    /**
     * @return \Cheppers\Robo\Drupal\Robo\Task\CoreTests\ListTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskDrupalCoreTestsList(array $options = []): CollectionBuilder
    {
        return $this->task(Task\CoreTests\ListTask::class, $options);
    }

    /**
     * @return \Cheppers\Robo\Drupal\Robo\Task\CoreTests\RunTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskDrupalCoreTestsRun(array $options = []): CollectionBuilder
    {
        return $this->task(Task\CoreTests\RunTask::class, $options);
    }
}
