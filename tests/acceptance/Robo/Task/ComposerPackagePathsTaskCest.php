<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance\Robo\Task;

use AcceptanceTester;

class ComposerPackagePathsTaskCest
{
    public function runBasicSuccess(AcceptanceTester $I)
    {
        $I->runRoboTask(\ComposerPackagePathsTaskRoboFile::class, 'basic', 'composer');
        $I->assertEquals(0, $I->getRoboTaskExitCode());
        $I->assertEquals("Success\n", $I->getRoboTaskStdOutput());
    }

    public function runBasicFail(AcceptanceTester $I)
    {
        $I->runRoboTask(\ComposerPackagePathsTaskRoboFile::class, 'basic', 'false');
        $I->assertEquals(1, $I->getRoboTaskExitCode());
        $I->assertContains("Fail\n", $I->getRoboTaskStdError());
    }
}
