<?php

namespace Cheppers\Robo\Drupal\Robo\Task;

use Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig;
use Cheppers\Robo\Drupal\VarExport;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * Class RebuildSitesPhpTask.
 *
 * @package Cheppers\Robo\Drupal\Robo\Task
 */
class RebuildSitesPhpTask extends BaseTask
{
    /**
     * @var \Cheppers\Robo\Drupal\ProjectType\Incubator\ProjectConfig
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
        $pc = $this->getProjectConfig();
        $projectUrls = $pc->getProjectUrls();
        $sites = '$sites = ' . VarExport::any($projectUrls, 0, '  ') . ";\n";
        $src = Path::join($pc->drupalRootDir, 'sites', 'example.sites.php');
        $dst = Path::join($pc->drupalRootDir, 'sites', 'sites.php');
        $content = (file_exists($src) ? file_get_contents($src) : "<?php\n\n");

        (new Filesystem())->mkdir(Path::join($pc->drupalRootDir, 'sites'));
        $writtenBytes = file_put_contents($dst, $content . $sites);

        return new Result(
            $this,
            ($writtenBytes === false ? 1 : 0),
            '',
            [
                'projectUrls' => $projectUrls,
            ]
        );
    }
}
