<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance\Robo\Task;

use Cheppers\Robo\Drupal\Test\AcceptanceTester;
use Cheppers\Robo\Drupal\Tests\Acceptance\Base as BaseCest;
use Symfony\Component\Filesystem\Filesystem;

class RebuildSitesPhpTaskCest extends BaseCest
{
    protected $class = \RebuildSitesPhpTaskRoboFile::class;

    public function runWithoutExampleSitesPhp(AcceptanceTester $I): void
    {
        $tmpDir = $this->createTmpDir();

        $id = __METHOD__;
        $I->runRoboTask($id, $tmpDir, $this->class, 'basic');
        $I->assertEquals(0, $I->getRoboTaskExitCode($id));
        $I->assertEquals("Success\n", $I->getRoboTaskStdOutput($id));

        $expected = implode("\n", [
            '<?php',
            '',
            $this->expectedSitesPhpContent(),
            '',
        ]);
        $I->assertEquals($expected, file_get_contents("$tmpDir/drupal_root/sites/sites.php"));
    }

    public function runWithExampleSitesPhp(AcceptanceTester $I): void
    {
        $tmpDir = $this->createTmpDir();
        $id = __METHOD__;
        $exampleSitesPhp = implode("\n", [
            '<?php',
            '',
            '/* Example sites.php */',
            '',
        ]);
        $fs = new Filesystem();
        $fs->dumpFile("$tmpDir/drupal_root/sites/example.sites.php", $exampleSitesPhp);

        $I->runRoboTask($id, $tmpDir, $this->class, 'basic');
        $I->assertEquals(0, $I->getRoboTaskExitCode($id));
        $I->assertEquals("Success\n", $I->getRoboTaskStdOutput($id));

        $expected = implode("\n", [
            '<?php',
            '',
            '/* Example sites.php */',
            $this->expectedSitesPhpContent(),
            '',
        ]);
        $I->assertEquals($expected, file_get_contents("$tmpDir/drupal_root/sites/sites.php"));
    }

    public function runFail(AcceptanceTester $I): void
    {
        $tmpDir = $this->createTmpDir();
        $id = __METHOD__;
        $fileName = "$tmpDir/drupal_root/sites/sites.php";
        $fs = new Filesystem();
        $fs->dumpFile($fileName, '');
        $fs->chmod($fileName, 0);
        $fs->chmod(dirname($fileName), 0);

        $I->runRoboTask($id, $tmpDir, $this->class, 'basic');
        $I->assertEquals(1, $I->getRoboTaskExitCode($id));
        $I->assertContains("Fail\n", $I->getRoboTaskStdError($id));

        $fs->chmod(dirname($fileName), 0777 - umask());
        $fs->chmod($fileName, 0666 - umask());
    }

    protected function expectedSitesPhpContent(): string
    {
        return implode("\n", [
            '$sites = [',
            "  '70100.my.default.localhost' => 'default.my',",
            "  '50623.my.default.localhost' => 'default.my',",
            "  '70100.pg.default.localhost' => 'default.pg',",
            "  '50623.pg.default.localhost' => 'default.pg',",
            "  '70100.my.foo.localhost' => 'foo.my',",
            "  '50623.my.foo.localhost' => 'foo.my',",
            "  '70100.pg.foo.localhost' => 'foo.pg',",
            "  '50623.pg.foo.localhost' => 'foo.pg',",
            '];',
        ]);
    }
}
