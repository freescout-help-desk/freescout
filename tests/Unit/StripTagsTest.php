<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test both \Helper::stripTags() and \Helper::stripDangerousTags().
 */
class StripTagsTest extends TestCase
{
    public function testStripTags(): void
    {
        $str = "<div>
1</div> 2";
        $this->assertEquals('1 2', \Helper::stripTags($str));
        $this->assertEquals("<div>
1</div> 2", \Helper::stripDangerousTags($str));

        $str = "<strong>John Smith</strong> started a new conversation #123";
        $this->assertEquals('John Smith started a new conversation #123', \Helper::stripTags($str));
        $this->assertEquals('<strong>John Smith</strong> started a new conversation #123', \Helper::stripDangerousTags($str));

        $str = "1<script>2</script>3";
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = "1<sCript>2</scripT>3";
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = "1<script>2";
        $this->assertEquals('12', \Helper::stripTags($str));
        $this->assertEquals('12', \Helper::stripDangerousTags($str));

        $str = "1<scr<script>ipt>2</script>3";
        $this->assertEquals('1', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = "1<script >2</script>3";
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = "1<script>
2</script>3";
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = "1<script
>2</script>3";
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = "1<script><!--
2
//--></script>3";
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = '1<iframe src="/test/" frameborder="0" class="modal-iframe"></iframe>2';
        $this->assertEquals($str, \Helper::stripDangerousTags($str, ['iframe']));

        $str = '1<input type="hidden" name="bcc[]" value="test@example.org">2';
        $this->assertEquals('12', \Helper::stripTags($str));
        $this->assertEquals('12', \Helper::stripDangerousTags($str));

        $str = '1<input type="hidden" name="bcc[]" value="test@example.org"/>2';
        $this->assertEquals('12', \Helper::stripTags($str));
        $this->assertEquals('12', \Helper::stripDangerousTags($str));

        // Tag split by other tags nested into its name.
        $str = '1<scr<script>ipt>2';
        $this->assertEquals('12', \Helper::stripTags($str));
        $this->assertEquals('12', \Helper::stripDangerousTags($str));

        $str = '1<scr<script>ipt>2</script>3';
        $this->assertEquals('1', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        // Closing tag split as well.
        $str = '1<scr<script>ipt>2</scr</script>ipt>3';
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        // Double nesting.
        $str = '1<scr<scr<script>ipt>ipt>2</script>3';
        $this->assertEquals('1', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        // Harmless tag nested into the tag name.
        $str = '1<scr<b>ipt>alert(1)</script>2';
        $this->assertEquals('1alert(1)2', \Helper::stripTags($str));
        $this->assertEquals('12', \Helper::stripDangerousTags($str));

        // Other unsafe tags split by nested unsafe tags.
        $str = '1<sty<input>le>x</style>2';
        $this->assertEquals('1x2', \Helper::stripTags($str));
        $this->assertEquals('12', \Helper::stripDangerousTags($str));

        $str = '1<inp<button>ut name="bcc[]">2';
        $this->assertEquals('12', \Helper::stripTags($str));
        $this->assertEquals('12', \Helper::stripDangerousTags($str));

        // Safe HTML.
        $str = '<p>ok <b>bold</b> <s>strike</s> a < b > c</p>';
        $this->assertEquals('ok bold strike a < b > c', \Helper::stripTags($str));
        $this->assertEquals($str, \Helper::stripDangerousTags($str));
    }
}