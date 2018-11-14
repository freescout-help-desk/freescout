<?php
namespace Underscore\Types;

use Underscore\Underscore;
use Underscore\UnderscoreTestCase;

class StringTest extends UnderscoreTestCase
{
    public $remove = 'foo foo bar foo kal ter son';

    public function provideAccord()
    {
        return [
            [10, '10 things'],
            [1, 'one thing'],
            [0, 'nothing'],
        ];
    }

    public function provideFind()
    {
        return [

            // Simple cases
            [false, 'foo', 'bar'],
            [true, 'foo', 'foo'],
            [true, 'FOO', 'foo', false],
            [false, 'FOO', 'foo', true],
            // Many needles, one haystack
            [true, ['foo', 'bar'], $this->remove],
            [false, ['vlu', 'bla'], $this->remove],
            [true, ['foo', 'vlu'], $this->remove, false, false],
            [false, ['foo', 'vlu'], $this->remove, false, true],
            // Many haystacks, one needle
            [true, 'foo', ['foo', 'bar']],
            [true, 'bar', ['foo', 'bar']],
            [false, 'foo', ['bar', 'kal']],
            [true, 'foo', ['foo', 'foo'], false, false],
            [false, 'foo', ['foo', 'bar'], false, true],
        ];
    }

    // Tests --------------------------------------------------------- /

    public function testCanCreateString()
    {
        $string = Strings::create();

        $this->assertEquals('', $string->obtain());
    }

    public function testDoesntPluralizeTwice()
    {
        $string = new Strings('person');

        $this->assertEquals('people', $string->plural());
        $this->assertEquals('people', $string->plural());
    }

    public function testHasAccessToStrMethods()
    {
        $string1 = Strings::limit('foo', 1);
        $string2 = Underscore::from('foo')->limit(1)->obtain();

        $this->assertEquals('f...', $string1);
        $this->assertEquals('f...', $string2);
    }

    public function testCanRemoveTextFromString()
    {
        $return = Strings::remove($this->remove, 'bar');

        $this->assertEquals('foo foo  foo kal ter son', $return);
    }

    public function testCanRemoveMultipleTextsFromString()
    {
        $return = Strings::remove($this->remove, ['foo', 'son']);

        $this->assertEquals('bar  kal ter', $return);
    }

    public function testCanToggleBetweenTwoStrings()
    {
        $toggle = Strings::toggle('foo', 'foo', 'bar');
        $this->assertEquals('bar', $toggle);
    }

    public function testCannotLooselyToggleBetweenStrings()
    {
        $toggle = Strings::toggle('dei', 'foo', 'bar');
        $this->assertEquals('dei', $toggle);
    }

    public function testCanLooselyToggleBetweenStrings()
    {
        $toggle = Strings::toggle('dei', 'foo', 'bar', true);
        $this->assertEquals('foo', $toggle);
    }

    public function testCanRepeatString()
    {
        $string = Strings::from('foo')->repeat(3)->obtain();

        $this->assertEquals('foofoofoo', $string);
    }

    /**
     * @dataProvider provideFind
     */
    public function testCanFindStringsInStrings(
        $expect,
        $needle,
        $haystack,
        $caseSensitive = false,
        $absoluteFinding = false
    ) {
        $result = Strings::find($haystack, $needle, $caseSensitive, $absoluteFinding);

        $this->assertEquals($expect, $result);
    }

    public function testCanAssertAStringStartsWith()
    {
        $this->assertTrue(Strings::startsWith('foobar', 'foo'));
        $this->assertFalse(Strings::startsWith('barfoo', 'foo'));
    }

    public function testCanAssertAStringEndsWith()
    {
        $this->assertTrue(Strings::endsWith('foobar', 'bar'));
        $this->assertFalse(Strings::endsWith('barfoo', 'bar'));
    }

    public function testStringsCanBeSlugged()
    {
        $this->assertEquals('my-new-post', Strings::slugify('My_nEw\\\/  @ post!!!'));
        $this->assertEquals('my_new_post', Strings::slugify('My nEw post!!!', '_'));
    }

    public function testRandomStringsCanBeGenerated()
    {
        $this->assertEquals(40, strlen(Strings::random(40)));
    }

    /**
     * @dataProvider provideAccord
     */
    public function testCanAccordAStringToItsNumeral($number, $expect)
    {
        $result = Strings::accord($number, '%d things', 'one thing', 'nothing');

        $this->assertEquals($expect, $result);
    }

    public function testCanSliceFromAString()
    {
        $string = Strings::sliceFrom('abcdef', 'c');

        return $this->assertEquals('cdef', $string);
    }

    public function testCanSliceToAString()
    {
        $string = Strings::sliceTo('abcdef', 'c');

        return $this->assertEquals('ab', $string);
    }

    public function testCanSliceAString()
    {
        $string = Strings::slice('abcdef', 'c');

        return $this->assertEquals(['ab', 'cdef'], $string);
    }

    public function testCanUseCorrectOrderForStrReplace()
    {
        $string = Strings::replace('foo', 'foo', 'bar');

        $this->assertEquals('bar', $string);
    }

    public function testCanExplodeString()
    {
        $string = Strings::explode('foo bar foo', ' ');
        $this->assertEquals(['foo', 'bar', 'foo'], $string);

        $string = Strings::explode('foo bar foo', ' ', -1);
        $this->assertEquals(['foo', 'bar'], $string);
    }

    public function testCanGenerateRandomWords()
    {
        $string = Strings::randomStrings($words = 5, $size = 5);

        $result = ($words * $size) + ($words * 1) - 1;
        $this->assertEquals($result, strlen($string));
    }

    public function testCanConvertToSnakeCase()
    {
        $string = Strings::toSnakeCase('thisIsAString');

        $this->assertEquals('this_is_a_string', $string);
    }

    public function testCanConvertToCamelCase()
    {
        $string = Strings::toCamelCase('this_is_a_string');

        $this->assertEquals('thisIsAString', $string);
    }

    public function testCanConvertToPascalCase()
    {
        $string = Strings::toPascalCase('this_is_a_string');

        $this->assertEquals('ThisIsAString', $string);
    }

    public function testCanConvertToLowercase()
    {
        $this->assertEquals('taylor', Strings::lower('TAYLOR'));
        $this->assertEquals('άχιστη', Strings::lower('ΆΧΙΣΤΗ'));
    }

    public function testCanConvertToUppercase()
    {
        $this->assertEquals('TAYLOR', Strings::upper('taylor'));
        $this->assertEquals('ΆΧΙΣΤΗ', Strings::upper('άχιστη'));
    }

    public function testCanConvertToTitleCase()
    {
        $this->assertEquals('Taylor', Strings::title('taylor'));
        $this->assertEquals('Άχιστη', Strings::title('άχιστη'));
    }

    public function testCanLimitStringsByCharacters()
    {
        $this->assertEquals('Tay...', Strings::limit('Taylor', 3));
        $this->assertEquals('Taylor', Strings::limit('Taylor', 6));
        $this->assertEquals('Tay___', Strings::limit('Taylor', 3, '___'));
    }

    public function testCanLimitByWords()
    {
        $this->assertEquals('Taylor...', Strings::words('Taylor Otwell', 1));
        $this->assertEquals('Taylor___', Strings::words('Taylor Otwell', 1, '___'));
        $this->assertEquals('Taylor Otwell', Strings::words('Taylor Otwell', 3));
    }

    public function testCanCheckIfIsIp()
    {
        $this->assertTrue(Strings::isIp('192.168.1.1'));
        $this->assertFalse(Strings::isIp('foobar'));
    }

    public function testCanCheckIfIsEmail()
    {
        $this->assertTrue(Strings::isEmail('foo@bar.com'));
        $this->assertFalse(Strings::isEmail('foobar'));
    }

    public function testCanCheckIfIsUrl()
    {
        $this->assertTrue(Strings::isUrl('http://www.foo.com/'));
        $this->assertFalse(Strings::isUrl('foobar'));
    }

    public function testCanPrependString()
    {
        $this->assertEquals('foobar', Strings::prepend('bar', 'foo'));
    }

    public function testCanAppendString()
    {
        $this->assertEquals('foobar', Strings::append('foo', 'bar'));
    }

    public function testCanGetBaseClass()
    {
        $this->assertEquals('Baz', Strings::baseClass('Foo\Bar\Baz'));
    }
}
