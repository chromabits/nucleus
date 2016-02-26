<?php

namespace Tests\Chromabits\Nucleus\Data;

use ArrayObject;
use Chromabits\Nucleus\Control\Maybe;
use Chromabits\Nucleus\Data\ArrayAccessMap;
use Chromabits\Nucleus\Testing\TestCase;

/**
 * Class ArrayAccessMapTest.
 *
 * @author Eduardo Trujillo <ed@chromabits.com>
 * @package Tests\Chromabits\Nucleus\Data
 */
class ArrayAccessMapTest extends TestCase
{
    public function testConstructor()
    {
        new ArrayAccessMap(new ArrayObject(['great' => 'job']));
    }

    public function testLookup()
    {
        $instance = new ArrayAccessMap(new ArrayObject(['great' => 'job']));

        $this->assertTrue($instance->lookup('omg')->isNothing());
        $this->assertTrue($instance->lookup('great')->isJust());
        $this->assertEquals('job', Maybe::fromJust($instance->lookup('great')));
    }

    public function testInsert()
    {
        $instance = new ArrayAccessMap(new ArrayObject(['great' => 'job']));

        $instance2 = $instance->insert('omg', 'wow');

        $this->assertNotSame($instance, $instance2);
        $this->assertEquals('wow', Maybe::fromJust($instance2->lookup('omg')));
    }

    public function testDelete()
    {
        $instance = new ArrayAccessMap(new ArrayObject(['great' => 'job']));

        $instance2 = $instance->delete('great');

        $this->assertNotSame($instance, $instance2);
        $this->assertTrue($instance2->lookup('great')->isNothing());
    }

    public function testMember()
    {
        $instance = new ArrayAccessMap(new ArrayObject(['great' => 'job']));

        $this->assertTrue($instance->member('great'));
        $this->assertFalse($instance->member('wow'));
    }
}
