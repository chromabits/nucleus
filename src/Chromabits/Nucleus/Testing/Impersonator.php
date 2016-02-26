<?php

/**
 * Copyright 2015, Eduardo Trujillo
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This file is part of the Nucleus package
 */

namespace Chromabits\Nucleus\Testing;

use Chromabits\Nucleus\Exceptions\LackOfCoffeeException;
use Chromabits\Nucleus\Exceptions\ResolutionException;
use Chromabits\Nucleus\Foundation\BaseObject;
use Chromabits\Nucleus\Meditation\Arguments;
use Chromabits\Nucleus\Meditation\Boa;
use Chromabits\Nucleus\Support\Arr;
use Chromabits\Nucleus\Support\Std;
use Chromabits\Nucleus\Testing\Mocking\CallAndThrowExpectation;
use Chromabits\Nucleus\Testing\Mocking\CallExpectation;
use Closure;
use Mockery;
use Mockery\MockInterface;
use ReflectionClass;
use ReflectionParameter;

/**
 * Class Impersonator.
 *
 * Automatically builds and injects mocks for testing.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Nucleus\Testing
 */
class Impersonator extends BaseObject
{
    /**
     * List of provided mocks.
     *
     * @var array
     */
    protected $provided;

    /**
     * Construct an instance of an Impersonator.
     */
    public function __construct()
    {
        parent::__construct();

        $this->provided = [];
    }

    /**
     * @return Impersonator
     */
    public static function define()
    {
        return new self();
    }

    /**
     * Attempt to build the provided class.
     *
     * Be aware that complex classes might not be resolved automatically.
     * For example, scalar types are currently not supported.
     *
     * @param string $target
     *
     * @throws ResolutionException
     * @return mixed
     */
    public function make($target)
    {
        $arguments = $this->getArgumentTypes($target);

        $resolved = $this->mockArguments($arguments);

        return new $target(...$resolved);
    }

    /**
     * Provide a mock.
     *
     * Here we do some "magic" to attempt to figure out what the mock
     * implements. In order for mock resolution to be fast, relationships
     * between types and mocks are stored on a hash table ($this->provided).
     * This means that if you have objects implementing the same interface or
     * that are instances of the same class, then the last object provided
     * will be the one used.
     *
     * For scenarios where you have two parameters of the same type in the
     * constructor or conflicting interfaces, it is recommended that you build
     * the object manually.
     *
     * @param mixed $mock
     *
     * @throws LackOfCoffeeException
     * @return $this
     */
    public function provide($mock)
    {
        if (is_string($mock) || is_array($mock)) {
            throw new LackOfCoffeeException(
                'A mock cannot be a string or an array.'
            );
        }

        $interfaces = class_implements($mock);
        $parents = class_parents($mock);

        foreach ($interfaces as $interface) {
            $this->provided[$interface] = $mock;
        }

        foreach ($parents as $parent) {
            $this->provided[$parent] = $mock;
        }

        $this->provided[get_class($mock)] = $mock;

        return $this;
    }

    /**
     * A shortcut for building mocks.
     *
     * @param string $type
     * @param Closure|CallExpectation[] $definition
     *
     * @return $this
     */
    public function mock($type, $definition)
    {
        Arguments::define(
            Boa::string(),
            Boa::either(Boa::func(), Boa::arrOf(
                Boa::instance(CallExpectation::class)
            ))
        )->check($type, $definition);

        if (is_array($definition)) {
            $this->provide(static::expectationsToMock(
                $type,
                $definition
            ));

            return $this;
        }

        $this->provide(Mockery::mock($type, $definition));

        return $this;
    }

    /**
     * Build a mock from an array of CallExpectations.
     *
     * @param string $type
     * @param CallExpectation[] $expectations
     *
     * @return MockInterface
     */
    public static function expectationsToMock($type, $expectations)
    {
        return Mockery::mock(
            $type,
            function (MockInterface $mock) use ($expectations) {
                Std::each(
                    function (CallExpectation $expect) use (&$mock) {
                        $mockExpect = $mock
                            ->shouldReceive($expect->getMethodName())
                            ->times($expect->getTimes())
                            ->withArgs($expect->getArguments())
                            ->andReturn($expect->getReturn());

                        if ($expect instanceof CallAndThrowExpectation) {
                            $mockExpect->andThrow(
                                $expect->getExceptionClass(),
                                $expect->getExceptionMessage(),
                                $expect->getExceptionCode()
                            );
                        }
                    },
                    $expectations
                );
            }
        );
    }

    /**
     * Reflect about a class' constructor parameter types.
     *
     * @param mixed $target
     *
     * @throws LackOfCoffeeException
     * @return ReflectionParameter[]
     */
    protected function getArgumentTypes($target)
    {
        $reflect = new ReflectionClass($target);

        if ($reflect->getConstructor() === null) {
            return [];
        }

        return $reflect->getConstructor()->getParameters();
    }

    /**
     * Attempt to automatically mock the arguments of a function.
     *
     * @param ReflectionParameter[] $parameters
     * @param array $overrides
     *
     * @return array
     * @throws ResolutionException
     */
    protected function mockArguments(array $parameters, $overrides = [])
    {
        $resolved = [];

        foreach ($parameters as $parameter) {
            $hint = $parameter->getClass();
            $name = $parameter->getName();

            if (Arr::has($overrides, $name)) {
                $resolved[] = $overrides[$name];

                continue;
            }

            if (is_null($hint)) {
                throw new ResolutionException();
            }

            $mock = $this->resolveMock($hint);

            $resolved[] = $mock;
        }

        return $resolved;
    }

    /**
     * Resolve which mock instance to use.
     *
     * Here we mainly decide whether to use something that was provided to or
     * go ahead an build an empty mock.
     *
     * @param ReflectionClass $type
     *
     * @return MockInterface
     */
    protected function resolveMock(ReflectionClass $type)
    {
        $name = $type->getName();

        if (array_key_exists($name, $this->provided)) {
            return $this->provided[$name];
        }

        return $this->buildMock($type);
    }

    /**
     * Build an empty mock.
     *
     * Override this method if you would like to use a different mocking library
     * or if you would like all your mocks having some properties in common.
     *
     * @param ReflectionClass $type
     *
     * @return MockInterface
     */
    protected function buildMock(ReflectionClass $type)
    {
        return Mockery::mock($type->getName());
    }

    /**
     * Shortcut for constructing an instance of a CallExpectation.
     *
     * @param string $methodName
     * @param array $arguments
     * @param mixed|null $return
     * @param int $times
     *
     * @return CallExpectation
     */
    public static function on(
        $methodName,
        array $arguments,
        $return = null,
        $times = 1
    ) {
        return new CallExpectation($methodName, $arguments, $return, $times);
    }

    /**
     * Shortcut for constructing an instance of a CallAndThrowExpectation.
     *
     * @param string $methodName
     * @param array $arguments
     * @param string $exceptionClass
     * @param string $exceptionMessage
     * @param int $exceptionCode
     *
     * @return CallAndThrowExpectation
     */
    public static function throwOn(
        $methodName,
        array $arguments,
        $exceptionClass,
        $exceptionMessage,
        $exceptionCode
    ) {
        return new CallAndThrowExpectation(
            $methodName,
            $arguments,
            $exceptionClass,
            $exceptionMessage,
            $exceptionCode
        );
    }

    /**
     * Construct an instance of the target class and call the method while
     * injecting any argument that was not provided.
     *
     * @param string $target
     * @param string $methodName
     * @param array $arguments
     *
     * @return mixed
     */
    public function makeAndCall($target, $methodName, array $arguments = [])
    {
        return $this->call($this->make($target), $methodName, $arguments);
    }

    /**
     * Call the method on the target object while injecting any missing
     * arguments using objects defined on this Impersonator instance.
     *
     * This allows one to easily call methods that define dependencies in their
     * arguments rather than just on the constructor of the class they reside
     * in.
     *
     * Impersonator will apply a similar algorithm to make(). Dependencies that
     * are not provided, will be automatically be replaced with a dummy mock.
     * However, in the case of method calls, any provided argument will take
     * precedence over any injection.
     *
     * @param mixed $target
     * @param string $methodName
     * @param array $arguments
     *
     * @return mixed
     */
    public function call($target, $methodName, array $arguments = [])
    {
        $reflection = new ReflectionClass($target);

        $resolved = $this->mockArguments(
            $reflection->getMethod($methodName)->getParameters(),
            $arguments
        );

        return $target->$methodName(...$resolved);
    }
}
