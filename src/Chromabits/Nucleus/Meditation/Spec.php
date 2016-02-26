<?php

/**
 * Copyright 2015, Eduardo Trujillo
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * This file is part of the Nucleus package
 */

namespace Chromabits\Nucleus\Meditation;

use Chromabits\Nucleus\Control\Maybe;
use Chromabits\Nucleus\Data\ArrayList;
use Chromabits\Nucleus\Data\ArrayMap;
use Chromabits\Nucleus\Data\Interfaces\LeftKeyFoldableInterface;
use Chromabits\Nucleus\Data\Iterable;
use Chromabits\Nucleus\Exceptions\CoreException;
use Chromabits\Nucleus\Foundation\BaseObject;
use Chromabits\Nucleus\Meditation\Constraints\AbstractConstraint;
use Chromabits\Nucleus\Meditation\Interfaces\CheckableInterface;
use Chromabits\Nucleus\Support\Std;

/**
 * Class Spec.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Nucleus\Meditation
 */
class Spec extends BaseObject implements CheckableInterface
{
    const ANNOTATION_CONSTRAINTS = 'constraints';

    const ANNOTATION_DEFAULT = 'default';

    const ANNOTATION_REQUIRED = 'required';

    /**
     * @var ArrayMap
     */
    protected $annotations;

    /**
     * Construct an instance of a Spec.
     *
     * @param array[]|AbstractConstraint[] $constraints
     * @param array $defaults
     * @param array $required
     */
    public function __construct(
        array $constraints = [],
        array $defaults = [],
        array $required = []
    ) {
        parent::__construct();

        $annotations = ArrayMap::zero();

        $annotations = ArrayMap::of($constraints)
            ->foldlWithKeys(
                function (ArrayMap $acc, $value, $key) {
                    return $acc->update(
                        $key,
                        function (ArrayMap $field) use ($value) {
                            return $field->insert(
                                static::ANNOTATION_CONSTRAINTS,
                                $value
                            );
                        },
                        ArrayMap::zero()
                    );
                },
                $annotations
            );

        $annotations = ArrayMap::of($defaults)
            ->foldlWithKeys(
                function (ArrayMap $acc, $value, $key) {
                    return $acc->update(
                        $key,
                        function (ArrayMap $field) use ($value) {
                            return $field->insert(
                                static::ANNOTATION_DEFAULT,
                                $value
                            );
                        },
                        ArrayMap::zero()
                    );
                },
                $annotations
            );

        $annotations = ArrayList::of($required)
            ->foldl(
                function (ArrayMap $acc, $value) {
                    return $acc->update(
                        $value,
                        function (ArrayMap $field) {
                            return $field->insert(
                                static::ANNOTATION_REQUIRED,
                                true
                            );
                        },
                        ArrayMap::zero()
                    );
                },
                $annotations
            );

        $this->annotations = $annotations;
    }

    /**
     * Construct an instance of a Spec.
     *
     * @param array $constraints
     * @param array $defaults
     * @param array $required
     *
     * @return static
     */
    public static function define(
        array $constraints = [],
        array $defaults = [],
        array $required = []
    ) {
        return new static($constraints, $defaults, $required);
    }

    /**
     * Check that a certain input passes the spec.
     *
     * @param mixed $input
     *
     * @return SpecResult
     */
    public function check(array $input)
    {
        $missing = [];
        $invalid = [];

        $check = function ($constraint, $key, $value, $input) use (
            &$missing,
            &$invalid
        ) {
            if ($constraint instanceof AbstractConstraint) {
                if (!$constraint->check($value, $input)) {
                    $invalid[$key][] = $constraint;
                }
            } elseif ($constraint instanceof CheckableInterface) {
                $result = $constraint->check($value);

                $missing = Std::concat(
                    $missing,
                    array_map(
                        function ($subKey) use ($key) {
                            return vsprintf('%s.%s', [$key, $subKey]);
                        },
                        $result->getMissing()
                    )
                );

                foreach ($result->getFailed() as $failedField => $constraints) {
                    $fullPath = vsprintf('%s.%s', [$key, $failedField]);

                    if (array_key_exists($fullPath, $invalid)) {
                        $invalid[$fullPath] = array_merge(
                            $invalid[$fullPath],
                            $constraints
                        );
                    } else {
                        $invalid[$fullPath] = $constraints;
                    }
                }
            } else {
                throw new CoreException(
                    vsprintf(
                        'Unexpected constraint type: %s.',
                        [
                            TypeHound::fetch($constraint),
                        ]
                    )
                );
            }
        };

        $inputMap = ArrayMap::of($input);

        $this->annotations->each(
            function ($value, $key) use (
                $check,
                $input,
                $inputMap,
                &$missing
            ) {
                // If a field is required but not present, we should report it.
                if (Maybe::fromMaybe(
                        false,
                        $value->lookup(static::ANNOTATION_REQUIRED)
                    )
                    && $inputMap->member($key) === false
                ) {
                    $missing[] = $key;

                    // There's no point on checking constraints on the field
                    // since it is missing.
                    return;
                } elseif ($inputMap->member($key) === false) {
                    // There's no point on checking constraints on the field
                    // since it is missing.
                    return;
                }

                $fieldValue = Maybe::fromJust($inputMap->lookup($key));

                $this
                    ->getInternalFieldConstraints($key)
                    ->each(
                        function ($constraint) use (
                            $check,
                            $key,
                            $fieldValue,
                            $input
                        ) {
                            $check($constraint, $key, $fieldValue, $input);
                        }
                    );
            }
        );

        if (count($missing) === 0 && count($invalid) === 0) {
            return new SpecResult($missing, $invalid, SpecResult::STATUS_PASS);
        }

        return new SpecResult($missing, $invalid, SpecResult::STATUS_FAIL);
    }

    /**
     * An alias for getFieldConstraints that can be overridden by child classes
     * wishing to inject their own constraints into the checking process.
     *
     * @param string $fieldName
     *
     * @return Iterable
     */
    protected function getInternalFieldConstraints($fieldName)
    {
        return $this->getFieldConstraints($fieldName);
    }

    /**
     * Get all the constraints for a single field.
     *
     * @param string $fieldName
     *
     * @return Iterable
     */
    public function getFieldConstraints($fieldName)
    {
        $maybeConstraints = $this->annotations->lookupIn(
            [$fieldName, static::ANNOTATION_CONSTRAINTS]
        );

        if ($maybeConstraints->isNothing()) {
            return ArrayList::zero();
        }

        $constraints = Maybe::fromJust($maybeConstraints);

        if (is_array($constraints)) {
            return ArrayList::of($constraints);
        } elseif ($constraints instanceof Iterable) {
            return $constraints;
        }

        return ArrayList::of([$constraints]);
    }

    /**
     * @return array|array[]|AbstractConstraint[]
     */
    public function getConstraints()
    {
        return $this->getAnnotation(static::ANNOTATION_CONSTRAINTS)->toArray();
    }

    /**
     * @param string $name
     *
     * @return ArrayMap
     */
    public function getAnnotation($name)
    {
        return $this->annotations
            ->map(
                function (ArrayMap $value) use ($name) {
                    return $value->lookup($name);
                }
            )
            ->filter(
                function (Maybe $value) {
                    return $value->isJust();
                }
            )
            ->map(
                function (Maybe $value) {
                    return Maybe::fromJust($value);
                }
            );
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        return $this->getAnnotation(static::ANNOTATION_DEFAULT)->toArray();
    }

    /**
     * @param string $fieldName
     *
     * @return Maybe
     */
    public function getFieldDefault($fieldName)
    {
        return $this->getFieldAnnotation(
            $fieldName,
            static::ANNOTATION_DEFAULT
        );
    }

    /**
     * Get a specific field annotation.
     *
     * @param string $fieldName
     * @param string $name
     *
     * @return Maybe
     */
    public function getFieldAnnotation($fieldName, $name)
    {
        return $this->annotations->lookupIn([$fieldName, $name]);
    }

    /**
     * @param string $fieldName
     *
     * @return bool
     */
    public function getFieldRequired($fieldName)
    {
        return Maybe::fromMaybe(
            false,
            $this->getFieldAnnotation(
                $fieldName,
                static::ANNOTATION_REQUIRED
            )
        );
    }

    /**
     * @return array
     */
    public function getRequired()
    {
        return $this
            ->getAnnotation(static::ANNOTATION_DEFAULT)
            ->filter(
                function ($value) {
                    return $value;
                }
            )
            ->keys()
            ->toArray();
    }

    /**
     * Get all annotations in this spec.
     *
     * @return ArrayMap
     */
    public function getAnnotations()
    {
        return $this->annotations;
    }

    /**
     * Get all the annotations for a single field.
     *
     * @param string $fieldName
     *
     * @return Maybe
     */
    public function getFieldAnnotations($fieldName)
    {
        return $this->annotations->lookup($fieldName);
    }

    /**
     * Set the constraints for a field.
     *
     * @param string $fieldName
     * @param Iterable|array|AbstractConstraint $constraints
     *
     * @return static
     */
    public function withFieldConstraints($fieldName, $constraints)
    {
        return $this->withFieldAnnotation(
            $fieldName,
            static::ANNOTATION_CONSTRAINTS,
            $constraints
        );
    }

    /**
     * Set the value of an annotation.
     *
     * @param string $fieldName
     * @param string $name
     * @param mixed $value
     *
     * @return static
     */
    public function withFieldAnnotation($fieldName, $name, $value)
    {
        $copy = clone $this;

        $copy->annotations = $this->annotations->update(
            $fieldName,
            function (ArrayMap $fieldAnnotations) use ($name, $value) {
                return $fieldAnnotations->insert($name, $value);
            },
            ArrayMap::zero()
        );

        return $copy;
    }

    /**
     * Set the default value for a field.
     *
     * @param string $fieldName
     * @param mixed $default
     *
     * @return static
     */
    public function withFieldDefault($fieldName, $default)
    {
        return $this->withFieldAnnotation(
            $fieldName,
            static::ANNOTATION_DEFAULT,
            $default
        );
    }

    /**
     * Set a field as required.
     *
     * @param string $fieldName
     * @param bool $value
     *
     * @return static
     */
    public function withFieldRequired($fieldName, $value = true)
    {
        return $this->withFieldAnnotation(
            $fieldName,
            static::ANNOTATION_REQUIRED,
            $value
        );
    }

    /**
     * Get this spec with a map applied as an annotation.
     *
     * @param string $name
     * @param LeftKeyFoldableInterface $map
     *
     * @return mixed
     */
    public function withAnnotation($name, LeftKeyFoldableInterface $map)
    {
        return $map->foldlWithKeys(
            function (self $acc, $value, $fieldName) use ($name) {
                return $acc->withFieldAnnotation($fieldName, $name, $value);
            },
            $this
        );
    }

    /**
     * Get this spec with each value in the provided map as the default of the
     * matching key-field name.
     *
     * @param LeftKeyFoldableInterface $map
     *
     * @return mixed
     */
    public function withDefaults(LeftKeyFoldableInterface $map)
    {
        return $this->withAnnotation(static::ANNOTATION_DEFAULT, $map);
    }
}
