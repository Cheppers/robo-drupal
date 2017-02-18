<?php

namespace Cheppers\Robo\Drupal\Robo\Task;

use Cheppers\Robo\Drupal\ProjectType\Base\ProjectConfig;
use Cheppers\Robo\Drupal\VarExport;
use Robo\Result;
use Robo\Task\BaseTask;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class RebuildSitesPhpTask extends BaseTask
{
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
        $pc = $this->getProjectConfig();
        $projectUrls = $pc->getProjectUrls();
        $sites = '$sites = ' . VarExport::any($projectUrls, 0, '  ') . ";\n";
        $src = Path::join($pc->drupalRootDir, 'sites', 'example.sites.php');
        $dst = Path::join($pc->drupalRootDir, 'sites', 'sites.php');
        $content = (file_exists($src) ? file_get_contents($src) : "<?php\n\n");

        $exitCode = 0;
        $message = '';
        try {
            (new Filesystem())->dumpFile($dst, $content . $sites);
        } catch (IOException $e) {
            $exitCode = 1;
            $message = $e->getMessage();
        }

        return new Result(
            $this,
            $exitCode,
            $message,
            [
                'projectUrls' => $projectUrls,
            ]
        );
    }
}
