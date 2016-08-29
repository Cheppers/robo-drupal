<?php

namespace Cheppers\Robo\Drupal\Robo\Task\CoreTests;

class ListTask extends BaseTask
{
    protected function buildOptions(): array
    {
        return parent::buildOptions() + [
            'list' => true,
        ];
    }
}
