<?php
PMVC\Load::plug();
PMVC\addPlugInFolder('../');
class HelloTest extends PHPUnit_Framework_TestCase
{
    function testPlugin()
    {
        ob_start();
        $plug = 'hello_world';
        print_r(PMVC\plug($plug));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains($plug,$output);
    }

    function testHello()
    {
        $willSay = 'hello, World!';
        ob_start();
        PMVC\plug('hello_world')->say($willSay);
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains($willSay,$output);
    }
}
