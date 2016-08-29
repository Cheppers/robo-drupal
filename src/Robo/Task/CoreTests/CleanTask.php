<?php

namespace Cheppers\Robo\Drupal\Robo\Task\CoreTests;

class CleanTask extends BaseTask
{
    protected function buildOptions(): array
    {
        return parent::buildOptions() + [
            'clean' => true,
        ];
    }
}
