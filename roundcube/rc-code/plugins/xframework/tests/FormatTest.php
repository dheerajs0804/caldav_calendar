<?php
/**
 * Roundcube Plus xframework plugin
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the file LICENSE for details.
 */

require_once(__DIR__ . "/../../xframework/common/Test.php");
require_once(__DIR__ . "/../common/Format.php");

class FormatTest extends XFramework\Test
{
    public function testConstruct()
    {
        $this->format = new XFramework\Format();

        $this->assertEquals($this->format->rcmail->dateFormats['php'], "Y-m-d");
        $this->assertEquals($this->format->rcmail->timeFormats['php'], "H:i");
        $this->assertEquals($this->format->rcmail->dmFormats['php'], "m-d");
    }

    public function testGetDateFormat()
    {
        $this->assertEquals($this->format->getDateFormat("php"), "Y-m-d");
        $this->assertEquals($this->format->getDateFormat("moment"),  "YYYY-MM-DD");
        $this->assertEquals($this->format->getDateFormat("datepicker"), "yy-mm-dd");
    }

    public function testGetTimeFormat()
    {
        $this->assertEquals($this->format->getTimeFormat("php"), "H:i");
        $this->assertEquals($this->format->getTimeFormat("moment"),  "HH:mm");
    }

    public function testFormatCurrency()
    {
        $this->assertEquals($this->format->formatCurrency("9.99"), "9.99");
        $this->assertEquals($this->format->formatCurrency("9.99", false, "sq_AL"), "9,99");
    }

    public function testFormatNumber()
    {
        $this->assertEquals($this->format->formatNumber("9.99", false, "sq_AL"), "9,99");
    }

    public function testGetSeparators()
    {
        $this->assertEquals($this->format->getSeparators(), [0 => '.', 1 => ',', 2 => '.', 3 => ',']);
        $this->assertEquals($this->format->getSeparators("sq_AL"), [0 => ',', 1 => ' ', 2 => ',', 3 => ' ']);
    }

    public function testFloatToString()
    {
        $this->assertEquals($this->format->floatToString(9.9), "9.9");
        $this->assertEquals($this->format->floatToString("string"), "string");
    }

    public function testLoadFormats()
    {

    }
}