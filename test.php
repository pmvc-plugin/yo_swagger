<?php
PMVC\Load::plug();
PMVC\addPlugInFolders(['../']);
class YoSwaggerTest extends PHPUnit_Framework_TestCase
{
    function testPlugin()
    {
        ob_start();
        $plug = 'yo_swagger';
        print_r(PMVC\plug($plug));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains($plug,$output);
    }

}
