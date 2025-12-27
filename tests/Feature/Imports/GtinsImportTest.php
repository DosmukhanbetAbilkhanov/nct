<?php

use App\Imports\GtinsImport;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

test('extracts valid GTINs from CSV file', function () {
    $import = new GtinsImport;

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    Excel::import($import, $file);

    $gtins = $import->getGtins();

    // Should extract 5 unique valid GTINs
    expect($gtins)->toHaveCount(5)
        ->and($gtins->toArray())->toContain('1234567890123')
        ->and($gtins->toArray())->toContain('9876543210987')
        ->and($gtins->toArray())->toContain('5551234567890')
        ->and($gtins->toArray())->toContain('7890123456789')
        ->and($gtins->toArray())->toContain('4567890123456');
});

test('filters out invalid GTINs', function () {
    $import = new GtinsImport;

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    Excel::import($import, $file);

    $gtins = $import->getGtins();

    // Invalid GTINs should not be in collection
    expect($gtins->toArray())->not->toContain('123') // too short
        ->and($gtins->toArray())->not->toContain('12345678901234567') // too long
        ->and($gtins->toArray())->not->toContain('abc1234567890') // non-numeric
        ->and($gtins->toArray())->not->toContain('invalid-gtin'); // non-numeric
});

test('removes duplicate GTINs', function () {
    $import = new GtinsImport;

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    Excel::import($import, $file);

    $gtins = $import->getGtins();

    // Count occurrences of duplicate GTIN
    $count = $gtins->filter(fn ($gtin) => $gtin === '1234567890123')->count();

    expect($count)->toBe(1);
});

test('trims whitespace from GTINs', function () {
    $import = new GtinsImport;

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    Excel::import($import, $file);

    $gtins = $import->getGtins();

    // '  1234567890123' should be trimmed and included
    expect($gtins->toArray())->toContain('1234567890123');
});

test('skips empty rows', function () {
    $import = new GtinsImport;

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    Excel::import($import, $file);

    $gtins = $import->getGtins();

    // Empty rows should be skipped, not counted
    expect($gtins)->toHaveCount(5);
});

test('returns empty collection when file has no valid GTINs', function () {
    $import = new GtinsImport;

    $file = new UploadedFile(
        base_path('tests/Fixtures/invalid-gtins.csv'),
        'invalid-gtins.csv',
        'text/csv',
        null,
        true
    );

    Excel::import($import, $file);

    $gtins = $import->getGtins();

    expect($gtins)->toBeEmpty();
});

test('normalizes GTINs', function () {
    $import = new GtinsImport;

    $file = new UploadedFile(
        base_path('tests/Fixtures/sample-gtins.csv'),
        'sample-gtins.csv',
        'text/csv',
        null,
        true
    );

    Excel::import($import, $file);

    $gtins = $import->getGtins();

    // All GTINs should be normalized (13 digits)
    foreach ($gtins as $gtin) {
        expect($gtin)->toHaveLength(13)
            ->and($gtin)->toBeNumeric();
    }
});
