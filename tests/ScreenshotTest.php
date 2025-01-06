<?php

use Spatie\Browsershot\Browsershot;

it('can take a screenshot', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::url('https://example.com')
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('can return a screenshot as base 64', function () {
    $base64 = Browsershot::url('https://example.com')
        ->base64Screenshot();

    expect(is_string($base64))->toBeTrue();
});

it('can take a screenshot when using pipe', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::url('https://example.com')
        ->usePipe()
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('can take a screenshot of arbitrary html', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::html('<h1>Hello world!!</h1>')
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('can take a full page screenshot', function () {
    $targetPath = __DIR__.'/temp/fullpageScreenshot.png';

    Browsershot::url('https://github.com/spatie/browsershot')
        ->fullPage()
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('can take a highly customized screenshot', function () {
    $targetPath = __DIR__.'/temp/customScreenshot.png';

    Browsershot::url('https://example.com')
        ->clip(290, 80, 700, 290)
        ->deviceScaleFactor(2)
        ->dismissDialogs()
        ->mobile()
        ->touch()
        ->windowSize(1280, 800)
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('can take a screenshot of an element matching a selector', function () {
    $targetPath = __DIR__.'/temp/nodeScreenshot.png';

    Browsershot::url('https://example.com')
        ->select('div')
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('can add a delay before taking a screenshot', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    $delay = 2000;

    $start = round(microtime(true) * 1000);

    Browsershot::url('https://example.com')
        ->setDelay($delay)
        ->save($targetPath);

    $end = round(microtime(true) * 1000);

    expect($end - $start)->toBeGreaterThanOrEqual($delay);
});

it('can get the output of a screenshot', function () {
    $output = Browsershot::url('https://example.com')
        ->screenshot();

    $finfo = finfo_open();

    $mimeType = finfo_buffer($finfo, $output, FILEINFO_MIME_TYPE);

    expect('image/png')->toEqual($mimeType);
});

it('can set type of screenshot', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.jpg';

    Browsershot::url('https://example.com')
        ->setScreenshotType('jpeg')
        ->save($targetPath);

    expect($targetPath)->toBeFile();

    expect($targetPath)->toHaveMime('image/jpeg');
});

it('can write options to a file and generate a screenshot', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::url('https://example.com')
        ->writeOptionsToFile()
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('will connect to remote instance and take screenshot', function () {
    $instance = Browsershot::url('https://example.com')
        ->setRemoteInstance();

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
            'remoteInstanceUrl' => 'http://127.0.0.1:9222',
        ],
    ], $instance->createScreenshotCommand('screenshot.png'));

    /*
     * to test the connection, uncomment the following code, and make sure you are running a chrome/chromium instance locally,
     * with the following param: --headless --remote-debugging-port=9222
     */
    /*
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    file_put_contents($targetPath, $instance->screenshot());
    expect($targetPath)->toBeFile();
    */
});

it('will connect to a custom remote instance and take screenshot', function () {
    $instance = Browsershot::url('https://example.com')
        ->setRemoteInstance('127.0.0.1', 9999);

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
            'remoteInstanceUrl' => 'http://127.0.0.1:9999',
        ],
    ], $instance->createScreenshotCommand('screenshot.png'));

    /*
    * to test the connection, uncomment the following code, and make sure you are running a chrome/chromium instance locally,
    * with the following params: --headless --remote-debugging-port=9999
    */
    /*
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    file_put_contents($targetPath, $instance->screenshot());
    expect($targetPath)->toBeFile();
    */
});

it('will connect to a custom ws endpoint and take screenshot', function () {
    $instance = Browsershot::url('https://example.com')
        ->setWSEndpoint('wss://chrome.browserless.io/');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
            'browserWSEndpoint' => 'wss://chrome.browserless.io/',
        ],
    ], $instance->createScreenshotCommand('screenshot.png'));

    // It should be online so mis-use the assetsContains because a 4xx error won't contain the word "browerless".
    $html = Browsershot::url('https://chrome.browserless.io/')
        ->bodyHtml();

    // If it's offline then this will fail.
    expect($html)->toContain('chrome.browserless.io');

    /* Now that we now the domain is online, assert the screenshot.
     * Although we can't be sure, because Browsershot itself falls back to launching a chromium instance in browser.js
    */

    $targetPath = __DIR__.'/temp/testScreenshot.png';

    file_put_contents($targetPath, $instance->screenshot());
    expect($targetPath)->toBeFile();
});

it('can take a scaled screenshot', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.pdf';

    Browsershot::url('https://example.com')
        ->scale(0.5)
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});
