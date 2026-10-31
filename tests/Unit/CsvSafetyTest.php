<?php

namespace Tests\Unit;

use App\Support\Csv;
use PHPUnit\Framework\TestCase;

class CsvSafetyTest extends TestCase
{
    public function test_spreadsheet_formula_prefixes_are_exported_as_text(): void
    {
        $values = ['=1+1', '+cmd', '-cmd', '@SUM(A1)', '  =1+1', "\t=1+1", "\r=1+1"];
        $this->assertSame(array_map(fn ($value) => "'".$value, $values), Csv::safeRow($values));
        $this->assertSame(['Manado', 123, null, '08123456'], Csv::safeRow(['Manado', 123, null, '08123456']));
    }
}
