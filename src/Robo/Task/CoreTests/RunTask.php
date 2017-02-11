<?php

namespace Cheppers\Robo\Drupal\Robo\Task\CoreTests;

use Cheppers\Robo\Drush\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class RunTask extends BaseTask
{

    //region Option - url.
    /**
     * @var string
     */
    protected $url = '';

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return $this
     */
    public function setUrl(string $url)
    {
        $this->url = $url;

        return $this;
    }
    //endregion

    //region Option - sqlite.
    /**
     * @var string
     */
    protected $sqlite = '';

    public function getSqlite(): string
    {
        return $this->sqlite;
    }

    /**
     * @return $this
     */
    public function setSqlite(string $sqlite)
    {
        $this->sqlite = $sqlite;

        return $this;
    }
    //endregion

    //region Option - keepResultsTable.
    /**
     * @var bool
     */
    protected $keepResultsTable = false;

    public function getKeepResultsTable(): bool
    {
        return $this->keepResultsTable;
    }

    /**
     * @return $this
     */
    public function setKeepResultsTable(bool $keepResultsTable)
    {
        $this->keepResultsTable = $keepResultsTable;

        return $this;
    }
    //endregion

    //region Option - keepResults.
    /**
     * @var bool
     */
    protected $keepResults = false;

    public function getKeepResults(): bool
    {
        return $this->keepResults;
    }

    /**
     * @return $this
     */
    public function setKeepResults(bool $keepResults)
    {
        $this->keepResults = $keepResults;

        return $this;
    }
    //endregion

    //region Option - dbUrl.
    /**
     * @var string
     */
    protected $dbUrl = '';

    public function getDbUrl(): string
    {
        return $this->dbUrl;
    }

    /**
     * @return $this
     */
    public function setDbUrl(string $dbUrl)
    {
        $this->dbUrl = $dbUrl;

        return $this;
    }
    //endregion

    //region Option - php.
    /**
     * @var string
     */
    protected $php = '';

    public function getPhp(): string
    {
        return $this->php;
    }

    /**
     * @return $this
     */
    public function setPhp(string $php)
    {
        $this->php = $php;

        return $this;
    }
    //endregion

    //region Option - concurrency.
    /**
     * @var int
     */
    protected $concurrency = 0;

    public function getConcurrency(): int
    {
        return $this->concurrency;
    }

    /**
     * @return $this
     */
    public function setConcurrency(int $concurrency)
    {
        $this->concurrency = $concurrency;

        return $this;
    }
    //endregion

    //region Option - xml.
    /**
     * @var string
     */
    protected $xml = '';

    public function getXml(): string
    {
        return $this->xml;
    }

    /**
     * @return $this
     */
    public function setXml(string $xml)
    {
        $this->xml = $xml;

        return $this;
    }
    //endregion

    //region Option - colorized.
    /**
     * @var bool
     */
    protected $colorized = true;

    public function isColorized(): bool
    {
        return $this->colorized;
    }

    /**
     * @return $this
     */
    public function setColorized(bool $colorized)
    {
        $this->colorized = $colorized;

        return $this;
    }
    //endregion

    //region Option - verbose.
    /**
     * @var bool
     */
    protected $verbose = true;

    public function isVerbose(): bool
    {
        return $this->verbose;
    }

    /**
     * @return $this
     */
    public function setVerbose(bool $verbose)
    {
        $this->verbose = $verbose;

        return $this;
    }
    //endregion

    //region Option - repeat.
    /**
     * @var int
     */
    protected $repeat = 0;

    public function getRepeat(): int
    {
        return $this->repeat;
    }

    /**
     * @return $this
     */
    public function setRepeat(int $repeat)
    {
        $this->repeat = $repeat;

        return $this;
    }
    //endregion

    //region Option - dieOnFail.
    /**
     * @var bool
     */
    protected $dieOnFail = false;

    public function getDieOnFail(): bool
    {
        return $this->dieOnFail;
    }

    /**
     * @return $this
     */
    public function setDieOnFail(bool $dieOnFail)
    {
        $this->dieOnFail = $dieOnFail;

        return $this;
    }
    //endregion

    //region Option - browser.
    /**
     * @var bool
     */
    protected $browser = false;

    public function getBrowser(): bool
    {
        return $this->browser;
    }

    /**
     * @return $this
     */
    public function setBrowser(bool $browser)
    {
        $this->browser = $browser;

        return $this;
    }
    //endregion

    //region Option - nonHtml.
    /**
     * @var bool
     */
    protected $nonHtml = true;

    public function isNonHtml(): bool
    {
        return $this->nonHtml;
    }

    /**
     * @return $this
     */
    public function setNonHtml(bool $nonHtml)
    {
        $this->nonHtml = $nonHtml;

        return $this;
    }
    //endregion

    //region Option - subjectType.
    /**
     * @var string
     */
    protected $subjectType = '';

    protected $validSubjectTypes = [
        '',
        'module',
        'class',
        'file',
        'types',
        'directory',
    ];

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    /**
     * @return $this
     */
    public function setSubjectType(string $subjectType)
    {
        if (!in_array($subjectType, $this->validSubjectTypes)) {
            $msg = sprintf('Valid subject types are: "%s".', implode(', ', $this->validSubjectTypes));

            throw new \InvalidArgumentException($msg);
        }

        $this->subjectType = $subjectType;

        return $this;
    }
    //endregion

    /**
     * @return $this
     */
    public function setOptions(array $options)
    {
        parent::setOptions($options);

        foreach ($options as $name => $value) {
            switch ($name) {
                case 'url':
                    $this->setUrl($value);
                    break;

                case 'sqlite':
                    $this->setSqlite($value);
                    break;

                case 'keepResultsTable':
                case 'keep-results-table':
                    $this->setKeepResultsTable($value);
                    break;

                case 'keepResults':
                case 'keep-results':
                    $this->setKeepResults($value);
                    break;

                case 'dbUrl':
                case 'dburl':
                    $this->setDbUrl($value);
                    break;

                case 'php':
                    $this->setPhp($value);
                    break;

                case 'concurrency':
                    $this->setConcurrency($value);
                    break;

                case 'xml':
                    $this->setXml($value);
                    break;

                case 'color':
                case 'colorized':
                    $this->setColorized($value);
                    break;

                case 'verbose':
                    $this->setVerbose($value);
                    break;

                case 'repeat':
                    $this->setRepeat($value);
                    break;

                case 'dieOnFail':
                case 'die-on-fail':
                    $this->setDieOnFail($value);
                    break;

                case 'browser':
                    $this->setBrowser($value);
                    break;

                case 'nonHtml':
                case 'non-html':
                    $this->setNonHtml($value);
                    break;

                case 'subjectType':
                    $this->setSubjectType($value);
                    break;
            }
        }

        return $this;
    }

    protected function buildOptions(): array
    {
        $options = parent::buildOptions() + [
            'url' => $this->getUrl(),
            'sqlite' => $this->getSqlite(),
            'keep-results-table' => $this->getKeepResultsTable(),
            'keep-results' => $this->getKeepResults(),
            'dburl' => $this->getDbUrl(),
            'php' => $this->getPhp(),
            'concurrency' => $this->getConcurrency(),
            'xml' => $this->getXml(),
            'color' => $this->isColorized(),
            'verbose' => $this->isVerbose(),
            'repeat' => $this->getRepeat(),
            'die-on-fail' => $this->getDieOnFail(),
            'browser' => $this->getBrowser(),
            'non-html' => $this->isNonHtml(),
        ];

        $subjectType = $this->getSubjectType();
        if (!$subjectType) {
            $arguments = Utils::filterDisabled($this->getArguments());
            $subject = reset($arguments);
            $subjectType = $this->autodetectSubjectType($subject);
        }

        $options[$subjectType] = true;

        return $options;
    }

    /**
     * @return $this
     */
    protected function runPrepare()
    {
        parent::runPrepare();

        $xml = $this->getXml();
        if ($xml) {
            $this->prepareDirectory($xml, $this->getDrupalRoot());
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareDirectory(string $dir, string $baseDir)
    {
        $baseDir = Path::makeAbsolute($baseDir ?: '.', getcwd());
        $dir = Path::makeAbsolute($dir, $baseDir);

        if (!file_exists($dir)) {
            (new Filesystem())->mkdir($dir);
        }

        return $this;
    }

    protected function autodetectSubjectType(string $subject): string
    {
        if (!$subject) {
            return 'all';
        }

        if (strpos($subject, '\\') !== false) {
            return 'class';
        }

        if (strpos($subject, '/') !== false) {
            $drupalRoot = Path::makeAbsolute($this->getDrupalRoot(), getcwd());

            return is_dir(Path::join($drupalRoot, $subject)) ? 'directory' : 'file';
        }

        return (strtolower($subject) !== $subject) ? 'types' : 'module';
    }
}
