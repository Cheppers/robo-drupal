<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\ProjectType\Base;

use Cheppers\Robo\Drupal\ProjectType\Base\Scripts;
use Codeception\Test\Unit;

class ScriptsTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    protected $class = Scripts::class;

    public function casesGetDrupalProfiles(): array
    {
        $fixturesDir = codecept_data_dir('fixtures/drupal_root');

        return [
            '01 without hidden profiles' => [
                [
                    'a',
                    'b',
                    'c',
                    'minimal',
                    'standard',
                ],
                "$fixturesDir/01",
                false,
            ],
            '01 with hidden profiles' => [
                [
                    'a',
                    'b',
                    'c',
                    'd',
                    'minimal',
                    'standard',
                    'testing',
                ],
                "$fixturesDir/01",
                true,
            ],
        ];
    }

    /**
     * @dataProvider casesGetDrupalProfiles
     */
    public function testGetDrupalProfiles(array $expected, string $drupalRoot, bool $withHiddenOnes): void
    {
        $this->tester->assertEquals(
            $expected,
            array_keys($this->callProtectedMethod('getDrupalProfiles', $drupalRoot, $withHiddenOnes))
        );
    }

    public function casesIoSelectDrupalProfileChoices(): array
    {
        $fixturesDir = codecept_data_dir('fixtures/drupal_root');

        return [
            '01 without hidden profiles' => [
                [
                    'a' => 'Test profile A (a)',
                    'b' => 'Test profile B (b)',
                    'c' => 'Test profile C (c)',
                    'minimal' => 'Minimal (minimal)',
                    'standard' => 'Standard (standard)',
                ],
                "$fixturesDir/01",
                false,
            ],
            '01 with hidden profiles' => [
                [
                    'a' => 'Test profile A (a)',
                    'b' => 'Test profile B (b)',
                    'c' => 'Test profile C (c)',
                    'd' => 'Test profile D (d)',
                    'minimal' => 'Minimal (minimal)',
                    'standard' => 'Standard (standard)',
                    'testing' => 'Testing (testing)',
                ],
                "$fixturesDir/01",
                true,
            ],
        ];
    }

    /**
     * @dataProvider casesIoSelectDrupalProfileChoices
     */
    public function testIoSelectDrupalProfileChoices(array $expected, string $drupalRoot, bool $withHiddenOnes): void
    {
        $this->tester->assertEquals(
            $expected,
            $this->callProtectedMethod('ioSelectDrupalProfileChoices', $drupalRoot, $withHiddenOnes)
        );
    }

    public function casesValidatePackageNameMachinePass(): array
    {
        return [
            'null' => [null, null],
            'a' => ['a', 'a'],
            'a-' => ['a', 'a-'],
            'a--' => ['a', 'a--'],
            'a-b' => ['a-b', 'a-b'],
            'a--b' => ['a-b', 'a--b'],
            'a---b' => ['a-b', 'a---b'],
        ];
    }

    /**
     * @dataProvider casesValidatePackageNameMachinePass
     */
    public function testValidatePackageNameMachinePass(?string $expected, ?string $input): void
    {
        $this->tester->assertEquals(
            $expected,
            $this->callProtectedMethod('validatePackageNameMachine', $input)
        );
    }

    public function casesValidatePackageNameMachineFail(): array
    {
        return [
            'empty' => [''],
            'space' => [' '],
            'starts with 0' => ['0a'],
            'starts with 1' => ['1a'],
            'contains .' => ['a.b'],
            'contains _' => ['a_b'],
        ];
    }

    /**
     * @dataProvider casesValidatePackageNameMachineFail
     */
    public function testValidatePackageNameMachineFail(?string $input): void
    {
        try {
            $this->callProtectedMethod('validatePackageNameMachine', $input);
            $this->tester->fail('@todo Error message. Where is the exception?');
        } catch (\InvalidArgumentException $e) {
            $this->tester->assertTrue(true, $e->getMessage());
        }
    }

    public function casesValidatePackageNameNamespacePass(): array
    {
        return [
            'null' => [null, null],
            'A' => ['A', 'A'],
            'AbCd01' => ['AbCd01', 'AbCd01'],
        ];
    }

    /**
     * @dataProvider casesValidatePackageNameNamespacePass
     */
    public function testValidatePackageNameNamespacePass(?string $expected, ?string $input): void
    {
        $this->tester->assertEquals(
            $expected,
            $this->callProtectedMethod('validatePackageNameNamespace', $input)
        );
    }

    public function casesValidatePackageNameNamespaceFail(): array
    {
        return [
            'a' => ['a'],
            '0a' => ['0a'],
            '1a' => ['1a'],
            'A.bCd' => ['A.bCd'],
            'A-bCd' => ['A-bCd'],
            'A_bCd' => ['A_bCd'],
        ];
    }

    /**
     * @dataProvider casesValidatePackageNameNamespaceFail
     */
    public function testValidatePackageNameNamespaceFail(?string $input): void
    {
        try {
            $this->callProtectedMethod('validatePackageNameNamespace', $input);
            $this->tester->fail('@todo Error message. Where is the exception?');
        } catch (\InvalidArgumentException $e) {
            $this->tester->assertTrue(true, $e->getMessage());
        }
    }

    public function casesValidateDrupalExtensionMachineNamePass(): array
    {
        return [
            'null' => [null, null],
            'a' => ['a', 'a'],
            'a_' => ['a', 'a_'],
            'a__' => ['a', 'a__'],
            'a_b' => ['a_b', 'a_b'],
            'a__b' => ['a_b', 'a__b'],
            'a___b' => ['a_b', 'a___b'],
        ];
    }

    /**
     * @dataProvider casesValidateDrupalExtensionMachineNamePass
     */
    public function testValidateDrupalExtensionMachineNamePass(?string $expected, ?string $input): void
    {
        $this->tester->assertEquals(
            $expected,
            $this->callProtectedMethod('validateDrupalExtensionMachineName', $input)
        );
    }

    public function casesValidateDrupalExtensionMachineNameFail(): array
    {
        return [
            'empty' => [''],
            'space' => [' '],
            'starts with 0' => ['0a'],
            'starts with 1' => ['1a'],
            'contains .' => ['a.b'],
            'contains -' => ['a-b'],
        ];
    }

    /**
     * @dataProvider casesValidateDrupalExtensionMachineNameFail
     */
    public function testValidateDrupalExtensionMachineNameFail($input): void
    {
        try {
            $this->callProtectedMethod('validateDrupalExtensionMachineName', $input);
            $this->tester->fail('@todo Error message. Where is the exception?');
        } catch (\InvalidArgumentException $e) {
            $this->tester->assertTrue(true, $e->getMessage());
        }
    }

    protected function callProtectedMethod(string $methodName, ...$args)
    {
        $class = $this->class;
        $instance = new $class();

        return $this
            ->getProtectedMethod($methodName)
            ->invokeArgs($instance, $args);
    }

    protected function getProtectedMethod(string $methodName): \ReflectionMethod
    {
        $reflection = new \ReflectionClass($this->class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
