<?php

namespace Helper\Dummy;

use Codeception\Lib\Console\Output as ConsoleOutput;

class Output extends ConsoleOutput
{
    /**
     * @var string
     */
    public $output = '';

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        $this->output .= $message . ($newline ? "\n" : '');
    }
}
