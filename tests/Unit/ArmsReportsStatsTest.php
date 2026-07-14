<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Covers the statistical helpers behind the ArmsReports module (ARMS-13).
 */
class ArmsReportsStatsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The module is not autoloaded while inactive — load the class directly.
        require_once __DIR__.'/../../Modules/ArmsReports/Services/Stats.php';
    }

    public function test_median()
    {
        $this->assertNull(\Modules\ArmsReports\Services\Stats::median([]));
        $this->assertSame(3, \Modules\ArmsReports\Services\Stats::median([3]));
        $this->assertSame(2, \Modules\ArmsReports\Services\Stats::median([1, 2, 3]));
        $this->assertSame(2.5, \Modules\ArmsReports\Services\Stats::median([4, 1, 3, 2]));
    }

    public function test_duration_humanization()
    {
        $this->assertSame('—', \Modules\ArmsReports\Services\Stats::duration(null));
        $this->assertSame('45s', \Modules\ArmsReports\Services\Stats::duration(45));
        $this->assertSame('45m', \Modules\ArmsReports\Services\Stats::duration(2700));
        $this->assertSame('3h 12m', \Modules\ArmsReports\Services\Stats::duration(11520));
        $this->assertSame('2d 4h', \Modules\ArmsReports\Services\Stats::duration(2 * 86400 + 4 * 3600));
    }

    public function test_reply_brackets()
    {
        $this->assertSame('1', \Modules\ArmsReports\Services\Stats::replyBracket(1));
        $this->assertSame('2–3', \Modules\ArmsReports\Services\Stats::replyBracket(2));
        $this->assertSame('2–3', \Modules\ArmsReports\Services\Stats::replyBracket(3));
        $this->assertSame('4–6', \Modules\ArmsReports\Services\Stats::replyBracket(6));
        $this->assertSame('7+', \Modules\ArmsReports\Services\Stats::replyBracket(12));
        $this->assertCount(4, \Modules\ArmsReports\Services\Stats::bracketLabels());
    }
}
