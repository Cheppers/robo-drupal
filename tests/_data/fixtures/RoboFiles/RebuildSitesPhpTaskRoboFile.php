<?php

use Cheppers\Robo\Drupal\Config\DatabaseServerConfig;
use Cheppers\Robo\Drupal\Config\PhpVariantConfig;
use Cheppers\Robo\Drupal\Config\SiteConfig;
use Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig;
use Cheppers\Robo\Drupal\Robo\DrupalTaskLoader;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class RebuildSitesPhpTaskRoboFile extends Robo\Tasks
{
    use DrupalTaskLoader;

    public function basic(): int
    {
        $pc = new ProjectConfig();

        $pc->phpVariants['70100'] = new PhpVariantConfig();
        $pc->phpVariants['50623'] = new PhpVariantConfig();

        $pc->databaseServers['my'] = new DatabaseServerConfig(['driver' => 'mysql']);
        $pc->databaseServers['pg'] = new DatabaseServerConfig(['driver' => 'pgsql']);

        $pc->sites['default'] = new SiteConfig();
        $pc->sites['default']->urls = [];

        $pc->sites['foo'] = new SiteConfig();
        $pc->sites['foo']->urls = [
            '70100.my.foo.localhost' => 'foo.my',
            '50623.my.foo.localhost' => 'foo.my',
            '70100.pg.foo.localhost' => 'foo.pg',
            '50623.pg.foo.localhost' => 'foo.pg',
        ];

        $pc->populateDefaultValues();

        $result = $this
            ->taskDrupalRebuildSitesPhp()
            ->setProjectConfig($pc)
            ->run();

        $stdOutput = $this->output();
        if ($result->wasSuccessful()) {
            $stdOutput->writeln('Success');
        } else {
            $stdError = ($stdOutput instanceof ConsoleOutputInterface) ? $stdOutput->getErrorOutput() : $stdOutput;
            $stdError->writeln('Fail');
        }

        return $result->getExitCode();
    }
}
