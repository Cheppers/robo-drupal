<?php

namespace Sweetchuck\Robo\Drupal\Tests\Acceptance\ProjectType\Incubator;

use Sweetchuck\Robo\Drupal\Test\AcceptanceTester;
use Sweetchuck\Robo\Drupal\Test\Helper\Utils\TmpDirManager;
use Sweetchuck\Robo\Drupal\Tests\Acceptance\Base as BaseCest;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

class RoboFileCest extends BaseCest
{

    /**
     * @var string
     */
    protected $class = \ProjectTypeIncubatorRoboFile::class;

    public function listTest(AcceptanceTester $i): void
    {
        $projectName = 'siteCreate.01';
        $workingDirectory = $this->prepareProject($projectName);
        $id = __METHOD__;
        $i->runRoboTask(
            $id,
            $workingDirectory,
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

    public function selfManagedExtensionsTest(AcceptanceTester $i): void
    {
        $envRoot = TmpDirManager::create();
        $idPrefix = __METHOD__;

        $i->createNewDrupalProject($envRoot, '01');
        $i->addExtensionsToDrupalProject($envRoot);

        $id = "$idPrefix:self:managed-extensions:table";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'self:managed-extensions'
        );

        $i->canSeeManagedExtensionsTable(
            [
                'm01' => "$envRoot/extensions/m01",
                'm02' => "$envRoot/extensions/m02",
            ],
            $i->getRoboTaskStdOutput($id)
        );
        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'ExitCode === 0');

        $id = "$idPrefix:self:managed-extensions:yaml";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'self:managed-extensions',
            '--format=yaml'
        );
        $i->seeManagedExtensionsYaml(
            [
                'm01' => "$envRoot/extensions/m01",
                'm02' => "$envRoot/extensions/m02",
            ],
            $i->getRoboTaskStdOutput($id),
            'StdOutput'
        );
        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'ExitCode === 0');
    }

    public function gitHooksInstallAndUninstallTest(AcceptanceTester $i): void
    {
        $envRoot = TmpDirManager::create();
        $idPrefix = __METHOD__;

        $i->createNewDrupalProject($envRoot, '01');

        $id = "$idPrefix:githooks:install:before";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'githooks:install'
        );
        $i->assertContains(
            'There is no managed extension under Git VCS.',
            $i->getRoboTaskStdOutput($id)
        );

        $id = "$idPrefix:githooks:uninstall:before";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'githooks:uninstall'
        );
        $i->assertContains(
            'There is no managed extension under Git VCS.',
            $i->getRoboTaskStdOutput($id)
        );

        $i->addExtensionsToDrupalProject($envRoot);

        $id = "$idPrefix:githooks:install";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'githooks:install'
        );

        $i->canSeeGitHooksAreInstalled("$envRoot/extensions/m01/.git");
        $i->canSeeGitHooksAreInstalled("$envRoot/extensions/m02/.git");

        $id = "$idPrefix:githooks:uninstall";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'githooks:uninstall'
        );

        $i->canSeeGitHooksAreNotInstalled("$envRoot/extensions/m01/.git");
        $i->canSeeGitHooksAreNotInstalled("$envRoot/extensions/m02/.git");
    }

    public function siteCreateBasicTest(AcceptanceTester $i): void
    {
        $projectName = 'siteCreate.01';
        $expectedDir = $this->fixturesRoot($projectName) . '/expected';
        $workingDir = $this->prepareProject($projectName);
        $id = __METHOD__;
        $i->runRoboTask(
            $id,
            $workingDir,
            $this->class,
            'site:create'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'Exit code');

        $dirs = [
            'sites/all/translations',
            'drupal_root/sites/default.my/files',
            'sites/default.my/config/sync',
            'sites/default.my/private',
            'sites/default.my/temporary',
            'drupal_root/sites/default.sl/files',
            'sites/default.sl/config/sync',
            'sites/default.sl/db',
            'sites/default.sl/private',
            'sites/default.sl/temporary',
        ];
        foreach ($dirs as $dir) {
            $i->assertDirectoryExists("$workingDir/$dir");
        }

        /** @var \Symfony\Component\Finder\Finder $files */
        $files = (new Finder())
            ->in($expectedDir)
            ->files();
        foreach ($files as $file) {
            $filePath = "$workingDir/" . $file->getRelativePathname();
            $i->openFile($filePath);
            $i->canSeeFileContentsEqual($file->getContents());
        }

        $files = [
            "$workingDir/sites/default.my/hash_salt.txt",
            "$workingDir/sites/default.sl/hash_salt.txt",
        ];
        foreach ($files as $file) {
            $i->assertGreaterThan(0, filesize($file));
        }
    }

    public function siteCreateAdvancedTest(AcceptanceTester $i): void
    {
        $projectName = 'siteCreate.02';
        $expectedDir = $this->fixturesRoot($projectName) . '/expected';
        $workingDir = $this->prepareProject($projectName);
        $id = __METHOD__;
        $description = implode(' ', [
            'create a new site where the "drupalRootDir" and the "outerSitesSubDir" configurations are different than',
            'the default ones.',
        ]);
        $i->wantTo($description);
        $i->runRoboTask(
            $id,
            $workingDir,
            $this->class,
            'site:create',
            'commerce',
            '--profile=minimal',
            '--long=shop',
            '--short=my'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'Exit code');

        $dirs = [
            'project/specific/all/translations',
            'web/public_html/sites/commerce.my/files',
            'project/specific/commerce.my/config/sync',
            'project/specific/commerce.my/private',
            'project/specific/commerce.my/temporary',
            'web/public_html/sites/commerce.sl/files',
            'project/specific/commerce.sl/config/sync',
            'project/specific/commerce.sl/db',
            'project/specific/commerce.sl/private',
            'project/specific/commerce.sl/temporary',
        ];
        foreach ($dirs as $dir) {
            $i->assertDirectoryExists("$workingDir/$dir");
        }

        /** @var \Symfony\Component\Finder\Finder $files */
        $files = (new Finder())
            ->in($expectedDir)
            ->files();
        foreach ($files as $file) {
            $filePath = "$workingDir/" . $file->getRelativePathname();
            $i->openFile($filePath);
            $i->canSeeFileContentsEqual($file->getContents());
        }

        $files = [
            "$workingDir/project/specific/commerce.my/hash_salt.txt",
            "$workingDir/project/specific/commerce.sl/hash_salt.txt",
        ];
        foreach ($files as $file) {
            $i->assertGreaterThan(0, filesize($file), "File has any content: '$file'");
        }
    }

    public function siteDeleteBasicTest(AcceptanceTester $i): void
    {
        $projectName = 'siteDelete.01';
        $workingDir = $this->prepareProject($projectName);
        $expectedDir = $this->fixturesRoot($projectName) . '/expected';
        $id = __METHOD__;
        $i->runRoboTask(
            $id,
            $workingDir,
            $this->class,
            'site:delete',
            '--yes',
            'default'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'Exit code');

        $dirs = [
            'sites/all/translations' => true,
            'sites/default.my' => false,
            'sites/default.sl' => false,
        ];
        foreach ($dirs as $dir => $shouldBeExists) {
            if ($shouldBeExists) {
                $i->assertDirectoryExists("$workingDir/$dir");
            } else {
                $i->assertFileNotExists("$workingDir/$dir");
            }
        }

        /** @var \Symfony\Component\Finder\Finder $files */
        $files = (new Finder())
            ->in($expectedDir)
            ->files();
        foreach ($files as $file) {
            $filePath = Path::join($workingDir, $file->getRelativePathname());
            $i->openFile($filePath);
            $i->canSeeFileContentsEqual($file->getContents());
        }
    }

    public function rebuildSitesPhpTest(AcceptanceTester $i): void
    {
        $projectName = 'siteDelete.01';
        $workingDir = $this->prepareProject($projectName);
        $id = __METHOD__;

        $expected = <<< 'PHP'
<?php

$sites = [
  '70106-dev.my.default.test.localhost' => 'default.my',
  '50630-dev.my.default.test.localhost' => 'default.my',
  '70106-dev.sl.default.test.localhost' => 'default.sl',
  '50630-dev.sl.default.test.localhost' => 'default.sl',
];

PHP;

        $i->runRoboTask(
            $id,
            $workingDir,
            $this->class,
            'rebuild:sites-php'
        );

        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'Exit code');

        $filePath = Path::join($workingDir, 'drupal_root', 'sites', 'sites.php');
        $i->openFile($filePath);
        $i->canSeeFileContentsEqual($expected);
    }

    public function siteInstallTest(AcceptanceTester $i): void
    {
        $tmpDir = TmpDirManager::create();
        $id = __METHOD__;

        $i->createNewDrupalProject($tmpDir, '01');
        $i->runRoboTask(
            "$id:create",
            "$tmpDir/root",
            $this->class,
            'site:create',
            '--profile=minimal',
            '--long=shop',
            '--short=mys',
            'commerce'
        );
        $i->assertEquals(
            0,
            $i->getRoboTaskExitCode("$id:create"),
            'robo site:create ExitCode === 0'
        );

        $i->runRoboTask(
            "$id:install",
            "$tmpDir/root",
            $this->class,
            'site:install',
            'commerce'
        );

        codecept_debug($i->getRoboTaskStdOutput("$id:install"));
        codecept_debug($i->getRoboTaskStdError("$id:install"));

        $i->assertEquals(
            0,
            $i->getRoboTaskExitCode("$id:install"),
            'robo site:install ExitCode === 0'
        );

        $i->seeDrupalSiteIsInstalled("$tmpDir/root", 'commerce.sl');
    }

    public function lintPhpcsTest(AcceptanceTester $i): void
    {
        $envRoot = TmpDirManager::create();
        $idPrefix = __METHOD__;

        $i->createNewDrupalProject($envRoot, '01');
        $i->addExtensionsToDrupalProject($envRoot);

        $id = "$idPrefix:lint:phpcs:all";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'lint:phpcs',
            '-vvv'
        );

        $exitCode = $i->getRoboTaskExitCode($id);
        $stdOutput = $i->getRoboTaskStdOutput($id);
        $expected = implode(PHP_EOL, [
            "$envRoot/extensions/m02/src/Form/M02DummyForm.php",
            '+----------+------+-------------------------------------------------------------+',
            '| Severity | Line | Message                                                     |',
            '+----------+------+-------------------------------------------------------------+',
            '| error    |    5 | Missing class doc comment                                   |',
            '| error    |    6 | Opening brace should be on the same line as the declaration |',
            '+----------+------+-------------------------------------------------------------+',
            '',
        ]);

        $i->assertEquals($expected, $stdOutput);
        $i->assertEquals(2, $exitCode, 'robo lint all ExitCode === 2');

        $id = "$idPrefix:lint:m01";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'lint:phpcs',
            'm01'
        );
        $i->assertEquals(0, $i->getRoboTaskExitCode($id), 'robo lint m01 ExitCode === 0');

        $id = "$idPrefix:lint:m02";
        $i->runRoboTask(
            $id,
            "$envRoot/root",
            $this->class,
            'lint:phpcs',
            'm02'
        );
        $i->assertEquals(2, $i->getRoboTaskExitCode($id), 'robo lint m02 ExitCode === 2');
    }
}
