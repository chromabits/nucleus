<?php

namespace Chromabits\Nucleus\Data;

use ArrayAccess;
use Chromabits\Nucleus\Control\Maybe;
use Chromabits\Nucleus\Foundation\BaseObject;

/**
 * Class ArrayAccessMap
 *
 * A Map backed by an ArrayAccess object.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Chromabits\Nucleus\Data
 */
class ArrayAccessMap extends BaseObject
{
    /**
     * @var ArrayAccess
     */
    protected $value;

    /**
     * Construct an instance of a ArrayAccessMap.
     *
     * @param ArrayAccess $input
     */
    public function __construct(ArrayAccess $input)
    {
        parent::__construct();

        $this->value = $input;
    }

    /**
     * Get the value of the provided key.
     *
     * @param string $key
     *
     * @return Maybe
     */
    public function lookup($key)
    {
        if (!$this->member($key)) {
            return Maybe::nothing();
        }

        return Maybe::just($this->value->offsetGet($key));
    }

    /**
     * Return a new Map of the same type containing the added key.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return static
     */
    public function insert($key, $value)
    {
        $cloned = clone $this->value;

        $cloned->offsetSet($key, $value);

        return new static($cloned);
    }

    /**
     * Return whether or not the map contains the specified key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function member($key)
    {
        return $this->value->offsetExists($key);
    }

    /**
     * Return a new Map of the same type without the specified key.
     *
     * @param string $key
     *
     * @return static
     */
    public function delete($key)
    {
        $cloned = clone $this->value;

        $cloned->offsetUnset($key);

        return new static($cloned);
    }
}
