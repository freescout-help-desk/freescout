<?php
namespace Underscore;

use Underscore\Dummies\DummyDefault;

class ParseTest extends UnderscoreTestCase
{
    ////////////////////////////////////////////////////////////////////
    ////////////////////////// DATA PROVIDERS //////////////////////////
    ////////////////////////////////////////////////////////////////////

    public function provideSwitchers()
    {
        return [
            ['toArray', null, []],
            ['toArray', 15, [15]],
            ['toArray', 'foobar', ['foobar']],
            ['toArray', (object) $this->array, $this->array],
            ['toArray', new DummyDefault(), ['foo', 'bar']],
            ['toString', 15, '15'],
            ['toString', ['foo', 'bar'], '["foo","bar"]'],
            ['toInteger', 'foo', 3],
            ['toInteger', '', 0],
            ['toInteger', '15', 15],
            ['toInteger', [1, 2, 3], 3],
            ['toInteger', [], 0],
            ['toObject', $this->array, (object) $this->array],
            ['toBoolean', '', false],
            ['toBoolean', 'foo', true],
            ['toBoolean', 15, true],
            ['toBoolean', 0, false],
            ['toBoolean', [], false],
        ];
    }

    ////////////////////////////////////////////////////////////////////
    ////////////////////////////// TESTS ///////////////////////////////
    ////////////////////////////////////////////////////////////////////

    public function testCanCreateCsvFiles()
    {
        $csv = Parse::toCSV($this->arrayMulti);
        $matcher = '"bar";"ter"'.PHP_EOL.'"bar";"ter"'.PHP_EOL.'"foo";"ter"';

        $this->assertEquals($matcher, $csv);
    }

    public function testCanUseCustomCsvDelimiter()
    {
        $csv = Parse::toCSV($this->arrayMulti, ',');
        $matcher = '"bar","ter"'.PHP_EOL.'"bar","ter"'.PHP_EOL.'"foo","ter"';

        $this->assertEquals($matcher, $csv);
    }

    public function testCanOutputCsvHeaders()
    {
        $csv = Parse::toCSV($this->arrayMulti, ',', true);
        $matcher = 'foo,bis'.PHP_EOL.'"bar","ter"'.PHP_EOL.'"bar","ter"'.PHP_EOL.'"foo","ter"';

        $this->assertEquals($matcher, $csv);
    }

    public function testCanConvertToJson()
    {
        $json = Parse::toJSON($this->arrayMulti);
        $matcher = '[{"foo":"bar","bis":"ter"},{"foo":"bar","bis":"ter"},{"bar":"foo","bis":"ter"}]';

        $this->assertEquals($matcher, $json);
    }

    public function testCanParseJson()
    {
        $json = Parse::toJSON($this->arrayMulti);
        $array = Parse::fromJSON($json);

        $this->assertEquals($this->arrayMulti, $array);
    }

    public function testCanParseXML()
    {
        $array = Parse::fromXML('<article><name>foo</name><content>bar</content></article>');
        $matcher = ['name' => 'foo', 'content' => 'bar'];

        $this->assertEquals($matcher, $array);
    }

    public function testCanParseCSV()
    {
        $array = Parse::fromCSV("foo;bar;bis\nbar\tfoo\tter");
        $results = [['foo', 'bar', 'bis'], ['bar', 'foo', 'ter']];

        $this->assertEquals($results, $array);
    }

    public function testCanParseCSVWithHeaders($value = '')
    {
        $array = Parse::fromCSV('foo;bar;bis'.PHP_EOL."bar\tfoo\tter", true);
        $results = [['foo' => 'bar', 'bar' => 'foo', 'bis' => 'ter']];

        $this->assertEquals($results, $array);
    }

    ////////////////////////////////////////////////////////////////////
    ///////////////////////// TYPES SWITCHERS //////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * @dataProvider provideSwitchers
     */
    public function testCanSwitchTypes($method, $from, $to)
    {
        $from = Parse::$method($from);

        $this->assertEquals($to, $from);
    }
}
