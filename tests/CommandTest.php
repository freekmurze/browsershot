<?php

use Spatie\Browsershot\Browsershot;

it('can create a command to generate a screenshot', function () {
    $command = Browsershot::url('https://example.com')
        ->clip(100, 50, 600, 400)
        ->deviceScaleFactor(2)
        ->fullPage()
        ->dismissDialogs()
        ->windowSize(1920, 1080)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'clip' => ['x' => 100, 'y' => 50, 'width' => 600, 'height' => 400],
            'path' => 'screenshot.png',
            'fullPage' => true,
            'dismissDialogs' => true,
            'viewport' => [
                'deviceScaleFactor' => 2,
                'width' => 1920,
                'height' => 1080,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can create a command to generate a screenshot and omit the background', function () {
    $command = Browsershot::url('https://example.com')
        ->clip(100, 50, 600, 400)
        ->deviceScaleFactor(2)
        ->hideBackground()
        ->windowSize(1920, 1080)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'clip' => ['x' => 100, 'y' => 50, 'width' => 600, 'height' => 400],
            'path' => 'screenshot.png',
            'omitBackground' => true,
            'viewport' => [
                'deviceScaleFactor' => 2,
                'width' => 1920,
                'height' => 1080,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can create a command to generate a pdf', function () {
    $command = Browsershot::url('https://example.com')
        ->showBackground()
        ->transparentBackground()
        ->landscape()
        ->margins(10, 20, 30, 40)
        ->pages('1-3')
        ->paperSize(210, 148)
        ->createPdfCommand('screenshot.pdf');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'pdf',
        'options' => [
            'path' => 'screenshot.pdf',
            'printBackground' => true,
            'omitBackground' => true,
            'landscape' => true,
            'margin' => ['top' => '10mm', 'right' => '20mm', 'bottom' => '30mm', 'left' => '40mm'],
            'pageRanges' => '1-3',
            'width' => '210mm',
            'height' => '148mm',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
        ],
    ], $command);
});

it('can create a command to generate a pdf with tags', function () {
    $command = Browsershot::url('https://example.com')
        ->showBackground()
        ->transparentBackground()
        ->taggedPdf()
        ->landscape()
        ->margins(10, 20, 30, 40)
        ->pages('1-3')
        ->paperSize(210, 148)
        ->createPdfCommand('screenshot.pdf');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'pdf',
        'options' => [
            'path' => 'screenshot.pdf',
            'printBackground' => true,
            'omitBackground' => true,
            'tagged' => true,
            'landscape' => true,
            'margin' => ['top' => '10mm', 'right' => '20mm', 'bottom' => '30mm', 'left' => '40mm'],
            'pageRanges' => '1-3',
            'width' => '210mm',
            'height' => '148mm',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
        ],
    ], $command);
});

it('can create a command to generate a pdf with a custom header', function () {
    $command = Browsershot::url('https://example.com')
        ->showBackground()
        ->landscape()
        ->margins(10, 20, 30, 40)
        ->pages('1-3')
        ->paperSize(210, 148)
        ->showBrowserHeaderAndFooter()
        ->headerHtml('<p>Test Header</p>')
        ->createPdfCommand('screenshot.pdf');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'pdf',
        'options' => [
            'path' => 'screenshot.pdf',
            'printBackground' => true,
            'landscape' => true,
            'margin' => ['top' => '10mm', 'right' => '20mm', 'bottom' => '30mm', 'left' => '40mm'],
            'pageRanges' => '1-3',
            'width' => '210mm',
            'height' => '148mm',
            'displayHeaderFooter' => true,
            'headerTemplate' => '<p>Test Header</p>',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
        ],
    ], $command);
});

it('can create a command to generate a pdf with a custom footer', function () {
    $command = Browsershot::url('https://example.com')
        ->showBackground()
        ->landscape()
        ->margins(10, 20, 30, 40)
        ->pages('1-3')
        ->paperSize(210, 148)
        ->showBrowserHeaderAndFooter()
        ->footerHtml('<p>Test Footer</p>')
        ->createPdfCommand('screenshot.pdf');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'pdf',
        'options' => [
            'path' => 'screenshot.pdf',
            'printBackground' => true,
            'landscape' => true,
            'margin' => ['top' => '10mm', 'right' => '20mm', 'bottom' => '30mm', 'left' => '40mm'],
            'pageRanges' => '1-3',
            'width' => '210mm',
            'height' => '148mm',
            'displayHeaderFooter' => true,
            'footerTemplate' => '<p>Test Footer</p>',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
        ],
    ], $command);
});

it('can create a command to generate a pdf with the header hidden', function () {
    $command = Browsershot::url('https://example.com')
        ->showBackground()
        ->landscape()
        ->margins(10, 20, 30, 40)
        ->pages('1-3')
        ->paperSize(210, 148)
        ->showBrowserHeaderAndFooter()
        ->hideHeader()
        ->createPdfCommand('screenshot.pdf');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'pdf',
        'options' => [
            'path' => 'screenshot.pdf',
            'printBackground' => true,
            'landscape' => true,
            'margin' => ['top' => '10mm', 'right' => '20mm', 'bottom' => '30mm', 'left' => '40mm'],
            'pageRanges' => '1-3',
            'width' => '210mm',
            'height' => '148mm',
            'displayHeaderFooter' => true,
            'headerTemplate' => '<p></p>',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
        ],
    ], $command);
});

it('can create a command to generate a pdf with the footer hidden', function () {
    $command = Browsershot::url('https://example.com')
        ->showBackground()
        ->landscape()
        ->margins(10, 20, 30, 40)
        ->pages('1-3')
        ->paperSize(210, 148)
        ->showBrowserHeaderAndFooter()
        ->hideFooter()
        ->createPdfCommand('screenshot.pdf');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'pdf',
        'options' => [
            'path' => 'screenshot.pdf',
            'printBackground' => true,
            'landscape' => true,
            'margin' => ['top' => '10mm', 'right' => '20mm', 'bottom' => '30mm', 'left' => '40mm'],
            'pageRanges' => '1-3',
            'width' => '210mm',
            'height' => '148mm',
            'displayHeaderFooter' => true,
            'footerTemplate' => '<p></p>',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
        ],
    ], $command);
});

it('can create a command to generate a pdf with paper format', function () {
    $command = Browsershot::url('https://example.com')
        ->showBackground()
        ->landscape()
        ->margins(10, 20, 30, 40)
        ->pages('1-3')
        ->format('a4')
        ->createPdfCommand('screenshot.pdf');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'pdf',
        'options' => [
            'path' => 'screenshot.pdf',
            'printBackground' => true,
            'landscape' => true,
            'margin' => ['top' => '10mm', 'right' => '20mm', 'bottom' => '30mm', 'left' => '40mm'],
            'pageRanges' => '1-3',
            'format' => 'a4',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
        ],
    ], $command);
});

it('can use given user agent', function () {
    $command = Browsershot::url('https://example.com')
        ->userAgent('my_special_snowflake')
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'userAgent' => 'my_special_snowflake',
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can set emulate media option', function () {
    $command = Browsershot::url('https://example.com')
        ->emulateMedia('screen')
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'emulateMedia' => 'screen',
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can set emulate media option to null', function () {
    $command = Browsershot::url('https://example.com')
        ->emulateMedia(null)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'emulateMedia' => null,
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can set emulate media features', function () {
    $command = Browsershot::url('https://example.com')
        ->emulateMediaFeatures([
            ['name' => 'prefers-color-scheme', 'value' => 'dark'],
        ])
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'emulateMediaFeatures' => '[{"name":"prefers-color-scheme","value":"dark"}]',
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can use pipe', function () {
    $command = Browsershot::url('https://example.com')
        ->usePipe()
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'pipe' => true,
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});
