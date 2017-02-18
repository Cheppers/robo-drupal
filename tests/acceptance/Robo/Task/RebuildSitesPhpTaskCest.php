<?php

namespace Cheppers\Robo\Drupal\Tests\Acceptance\Robo\Task;

use AcceptanceTester;
use Symfony\Component\Filesystem\Filesystem;

class RebuildSitesPhpTaskCest
{
    protected $tmpDirs = [];

    protected $tmpDir = '';

    public function __construct()
    {
        register_shutdown_function(function () {
            (new Filesystem())->remove($this->tmpDirs);
        });
    }

    // @codingStandardsIgnoreStart
    public function _before()
    {
        // @codingStandardsIgnoreEnd
        $this->createTmpDir();
    }

    public function runWithoutExampleSitesPhp(AcceptanceTester $I)
    {
        $cwd = getcwd();
        chdir($this->tmpDir);
        $I->runRoboTask(\RebuildSitesPhpTaskRoboFile::class, 'basic');
        chdir($cwd);
        $I->assertEquals(0, $I->getRoboTaskExitCode());
        $I->assertEquals("Success\n", $I->getRoboTaskStdOutput());

        $expected = implode("\n", [
            '<?php',
            '',
            $this->expectedSitesPhpContent(),
            '',
        ]);
        $I->assertEquals($expected, file_get_contents("{$this->tmpDir}/drupal_root/sites/sites.php"));
    }

    public function runWithExampleSitesPhp(AcceptanceTester $I)
    {
        $cwd = getcwd();
        $exampleSitesPhp = implode("\n", [
            '<?php',
            '',
            '/* Example sites.php */',
            '',
        ]);
        $fs = new Filesystem();
        $fs->dumpFile("{$this->tmpDir}/drupal_root/sites/example.sites.php", $exampleSitesPhp);

        chdir($this->tmpDir);
        $I->runRoboTask(\RebuildSitesPhpTaskRoboFile::class, 'basic');
        chdir($cwd);
        $I->assertEquals(0, $I->getRoboTaskExitCode());
        $I->assertEquals("Success\n", $I->getRoboTaskStdOutput());

        $expected = implode("\n", [
            '<?php',
            '',
            '/* Example sites.php */',
            $this->expectedSitesPhpContent(),
            '',
        ]);
        $I->assertEquals($expected, file_get_contents("{$this->tmpDir}/drupal_root/sites/sites.php"));
    }

    public function runFail(AcceptanceTester $I)
    {
        $cwd = getcwd();
        $fileName = "{$this->tmpDir}/drupal_root/sites/sites.php";
        $fs = new Filesystem();
        $fs->dumpFile($fileName, '');
        $fs->chmod($fileName, 0000);
        $fs->chmod(dirname($fileName), 0000);

        chdir($this->tmpDir);
        $I->runRoboTask(\RebuildSitesPhpTaskRoboFile::class, 'basic');
        chdir($cwd);
        $I->assertEquals(1, $I->getRoboTaskExitCode());
        $I->assertEquals("Fail\n", $I->getRoboTaskStdError());

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

    protected function createTmpDir()
    {
        $class = explode('\\', __CLASS__);
        $tmpDir = tempnam(sys_get_temp_dir(), 'robo-drupal-' . end($class));
        if (!$tmpDir) {
            throw new \Exception();
        }

        unlink($tmpDir);
        mkdir($tmpDir);
        $this->tmpDirs[] = $tmpDir;
        $this->tmpDir = $tmpDir;
    }
}
