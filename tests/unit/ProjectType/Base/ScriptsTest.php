<?php

namespace Cheppers\Robo\Drupal\Tests\Unit\ProjectType\Base;

use Cheppers\Robo\Drupal\ProjectType\Base\Scripts;
use Codeception\Test\Unit;

class ScriptsTest extends Unit
{
    /**
     * @var \Cheppers\Robo\Drupal\Test\UnitTester
     */
    protected $tester;

    /**
     * @var string
     */
    protected $className = Scripts::class;

    //region ::renamePackageComposer()
    public function casesRenamePackageComposer(): array
    {
        return [
            'basic' => [
                [
                    'name' => 'nv/nn',
                    'autoload' => [
                        'psr-4' => [
                            'Ab\Cd\Ef\\' => 'foo',
                            'Nv\Nn\\' => 'src/',
                            'Nv\Nn\Ov\On\\' => 'src/',
                            'Nv\Nn\Tests\\' => 'tests/',
                        ],
                    ],
                    'scripts' => [
                        'a' => 'Nv\Nn\Composer\Scripts::foo',
                        'b' => [
                            'Ab\Cd\Ef\\Foo::bar',
                            'Nv\Nn\Composer\Scripts::bar',
                            'Nv\Nn\Ov\On\Scripts::baz',
                        ],
                    ],
                ],
                [
                    'oldVendorNamespace' => 'Ov',
                    'oldNameNamespace' => 'On',
                    'inputNewVendorNamespace' => 'Nv',
                    'inputNewNameNamespace' => 'Nn',
                    'inputNewVendorMachine' => 'nv',
                    'inputNewNameMachine' => 'nn',
                    'package' => [
                        'name' => '',
                        'autoload' => [
                            'psr-4' => [
                                'Ab\Cd\Ef\\' => 'foo',
                                'Ov\On\\' => 'src/',
                                'Ov\On\Ov\On\\' => 'src/',
                                'Ov\On\Tests\\' => 'tests/',
                            ],
                        ],
                        'scripts' => [
                            'a' => 'Ov\On\Composer\Scripts::foo',
                            'b' => [
                                'Ab\Cd\Ef\\Foo::bar',
                                'Ov\On\Composer\Scripts::bar',
                                'Ov\On\Ov\On\Scripts::baz',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRenamePackageComposer
     */
    public function testRenamePackageComposer(array $expected, array $innerState): void
    {
        $className = $this->className;
        $instance = new $className();
        $properties = $this->initInnerState($innerState);
        $method = $this->getProtectedMethod('renamePackageComposer');
        $method->invokeArgs($instance, []);

        $this->tester->assertEquals($expected, $properties['package']->getValue($instance));
    }
    //endregion

    //region ::getDrupalProfiles()
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
    //endregion

    //region ::ioSelectDrupalProfileChoices()
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
    //endregion

    //region ::validatePackageNameMachine()
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
    //endregion

    //region ::validatePackageNamespace()
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
    //endregion

    //region ::validateDrupalExtensionMachineName()
    public function casesValidateDrupalExtensionMachineNamePass(): array
    {
        return [
            'null' => [null, null, false],
            'empty' => ['', null, false],
            'a' => ['a', 'a', true],
            'a_' => ['a', 'a_', true],
            'a__' => ['a', 'a__', true],
            'a_b' => ['a_b', 'a_b', true],
            'a__b' => ['a_b', 'a__b', true],
            'a___b' => ['a_b', 'a___b', true],
        ];
    }

    /**
     * @dataProvider casesValidateDrupalExtensionMachineNamePass
     */
    public function testValidateDrupalExtensionMachineNamePass(?string $expected, ?string $input, bool $required): void
    {
        $this->tester->assertEquals(
            $expected,
            $this->callProtectedMethod('validateDrupalExtensionMachineName', $input, $required)
        );
    }

    public function casesValidateDrupalExtensionMachineNameFail(): array
    {
        return [
            'empty' => ['', true],
            'space' => [' ', true],
            'starts with 0' => ['0a', false],
            'starts with 1' => ['1a', false],
            'contains .' => ['a.b', false],
            'contains -' => ['a-b', false],
        ];
    }

    /**
     * @dataProvider casesValidateDrupalExtensionMachineNameFail
     */
    public function testValidateDrupalExtensionMachineNameFail(?string $input, bool $required): void
    {
        try {
            $this->callProtectedMethod('validateDrupalExtensionMachineName', $input, $required);
            $this->tester->fail('@todo Error message. Where is the exception?');
        } catch (\InvalidArgumentException $e) {
            $this->tester->assertTrue(true, $e->getMessage());
        }
    }
    //endregion

    //region ::removePostCreateProjectCmdScript()
    public function casesRemovePostCreateProjectCmdScript(): array
    {
        return [
            'string' => [
                [
                    'scripts' => [
                        'foo' => 'true',
                    ],
                ],
                [
                    'package' => [
                        'scripts' => [
                            'post-create-project-cmd' => 'true',
                            'foo' => 'true',
                        ],
                    ],
                ],
            ],
            'array' => [
                [
                    'scripts' => [
                        'foo' => 'true',
                    ],
                ],
                [
                    'package' => [
                        'scripts' => [
                            'post-create-project-cmd' => [
                                'bar' => 'true',
                                'baz' => 'true',
                            ],
                            'foo' => 'true',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesRemovePostCreateProjectCmdScript
     */
    public function testRemovePostCreateProjectCmdScript(array $expected, array $innerState): void
    {
        $className = $this->className;
        $instance = new $className();
        $properties = $this->initInnerState($innerState);
        $method = $this->getProtectedMethod('removePostCreateProjectCmdScript');
        $method->invokeArgs($instance, []);

        $this->tester->assertEquals($expected, $properties['package']->getValue($instance));
    }
    //endregion

    public function casesIoAskQuestion(): array
    {
        return [
            'basic' => [
                implode("\n", [
                    '<question>my-question</question>',
                    '<question>my-description</question>',
                    'Default: "<info>my-default</info>"',
                    ': ',
                ]),
                'my-question',
                'my-default',
                'my-description'
            ],
            'extra' => [
                implode("\n", [
                    '<question>my-question, my-title, 42/100</question>',
                    '<question>my-description</question>',
                    'Default: "<info>my-default</info>"',
                    ': ',
                ]),
                'my-question, {title}, {current}/100',
                'my-default',
                'my-description',
                [
                    '{current}' => 42,
                    '{title}' => 'my-title',
                ],
            ],
        ];
    }

    /**
     * @dataProvider casesIoAskQuestion
     */
    public function testIoAskQuestion(string $expected, ...$args): void
    {
        $className = $this->className;
        $instance = new $className();
        $method = $this->getProtectedMethod('IoAskQuestion');
        $this->assertEquals($expected, $method->invokeArgs($instance, $args));
    }

    protected function callProtectedMethod(string $methodName, ...$args)
    {
        $className = $this->className;
        $instance = new $className();

        return $this
            ->getProtectedMethod($methodName)
            ->invokeArgs($instance, $args);
    }

    protected function getProtectedMethod(string $methodName): \ReflectionMethod
    {
        $reflection = new \ReflectionClass($this->className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @return \ReflectionProperty[]
     */
    protected function getProtectedProperties(array $propertyNames): array
    {
        $properties = [];
        $reflection = new \ReflectionClass($this->className);
        foreach ($propertyNames as $propertyName) {
            $properties[$propertyName] = $reflection->getProperty($propertyName);
            $properties[$propertyName]->setAccessible(true);
        }

        return $properties;
    }

    /**
     * @return \ReflectionProperty[]
     */
    protected function initInnerState(array $innerState): array
    {
        /** @var \ReflectionProperty[] $properties */
        $properties = $this->getProtectedProperties(array_keys($innerState));

        $className = $this->className;
        $instance = new $className();
        foreach ($properties as $propertyName => $property) {
            $property->setValue($instance, $innerState[$propertyName]);
        }

        return $properties;
    }
}
