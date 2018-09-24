<?php

namespace EventyTests\Unit;

use EventyTests\DummyClass;
use PHPUnit\Framework\TestCase;
use TorMorten\Eventy\Events;

class FilterTest extends TestCase
{
    public function setUp()
    {
        $this->events = new Events();
    }

    /**
     * @test
     */
    public function it_can_hook_a_callable()
    {
        $this->events->addFilter('my_awesome_filter', function ($value) {
            return $value.' Filtered';
        });
        $this->assertEquals($this->events->filter('my_awesome_filter', 'Value Was'), 'Value Was Filtered');
    }

    /**
     * @test
     */
    public function it_can_hook_an_array()
    {
        $class = new class('DummyClass') {
            public function filter($value)
            {
                return $value.' Filtered';
            }
        };
        $this->events->addFilter('my_amazing_filter', [$class, 'filter']);

        $this->assertEquals($this->events->filter('my_amazing_filter', 'Value Was'), 'Value Was Filtered');
    }

    /**
     * @test
     */
    public function a_hook_fires_even_if_there_are_two_listeners_with_the_same_priority()
    {
        $this->events->addFilter('my_great_filter', function ($value) {
            return $value.' Once';
        }, 20);

        $this->events->addFilter('my_great_filter', function ($value) {
            return $value.' And Twice';
        }, 20);

        $this->assertEquals($this->events->filter('my_great_filter', 'I Was Filtered'), 'I Was Filtered Once And Twice');
    }

    /**
     * @test
     */
    public function listeners_are_sorted_by_priority()
    {
        $this->events->addFilter('my_awesome_filter', function ($value) {
            return $value.' Filtered';
        }, 20);

        $this->events->addFilter('my_awesome_filter', function ($value) {
            return $value.' Filtered';
        }, 8);

        $this->events->addFilter('my_awesome_filter', function ($value) {
            return $value.' Filtered';
        }, 12);

        $this->events->addFilter('my_awesome_filter', function ($value) {
            return $value.' Filtered';
        }, 40);

        $this->assertEquals($this->events->getFilter()->getListeners()->values()[0]['priority'], 8);
        $this->assertEquals($this->events->getFilter()->getListeners()->values()[1]['priority'], 12);
        $this->assertEquals($this->events->getFilter()->getListeners()->values()[2]['priority'], 20);
        $this->assertEquals($this->events->getFilter()->getListeners()->values()[3]['priority'], 40);
    }

    /**
     * @test
     */
    public function a_single_filter_is_removed()
    {
        // check the collection has 1 item
        $this->events->addFilter('my_awesome_filter', 'my_awesome_function', 30, 1);
        $this->assertEquals($this->events->getFilter()->getListeners()->where('hook', 'my_awesome_filter')->count(), 1);

        // check removeFilter removes the filter
        $this->events->removeFilter('my_awesome_filter', 'my_awesome_function', 30);
        $this->assertEquals($this->events->getFilter()->getListeners()->where('hook', 'my_awesome_filter')->count(), 0);
    }

    /**
     * @test
     */
    public function all_filters_removed()
    {
        // check the collection has 3 items before checking they're removed
        $this->events->addFilter('my_awesome_filter', 'my_awesome_function', 30, 1);
        $this->events->addFilter('my_awesome_filter', 'my_other_awesome_function', 30, 1);
        $this->events->addFilter('my_awesome_filter_2', 'my_awesome_function_2', 30, 1);
        $this->assertEquals($this->events->getFilter()->getListeners()->count(), 3);

        // check removeFilter removes the filter
        $this->events->removeAllFilters();
        $this->assertEquals($this->events->getFilter()->getListeners()->count(), 0);
    }

    /**
     * @test
     */
    public function all_filters_removed_by_hook()
    {
        // check the collection has 1 item
        $this->events->addFilter('my_awesome_filter', 'my_awesome_function', 30, 1);
        $this->events->addFilter('my_awesome_filter', 'my_other_awesome_function', 30, 1);
        $this->events->addFilter('my_awesome_filter_2', 'my_awesome_function', 30, 1);
        $this->assertEquals($this->events->getFilter()->getListeners()->count(), 3);

        // check removeFilter removes the filter
        $this->events->removeAllFilters('my_awesome_filter');
        $this->assertEquals($this->events->getFilter()->getListeners()->where('hook', 'my_awesome_filter')->count(), 0);
        // check that the other filter wasn't removed
        $this->assertEquals($this->events->getFilter()->getListeners()->where('hook', 'my_awesome_filter_2')->count(), 1);
    }
}
