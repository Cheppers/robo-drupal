<?php

namespace Cheppers\Robo\Drupal\Config;

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
        parent::initPropertyMapping();

        $this->propertyMapping['releaseDir'] = 'releaseDir';
        $this->propertyMapping['gitRemoteName'] = 'gitRemoteName';
        $this->propertyMapping['gitRemoteBranch'] = 'gitRemoteBranch';
        $this->propertyMapping['gitLocalBranch'] = 'gitLocalBranch';

        return $this;
    }
}
