<?php

namespace Tests\Unit;

use App\Services\Import\ThaiDateParser;
use PHPUnit\Framework\TestCase;

class ThaiDateParserTest extends TestCase
{
    public function test_it_parses_various_thai_date_formats(): void
    {
        // 4-digit BE
        $this->assertEquals('2026-05-07', ThaiDateParser::parse('07 พ.ค. 2569'));
        $this->assertEquals('2026-05-15', ThaiDateParser::parse('15 พ.ค. 2569'));

        // 2-digit BE
        $this->assertEquals('2014-04-08', ThaiDateParser::parse('08 เม.ย. 57'));
        $this->assertEquals('2008-09-23', ThaiDateParser::parse('23 ก.ย. 51'));

        // Standard formats
        $this->assertEquals('2007-04-25', ThaiDateParser::parse('25/04/2550'));
        $this->assertEquals('2007-10-01', ThaiDateParser::parse('01/10/2550'));

        // Slash with 2-digit BE
        $this->assertEquals('2007-04-25', ThaiDateParser::parse('25/4/50'));

        // Null / Empty cases
        $this->assertNull(ThaiDateParser::parse(''));
        $this->assertNull(ThaiDateParser::parse('-'));
        $this->assertNull(ThaiDateParser::parse(null));
    }
}
