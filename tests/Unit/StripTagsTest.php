<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StripTagsTest extends TestCase
{
    public function testStripTags(): void
    {
        $str = "1<script>2</script>3";
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = "1<sCript>2</scripT>3";
        $this->assertEquals('13', \Helper::stripTags($str));
        $this->assertEquals('13', \Helper::stripDangerousTags($str));

        $str = "1<script>2";
        $this->assertEquals('12', \Helper::stripTags($str));
        $this->assertEquals('12', \Helper::stripDangerousTags($str));

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
    }
}