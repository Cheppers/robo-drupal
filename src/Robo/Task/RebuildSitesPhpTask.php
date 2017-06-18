<?php

namespace Cheppers\Robo\Drupal\Robo\Task;

use Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig;
use Cheppers\Robo\Drupal\VarExport;
use Robo\Result;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class RebuildSitesPhpTask extends BaseTask
{
    /**
     * {@inheritdoc}
     */
    protected $taskName = 'Drupal - Rebuild sites.php';

    /**
     * @var string
     */
    protected $filesystemClass = Filesystem::class;

    /**
     * @var \Symfony\Component\Filesystem\Filesystem
     */
    protected $fs;

    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig
     */
    protected $projectConfig;

    /**
     * TaskRebuildSitesPhp constructor.
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'projectConfig':
                    $this->setProjectConfig($value);
                    break;
            }
        }

        return $this;
    }

    public function getProjectConfig(): ProjectConfig
    {
        return $this->projectConfig;
    }

    /**
     * @return $this
     */
    public function setProjectConfig(ProjectConfig $projectConfig)
    {
        $this->projectConfig = $projectConfig;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function run(): Result
    {
        /** @var \Symfony\Component\Filesystem\Filesystem $fs */
        $this->fs = new $this->filesystemClass();
        $pc = $this->getProjectConfig();
        $sitesPhpDefinition = $pc->getSitesPhpDefinition();
        $dst = Path::join($pc->drupalRootDir, 'sites', 'sites.php');
        $content = $this->getInitialContentOfSitesPhp();
        $content .= '$sites = ' . VarExport::any($sitesPhpDefinition, 0, '  ') . ";\n";

        $exitCode = 0;
        $message = '';
        try {
            $this->fs->dumpFile($dst, $content);
        } catch (IOException $e) {
            $exitCode = 1;
            $message = $e->getMessage();
        }

        return new Result(
            $this,
            $exitCode,
            $message,
            [
                'sitesPhp' => $sitesPhpDefinition,
            ]
        );
    }

    protected function getInitialContentOfSitesPhp(): string
    {
        $src = Path::join($this->getProjectConfig()->drupalRootDir, 'sites', 'example.sites.php');

        return ($this->fs->exists($src) ? file_get_contents($src) : "<?php\n\n");
    }
}
