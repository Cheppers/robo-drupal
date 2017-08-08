<?php

namespace Sweetchuck\Robo\Drupal\Config;

class GeneralReleaseConfig extends BaseConfig
{
    public $releaseDir = 'release/general';

    public $gitRemoteName = 'origin';

    public $gitRemoteBranch = 'production';

    public $gitLocalBranch = 'production';

    /**
     * {@inheritdoc}
     */
    protected function initPropertyMapping()
    {
        $this->propertyMapping += [
            'releaseDir' => 'releaseDir',
            'gitRemoteName' => 'gitRemoteName',
            'gitRemoteBranch' => 'gitRemoteBranch',
            'gitLocalBranch' => 'gitLocalBranch',
        ];
        parent::initPropertyMapping();

        return $this;
    }
}
