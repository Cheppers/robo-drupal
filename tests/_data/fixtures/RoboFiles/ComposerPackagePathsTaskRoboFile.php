<?php

use Cheppers\AssetJar\AssetJar;
use Cheppers\Robo\Drupal\Robo\ComposerTaskLoader;
use Robo\Collection\CollectionBuilder;

class ComposerPackagePathsTaskRoboFile extends Robo\Tasks
{
    use ComposerTaskLoader;

    public function basic(string $composerExecutable): CollectionBuilder
    {
        return $this
            ->collectionBuilder()
            ->addCode(
                function () use ($composerExecutable) {
                    $assetJar = new AssetJar();
                    $result = $this
                        ->taskComposerPackagePaths([
                            'assetJar' => $assetJar,
                            'assetJarMapping' => [
                                'packagePaths' => ['foo', 'bar'],
                            ],
                            'workingDirectory' => '.',
                            'composerExecutable' => $composerExecutable,
                        ])
                        ->run();

                    if ($result->wasSuccessful() && isset($result['packagePaths']['cheppers/asset-jar'])) {
                        $this->output()->writeln('Success');
                    }

                    return $result->getExitCode();
                }
            );
    }
}
