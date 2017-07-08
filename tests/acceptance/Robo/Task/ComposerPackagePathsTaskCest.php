<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance\Robo\Task;

use Cheppers\Robo\Drupal\Test\AcceptanceTester;

class ComposerPackagePathsTaskCest
{
    protected $class = \ComposerPackagePathsTaskRoboFile::class;

    protected function id(string $suffix): string
    {
        return static::class . ":$suffix";
    }

    public function runBasicSuccess(AcceptanceTester $I)
    {
        $id = $this->id('basic:composer');
        $I->runRoboTask($id, null, $this->class, 'basic', 'composer');
        $I->assertEquals(0, $I->getRoboTaskExitCode($id));
        $I->assertEquals("Success\n", $I->getRoboTaskStdOutput($id));
    }

    public function runBasicFail(AcceptanceTester $I)
    {
        $id = $this->id('basic:false');
        $I->runRoboTask($id, null, $this->class, 'basic', 'false');
        $I->assertEquals(1, $I->getRoboTaskExitCode($id));
        $I->assertContains("Fail\n", $I->getRoboTaskStdError($id));
    }
}
