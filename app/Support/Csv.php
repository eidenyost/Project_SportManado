<?php

namespace App\Support;

final class Csv
{
    /** Keep user supplied cells as text when a spreadsheet opens an export. */
    public static function safeRow(array $cells): array
    {
        return array_map(static function ($cell) {
            if (is_string($cell) && preg_match('/^(?:[\s\x00-\x1F]*[=+\-@]|[\t\r\n])/u', $cell)) {
                return "'".$cell;
            }
            return $cell;
        }, $cells);
    }
}
