<?php
namespace Underscore\Types;

use Underscore\Underscore;
use Underscore\UnderscoreTestCase;

class ArraysTest extends UnderscoreTestCase
{
    // Tests --------------------------------------------------------- /

    public function testCanCreateArray()
    {
        $array = Arrays::create();

        $this->assertEquals([], $array->obtain());
    }

    public function testCanUseClassDirectly()
    {
        $under = Arrays::get($this->array, 'foo');

        $this->assertEquals('bar', $under);
    }

    public function testCanCreateChainableObject()
    {
        $under = Underscore::from($this->arrayNumbers);
        $under = $under->get(1);

        $this->assertEquals(2, $under);
    }

    public function testCanGetKeys()
    {
        $array = Arrays::keys($this->array);

        $this->assertEquals(['foo', 'bis'], $array);
    }

    public function testCanGetValues()
    {
        $array = Arrays::values($this->array);

        $this->assertEquals(['bar', 'ter'], $array);
    }

    public function testCanSetValues()
    {
        $array = ['foo' => ['foo' => 'bar'], 'bar' => 'bis'];
        $array = Arrays::set($array, 'foo.bar.bis', 'ter');

        $this->assertEquals('ter', $array['foo']['bar']['bis']);
        $this->assertArrayHasKey('bar', $array);
    }

    public function testCanRemoveValues()
    {
        $array = Arrays::remove($this->arrayMulti, '0.foo');
        $matcher = $this->arrayMulti;
        unset($matcher[0]['foo']);

        $this->assertEquals($matcher, $array);
    }

    public function testCanRemoveMultipleValues()
    {
        $array = Arrays::remove($this->arrayMulti, ['0.foo', '1.foo']);
        $matcher = $this->arrayMulti;
        unset($matcher[0]['foo']);
        unset($matcher[1]['foo']);

        $this->assertEquals($matcher, $array);
    }

    public function testCanReturnAnArrayWithoutSomeValues()
    {
        $array = ['foo', 'foo', 'bar', 'bis', 'bar', 'bis', 'ter'];
        $array = Arrays::without($array, 'foo', 'bar');

        $this->assertEquals([3 => 'bis', 5 => 'bis', 6 => 'ter'], $array);
        $this->assertNotContains('foo', Arrays::without($array, 'foo', 'bar'));
        $this->assertNotContains('bar', Arrays::without($array, 'foo', 'bar'));
        // new use case
        $exclusion = ['foo', 'bar'];
        $this->assertNotContains('foo', Arrays::without($array, $exclusion));
        $this->assertNotContains('bar', Arrays::without($array, $exclusion));
    }

    public function testCanGetSumOfArray()
    {
        $array = Arrays::sum([1, 2, 3]);

        $this->assertEquals(6, $array);
    }

    public function testCanGetcountArray()
    {
        $array = Arrays::size([1, 2, 3]);

        $this->assertEquals(3, $array);
    }

    public function testCanSeeIfArrayContainsValue()
    {
        $true = Arrays::contains([1, 2, 3], 2);
        $false = Arrays::contains([1, 2, 3], 5);

        $this->assertTrue($true);
        $this->assertFalse($false);
    }

    public function testCanCheckIfHasValue()
    {
        $under = Arrays::has($this->array, 'foo');

        $this->assertTrue($under);
    }

    public function testCanGetValueFromArray()
    {
        $array = ['foo' => ['bar' => 'bis']];
        $under = Arrays::get($array, 'foo.bar');

        $this->assertEquals('bis', $under);
    }

    public function testCantConflictWithNativeFunctions()
    {
        $array = ['foo' => ['bar' => 'bis']];
        $under = Arrays::get($array, 'ter', 'str_replace');

        $this->assertEquals('str_replace', $under);
    }

    public function testCanFallbackClosure()
    {
        $array = ['foo' => ['bar' => 'bis']];
        $under = Arrays::get($array, 'ter', function () {
            return 'closure';
        });

        $this->assertEquals('closure', $under);
    }

    public function testCanDoSomethingAtEachValue()
    {
        $closure = function ($value, $key) {
            echo $key.':'.$value.':';
        };

        Arrays::at($this->array, $closure);
        $result = 'foo:bar:bis:ter:';

        $this->expectOutputString($result);
    }

    public function testCanActOnEachValueFromArray()
    {
        $closure = function ($value, $key) {
            return $key.':'.$value;
        };

        $under = Arrays::each($this->array, $closure);
        $result = ['foo' => 'foo:bar', 'bis' => 'bis:ter'];

        $this->assertEquals($result, $under);
    }

    public function testCanFindAValueInAnArray()
    {
        $under = Arrays::find($this->arrayNumbers, function ($value) {
            return $value % 2 === 0;
        });
        $this->assertEquals(2, $under);

        $unfound = Arrays::find($this->arrayNumbers, function ($value) {
            return $value === 5;
        });
        $this->assertNull($unfound);
    }

    public function testCanFilterValuesFromAnArray()
    {
        $under = Arrays::filter($this->arrayNumbers, function ($value) {
            return $value % 2 !== 0;
        });

        $this->assertEquals([0 => 1, 2 => 3], $under);
    }

    public function testCanFilterRejectedValuesFromAnArray()
    {
        $under = Arrays::reject($this->arrayNumbers, function ($value) {
            return $value % 2 !== 0;
        });

        $this->assertEquals([1 => 2], $under);
    }

    public function testCanMatchAnArrayContent()
    {
        $under = Arrays::matches($this->arrayNumbers, function ($value) {
            return is_int($value);
        });

        $this->assertTrue($under);
    }

    public function testCanMatchPathOfAnArrayContent()
    {
        $under = Arrays::matchesAny($this->arrayNumbers, function ($value) {
            return $value === 2;
        });

        $this->assertTrue($under);
    }

    public function testCanInvokeFunctionsOnValues()
    {
        $array = ['   foo  ', '   bar   '];
        $array = Arrays::invoke($array, 'trim');

        $this->assertEquals(['foo', 'bar'], $array);
    }

    public function testCanInvokeFunctionsOnValuesWithSingleArgument()
    {
        $array = ['_____foo', '____bar   '];
        $array = Arrays::invoke($array, 'trim', ' _');

        $this->assertEquals(['foo', 'bar'], $array);
    }

    public function testCanInvokeFunctionsWithDifferentArguments()
    {
        $array = ['_____foo  ', '__bar   '];
        $array = Arrays::invoke($array, 'trim', ['_', ' ']);

        $this->assertEquals(['foo  ', '__bar'], $array);
    }

    public function testCanPluckColumns()
    {
        $under = Arrays::pluck($this->arrayMulti, 'foo');
        $matcher = ['bar', 'bar', null];

        $this->assertEquals($matcher, $under);
    }

    public function testCanCalculateAverageValue()
    {
        $average1 = [5, 10, 15, 20];
        $average2 = ['foo', 'b', 'ar'];
        $average3 = [['lol'], 10, 20];

        $average1 = Arrays::average($average1);
        $average2 = Arrays::average($average2);
        $average3 = Arrays::average($average3);

        $this->assertEquals(13, $average1);
        $this->assertEquals(0, $average2);
        $this->assertEquals(10, $average3);
    }

    public function testCanGetFirstValue()
    {
        $under1 = Arrays::first($this->array);
        $under2 = Arrays::first($this->arrayNumbers, 2);

        $this->assertEquals('bar', $under1);
        $this->assertEquals([1, 2], $under2);
    }

    public function testCanGetLastValue()
    {
        $under = Arrays::last($this->array);

        $this->assertEquals('ter', $under);
    }

    public function testCanGetLastElements()
    {
        $under = Arrays::last($this->arrayNumbers, 2);

        $this->assertEquals([2, 3], $under);
    }

    public function testCanXInitialElements()
    {
        $under = Arrays::initial($this->arrayNumbers, 1);

        $this->assertEquals([1, 2], $under);
    }

    public function testCanGetRestFromArray()
    {
        $under = Arrays::rest($this->arrayNumbers, 1);

        $this->assertEquals([2, 3], $under);
    }

    public function testCanCleanArray()
    {
        $array = [false, true, 0, 1, 'full', ''];
        $array = Arrays::clean($array);

        $this->assertEquals([1 => true, 3 => 1, 4 => 'full'], $array);
    }

    public function testCanGetMaxValueFromAnArray()
    {
        $under = Arrays::max($this->arrayNumbers);

        $this->assertEquals(3, $under);
    }

    public function testCanGetMaxValueFromAnArrayWithClosure()
    {
        $under = Arrays::max($this->arrayNumbers, function ($value) {
            return $value * -1;
        });

        $this->assertEquals(-1, $under);
    }

    public function testCanGetMinValueFromAnArray()
    {
        $under = Arrays::min($this->arrayNumbers);

        $this->assertEquals(1, $under);
    }

    public function testCanGetMinValueFromAnArrayWithClosure()
    {
        $under = Arrays::min($this->arrayNumbers, function ($value) {
            return $value * -1;
        });

        $this->assertEquals(-3, $under);
    }

    public function testCanSortKeys()
    {
        $under = Arrays::sortKeys(['z' => 0, 'b' => 1, 'r' => 2]);
        $this->assertEquals(['b' => 1, 'r' => 2, 'z' => 0], $under);

        $under = Arrays::sortKeys(['z' => 0, 'b' => 1, 'r' => 2], 'desc');
        $this->assertEquals(['z' => 0, 'r' => 2, 'b' => 1], $under);
    }

    public function testCanSortValues()
    {
        $under = Arrays::sort([5, 3, 1, 2, 4], null, 'desc');
        $this->assertEquals([5, 4, 3, 2, 1], $under);

        $under = Arrays::sort(range(1, 5), function ($value) {
            return $value % 2 === 0;
        });
        $this->assertEquals([1, 3, 5, 2, 4], $under);
    }

    public function testCanGroupValues()
    {
        $under = Arrays::group(range(1, 5), function ($value) {
            return $value % 2 === 0;
        });
        $matcher = [
            [1, 3, 5],
            [2, 4],
        ];

        $this->assertEquals($matcher, $under);
    }

    public function testCanCreateFromRange()
    {
        $range = Arrays::range(5);
        $this->assertEquals([1, 2, 3, 4, 5], $range);

        $range = Arrays::range(-2, 2);
        $this->assertEquals([-2, -1, 0, 1, 2], $range);

        $range = Arrays::range(1, 10, 2);
        $this->assertEquals([1, 3, 5, 7, 9], $range);
    }

    public function testCantChainRange()
    {
        $this->setExpectedException('Exception');

        Arrays::from($this->arrayNumbers)->range(5);
    }

    public function testCanCreateFromRepeat()
    {
        $repeat = Arrays::repeat('foo', 3);

        $this->assertEquals(['foo', 'foo', 'foo'], $repeat);
    }

    public function testCanMergeArrays()
    {
        $array = Arrays::merge($this->array, ['foo' => 3], ['kal' => 'mon']);

        $this->assertEquals(['foo' => 3, 'bis' => 'ter', 'kal' => 'mon'], $array);
    }

    public function testCanGetRandomValue()
    {
        $array = Arrays::random($this->array);

        $this->assertContains($array, $this->array);
    }

    public function testCanGetSeveralRandomValue()
    {
        $array = Arrays::random($this->arrayNumbers, 2);
        foreach ($array as $a) {
            $this->assertContains($a, $this->arrayNumbers);
        }
    }

    public function testCanSearchForAValue()
    {
        $array = Arrays::search($this->array, 'ter');

        $this->assertEquals('bis', $array);
    }

    public function testCanDiffBetweenArrays()
    {
        $array = Arrays::diff($this->array, ['foo' => 'bar', 'ter' => 'kal']);
        $chain = Arrays::from($this->array)->diff(['foo' => 'bar', 'ter' => 'kal']);

        $this->assertEquals(['bis' => 'ter'], $array);
        $this->assertEquals(['bis' => 'ter'], $chain->obtain());
    }

    public function testCanRemoveFirstValueFromAnArray()
    {
        $array = Arrays::removeFirst($this->array);

        $this->assertEquals(['bis' => 'ter'], $array);
    }

    public function testCanRemoveLasttValueFromAnArray()
    {
        $array = Arrays::removeLast($this->array);

        $this->assertEquals(['foo' => 'bar'], $array);
    }

    public function testCanImplodeAnArray()
    {
        $array = Arrays::implode($this->array, ',');

        $this->assertEquals('bar,ter', $array);
    }

    public function testCanFlattenArraysToDotNotation()
    {
        $array = [
            'foo' => 'bar',
            'kal' => [
                'foo' => [
                    'bar',
                    'ter',
                ],
            ],
        ];
        $flattened = [
            'foo' => 'bar',
            'kal.foo.0' => 'bar',
            'kal.foo.1' => 'ter',
        ];

        $flatten = Arrays::flatten($array);

        $this->assertEquals($flatten, $flattened);
    }

    public function testCanFlattenArraysToCustomNotation()
    {
        $array = [
            'foo' => 'bar',
            'kal' => [
                'foo' => [
                    'bar',
                    'ter',
                ],
            ],
        ];
        $flattened = [
            'foo' => 'bar',
            'kal/foo/0' => 'bar',
            'kal/foo/1' => 'ter',
        ];

        $flatten = Arrays::flatten($array, '/');

        $this->assertEquals($flatten, $flattened);
    }

    public function testCanReplaceValues()
    {
        $array = Arrays::replace($this->array, 'foo', 'notfoo', 'notbar');
        $matcher = ['notfoo' => 'notbar', 'bis' => 'ter'];

        $this->assertEquals($matcher, $array);
    }

    public function testCanPrependValuesToArrays()
    {
        $array = Arrays::prepend($this->array, 'foo');
        $matcher = [0 => 'foo', 'foo' => 'bar', 'bis' => 'ter'];

        $this->assertEquals($matcher, $array);
    }

    public function testCanAppendValuesToArrays()
    {
        $array = Arrays::append($this->array, 'foo');
        $matcher = ['foo' => 'bar', 'bis' => 'ter', 0 => 'foo'];

        $this->assertEquals($matcher, $array);
    }

    public function testCanReplaceValuesInArrays()
    {
        $array = $this->array;
        $array = Arrays::replaceValue($array, 'bar', 'replaced');

        $this->assertEquals('replaced', $array['foo']);
    }

    public function testCanReplaceKeysInArray()
    {
        $array = $this->array;
        $array = Arrays::replaceKeys($array, ['bar', 'ter']);

        $this->assertEquals(['bar' => 'bar', 'ter' => 'ter'], $array);
    }

    public function testCanGetIntersectionOfTwoArrays()
    {
        $a = ['foo', 'bar'];
        $b = ['bar', 'baz'];
        $array = Arrays::intersection($a, $b);

        $this->assertEquals(['bar'], $array);
    }

    public function testIntersectsBooleanFlag()
    {
        $a = ['foo', 'bar'];
        $b = ['bar', 'baz'];

        $this->assertTrue(Arrays::intersects($a, $b));

        $a = 'bar';
        $this->assertTrue(Arrays::intersects($a, $b));
        $a = 'foo';
        $this->assertFalse(Arrays::intersects($a, $b));
    }

    public function testFilterBy()
    {
        $a = [
            ['id' => 123, 'name' => 'foo', 'group' => 'primary', 'value' => 123456, 'when' => '2014-01-01'],
            ['id' => 456, 'name' => 'bar', 'group' => 'primary', 'value' => 1468, 'when' => '2014-07-15'],
            ['id' => 499, 'name' => 'baz', 'group' => 'secondary', 'value' => 2365, 'when' => '2014-08-23'],
            ['id' => 789, 'name' => 'ter', 'group' => 'primary', 'value' => 2468, 'when' => '2010-03-01'],
            ['id' => 888,'name' => 'qux','value' => 6868,'when' => '2015-01-01'],
            ['id' => 999,'name' => 'flux','group' => null,'value' => 6868,'when' => '2015-01-01'],
        ];

        $b = Arrays::filterBy($a, 'name', 'baz');
        $this->assertCount(1, $b);
        $this->assertEquals(2365, $b[0]['value']);

        $b = Arrays::filterBy($a, 'name', ['baz']);
        $this->assertCount(1, $b);
        $this->assertEquals(2365, $b[0]['value']);

        $c = Arrays::filterBy($a, 'value', 2468);
        $this->assertCount(1, $c);
        $this->assertEquals('primary', $c[0]['group']);

        $d = Arrays::filterBy($a, 'group', 'primary');
        $this->assertCount(3, $d);

        $e = Arrays::filterBy($a, 'value', 2000, 'lt');
        $this->assertCount(1, $e);
        $this->assertEquals(1468, $e[0]['value']);

        $e = Arrays::filterBy($a, 'value', [2468, 2365], 'contains');
        $this->assertCount(2, $e);
        $this->assertContains(2468, Arrays::pluck($e, 'value'));
        $this->assertNotContains(1468, Arrays::pluck($e, 'value'));

        $e = Arrays::filterBy($a, 'when', '2014-02-01', 'older');
        $this->assertCount(2, $e);
        $this->assertContains('2014-01-01', Arrays::pluck($e, 'when'));
        $this->assertContains('2010-03-01', Arrays::pluck($e, 'when'));
        $this->assertNotContains('2014-08-23', Arrays::pluck($e, 'when'));

        $f = Arrays::filterBy($a, 'group', 'primary', 'ne');
        $this->assertCount(3, $f, 'Count should pick up groups which are explicitly set as null AND those which don\'t have the property at all');
        $this->assertContains('qux', Arrays::pluck($f, 'name'));
        $this->assertContains('flux', Arrays::pluck($f, 'name'));
    }

    public function testFindBy()
    {
        $a = [
            ['id' => 123, 'name' => 'foo', 'group' => 'primary', 'value' => 123456],
            ['id' => 456, 'name' => 'bar', 'group' => 'primary', 'value' => 1468],
            ['id' => 499, 'name' => 'baz', 'group' => 'secondary', 'value' => 2365],
            ['id' => 789, 'name' => 'ter', 'group' => 'primary', 'value' => 2468],
        ];

        $b = Arrays::findBy($a, 'name', 'baz');
        $this->assertTrue(is_array($b));
        $this->assertCount(4, $b); // this is counting the number of keys in the array (id,name,group,value)
        $this->assertEquals(2365, $b['value']);
        $this->assertArrayHasKey('name', $b);
        $this->assertArrayHasKey('group', $b);
        $this->assertArrayHasKey('value', $b);

        $c = Arrays::findBy($a, 'value', 2468);
        $this->assertTrue(is_array($c));
        $this->assertCount(4, $c);
        $this->assertEquals('primary', $c['group']);

        $d = Arrays::findBy($a, 'group', 'primary');
        $this->assertTrue(is_array($d));
        $this->assertCount(4, $d);
        $this->assertEquals('foo', $d['name']);

        $e = Arrays::findBy($a, 'value', 2000, 'lt');
        $this->assertTrue(is_array($e));
        $this->assertCount(4, $e);
        $this->assertEquals(1468, $e['value']);
    }

    public function testRemoveValue()
    {
        // numeric array
        $a = ['foo', 'bar', 'baz'];
        $this->assertCount(2, Arrays::removeValue($a, 'bar'));
        $this->assertNotContains('bar', Arrays::removeValue($a, 'bar'));
        $this->assertContains('foo', Arrays::removeValue($a, 'bar'));
        $this->assertContains('baz', Arrays::removeValue($a, 'bar'));
        // associative array
        $a = [
            'foo' => 'bar',
            'faz' => 'ter',
            'one' => 'two',
        ];
        $this->assertCount(2, Arrays::removeValue($a, 'bar'));
        $this->assertNotContains('bar', array_values(Arrays::removeValue($a, 'bar')));
        $this->assertContains('ter', array_values(Arrays::removeValue($a, 'bar')));
        $this->assertContains('two', array_values(Arrays::removeValue($a, 'bar')));
    }
}
