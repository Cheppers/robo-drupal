<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance\ProjectType\Incubator;

use Cheppers\Robo\Drupal\Test\AcceptanceTester;

class ProjectTypeIncubatorRoboFileCest
{

    /**
     * @var string
     */
    protected $class = \ProjectTypeIncubatorRoboFile::class;

    protected function workingDirectory(string $dir): string
    {
        return codecept_data_dir("fixtures/ProjectType/Incubator/$dir");
    }

    public function listTest(AcceptanceTester $i)
    {
        $id = __METHOD__;
        $i->runRoboTask(
            $id,
            $this->workingDirectory("p1/base"),
            $this->class,
            'list',
            '--format=json'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id));

        $tasks = json_decode($i->getRoboTaskStdOutput($id), true);

        foreach (array_keys($tasks['namespaces']) as $key) {
            $item = $tasks['namespaces'][$key];
            unset($tasks['namespaces'][$key]);
            $tasks['namespaces'][$item['id']] = $item;
        }

        $i->assertEquals(
            [
                'githooks:install',
                'githooks:uninstall',
            ],
            $tasks['namespaces']['githooks']['commands']
        );
    }
}
