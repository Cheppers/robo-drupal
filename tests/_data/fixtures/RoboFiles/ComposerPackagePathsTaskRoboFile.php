<?php

use Sweetchuck\AssetJar\AssetJar;
use Sweetchuck\Robo\Drupal\Robo\ComposerTaskLoader;
use Sweetchuck\Robo\Drupal\Utils;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ComposerPackagePathsTaskRoboFile extends Robo\Tasks
{
    use ComposerTaskLoader;

    public function basic(string $composerExecutable): int
    {
        $assetJar = new AssetJar();
        $result = $this
            ->taskComposerPackagePaths([
                'assetJar' => $assetJar,
                'assetJarMapping' => [
                    'packagePaths' => ['foo', 'bar'],
                ],
                'workingDirectory' => Utils::getRoboDrupalRoot(),
                'composerExecutable' => $composerExecutable,
            ])
            ->run();

        $stdOutput = $this->output();
        if ($result->wasSuccessful() && isset($result['packagePaths']['sweetchuck/asset-jar'])) {
            $stdOutput->writeln('Success');
        } else {
            $stdError = ($stdOutput instanceof ConsoleOutputInterface) ? $stdOutput->getErrorOutput() : $stdOutput;
            $stdError->writeln('Fail');
        }

        return $result->getExitCode();
    }
}
