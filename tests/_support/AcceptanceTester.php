<?php

namespace Cheppers\Robo\Drupal\Test;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    public function assertDirectoryExists(string $path, string $message = null)
    {
        if (!$message) {
            $message = "Directory is exists: '$path'";
        }

        $this->assertTrue(\is_dir($path), $message);
    }
}
