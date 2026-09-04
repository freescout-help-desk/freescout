<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CssMinTest extends TestCase
{
    public function testNativeCssCustomPropertiesArePreserved()
    {
        $css = ':root { --sidebar-width: 360px; } .sidebar { width: var(--sidebar-width); }';

        $this->assertSame(
            ':root{--sidebar-width:360px}.sidebar{width:var(--sidebar-width)}',
            \CssMin::minify($css)
        );
    }

    public function testNativeCssCustomPropertiesInsideClampArePreserved()
    {
        $css = '.sidebar { width: clamp(280px, 25vw, var(--sidebar-width)); }';

        $this->assertSame(
            '.sidebar{width:clamp(280px, 25vw, var(--sidebar-width))}',
            \CssMin::minify($css)
        );
    }

    public function testLegacyCssMinVariablesAreStillResolved()
    {
        $css = '@variables { sidebarWidth: 360px; } .sidebar { width: var(sidebarWidth); }';

        $this->assertSame('.sidebar{width:360px}', \CssMin::minify($css));
    }
}
