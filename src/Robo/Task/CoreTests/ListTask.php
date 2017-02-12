<?php

namespace Cheppers\Robo\Drupal\Robo\Task\CoreTests;

use Symfony\Component\Process\Process;

class ListTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    protected $taskName = 'DrupalCoreScriptsRunTestsList';

    /**
     * @var string
     */
    protected $stdOutput = '';

    protected $assets = [
        'groups' => [],
    ];

    /**
     * {@inheritdoc}
     */
    protected function buildOptions(): array
    {
        return parent::buildOptions() + [
            'list' => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function runCallback(string $type, string $data): void
    {
        parent::runCallback($type, $data);
        switch ($type) {
            case Process::OUT:
                $this->stdOutput .= $data;
                break;
        }
    }

    /**
     * @return $this
     */
    protected function parseStdOutput()
    {
        $lines = explode("\n", $this->stdOutput);
        $i = 0;
        while ($i < count($lines) && strpos($lines[$i], '----') !== 0) {
            $i++;
        }
        $i += 2;

        if (!isset($lines[$i])) {
            return $this;
        }

        $group = $lines[$i++];
        while ($i < count($lines)) {
            $line = $lines[$i];
            if (strpos($line, ' - ') === 0) {
                $this->assets['groups'][$group][] = substr($line, 3);
            } elseif ($line) {
                $group = $line;
            }
            $i++;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function runReleaseAssets()
    {
        $this->parseStdOutput();

        return parent::runReleaseAssets();
    }
}
