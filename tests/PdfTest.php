<?php

use Spatie\Browsershot\Browsershot;
use Spatie\PdfToText\Pdf;

it('can save a pdf by using the pdf extension', function () {
    $targetPath = __DIR__.'/temp/testPdf.pdf';

    Browsershot::url('https://example.com')
        ->save($targetPath);

    expect($targetPath)->toBeFile();

    expect(mime_content_type($targetPath))->toEqual('application/pdf');
});

it('can save a highly customized pdf', function () {
    $targetPath = __DIR__.'/temp/customPdf.pdf';

    Browsershot::url('https://example.com')
        ->hideBrowserHeaderAndFooter()
        ->showBackground()
        ->landscape()
        ->margins(5, 25, 5, 25)
        ->pages('1')
        ->savePdf($targetPath);

    expect($targetPath)->toBeFile();

    expect(mime_content_type($targetPath))->toEqual('application/pdf');
});

it('can save a highly customized pdf when using pipe', function () {
    $targetPath = __DIR__.'/temp/customPdf.pdf';

    Browsershot::url('https://example.com')
        ->usePipe()
        ->hideBrowserHeaderAndFooter()
        ->showBackground()
        ->landscape()
        ->margins(5, 25, 5, 25)
        ->pages('1')
        ->savePdf($targetPath);

    expect($targetPath)->toBeFile();

    expect(mime_content_type($targetPath))->toEqual('application/pdf');
});

it('can save a pdf with the taggedPdf option', function () {
    $targetPath = __DIR__.'/temp/customPdf.pdf';

    Browsershot::url('https://example.com')
        ->taggedPdf()
        ->pages('1')
        ->savePdf($targetPath);

    expect($targetPath)->toBeFile();

    expect(mime_content_type($targetPath))->toEqual('application/pdf');
});

it('can return a pdf as base 64', function () {
    $base64 = Browsershot::url('https://example.com')
        ->base64pdf();

    expect(is_string($base64))->toBeTrue();
});

it('can return a pdf', function () {
    $binPath = PHP_OS === 'Linux' ? '/usr/bin/pdftotext' : '/opt/homebrew/bin/pdftotext';
    $targetPath = __DIR__.'/temp/testPdf.pdf';

    $pdf = Browsershot::url('https://example.com')
        ->pdf();
    file_put_contents($targetPath, $pdf);

    expect(Pdf::getText($targetPath, $binPath))->toContain('Example Domain');
});

it('can write options to a file and generate a pdf', function () {
    $targetPath = __DIR__.'/temp/testPdf.pdf';

    Browsershot::url('https://example.com')
        ->writeOptionsToFile()
        ->save($targetPath);

    expect($targetPath)->toBeFile();

    expect(mime_content_type($targetPath))->toEqual('application/pdf');
});

it('can generate a pdf with custom paper size unit', function () {
    $command = Browsershot::url('https://example.com')
        ->paperSize(8.3, 11.7, 'in')
        ->margins(0.39, 0.78, 1.18, 1.57, 'in')
        ->createPdfCommand('screenshot.pdf');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'pdf',
        'options' => [
            'path' => 'screenshot.pdf',
            'margin' => [
                'top' => '0.39in',
                'right' => '0.78in',
                'bottom' => '1.18in',
                'left' => '1.57in',
            ],
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'width' => '8.3in',
            'height' => '11.7in',
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
        ],
    ], $command);
});
