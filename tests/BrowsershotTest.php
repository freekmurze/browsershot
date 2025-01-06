<?php

use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\ChromiumResult;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
use Spatie\Browsershot\Exceptions\ElementNotFound;
use Spatie\Browsershot\Exceptions\FileUrlNotAllowed;
use Spatie\Browsershot\Exceptions\UnsuccessfulResponse;
use Spatie\Image\Enums\Fit;
use Symfony\Component\Process\Exception\ProcessFailedException;

beforeEach(function () {
    emptyTempDirectory();
});

it('can get the requests list', function () {
    $list = Browsershot::url('https://example.com')
        ->triggeredRequests();

    expect($list)->toHaveCount(1);

    $this->assertEquals(
        [['url' => 'https://example.com/']],
        $list
    );
});

it('can get the redirect history', function () {
    $list = Browsershot::url('http://www.spatie.be')
        ->redirectHistory();

    $list = array_map(function ($item) {
        unset($item['headers']);

        return $item;
    }, $list);

    expect($list)->toHaveCount(2);

    $this->assertEquals([
        [
            'url' => 'https://www.spatie.be/',
            'status' => 301,
            'reason' => '',
        ],
        [
            'url' => 'https://spatie.be/',
            'status' => 200,
            'reason' => '',
        ],
    ], $list);
});

it('will not allow a file url', function (string $url) {
    Browsershot::url($url);
})->throws(FileUrlNotAllowed::class)->with([
    'file://test',
    'File://test',
    'file:/test',
    'file:\test',
    'file:',
    'file:\\test',
    'view-source',
    'View-Source',
]);

it('will not allow a malformed file url with too many slashes', function () {
    Browsershot::url('file:///test');
})->throws(FileUrlNotAllowed::class);

it('will not allow a file url that has leading spaces', function () {
    Browsershot::url('    file://test');
})->throws(FileUrlNotAllowed::class);

it('will not allow a slightly malformed file url', function () {
    Browsershot::url('file:/test');
})->throws(FileUrlNotAllowed::class);

it('no redirects - will not follow redirects', function () {
    $targetPath = __DIR__.'/temp/redirect_fail.pdf';

    Browsershot::url('http://www.spatie.be')
        ->disableRedirects()
        ->save($targetPath);

    expect($targetPath)->not->toBeFile();
})->throws(ProcessFailedException::class);

it('no redirects - will still render direct 200 OKs', function () {
    $targetPath = __DIR__.'/temp/redirect_success.pdf';

    Browsershot::url('https://spatie.be/')
        ->disableRedirects()
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('can take a high density screenshot', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::url('https://example.com')
        ->deviceScaleFactor(2)
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('cannot save without an extension', function () {
    $this->expectException(CouldNotTakeBrowsershot::class);

    $targetPath = __DIR__.'/temp/testScreenshot';

    Browsershot::url('https://example.com')
        ->save($targetPath);
});

it('throws an exception if the selector does not match any elements', function () {
    $this->expectException(ElementNotFound::class);
    $targetPath = __DIR__.'/temp/nodeScreenshot.png';

    Browsershot::url('https://example.com')
        ->select('not-a-valid-selector')
        ->save($targetPath);
});

it('can handle a permissions error', function () {
    $targetPath = '/cantWriteThisPdf.png';

    $this->expectException(ProcessFailedException::class);

    Browsershot::url('https://example.com')
        ->save($targetPath);
});

it('can set another node binary', function () {
    $this->expectException(ProcessFailedException::class);

    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::html('Foo')
        ->setBinPath(__DIR__.'/../browser.js')
        ->save($targetPath);
});

it('can set another chrome executable path', function () {
    $this->expectException(ProcessFailedException::class);

    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::html('Foo')
        ->setChromePath('non-existant/bin/wich/causes/an/exception')
        ->save($targetPath);
});

it('can set another bin path', function () {
    $this->expectException(ProcessFailedException::class);

    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::html('Foo')
        ->setBinPath('non-existant/bin/wich/causes/an/exception')
        ->save($targetPath);
});

it('can set the include path and still works', function () {
    $targetPath = __DIR__.'/temp/testScreenshot.png';

    Browsershot::html('Foo')
        ->setIncludePath('$PATH:/usr/local/bin:/mypath')
        ->save($targetPath);

    expect($targetPath)->toBeFile();
});

it('can run without sandbox', function () {
    $command = Browsershot::url('https://example.com')
        ->noSandbox()
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
            'args' => [
                '--no-sandbox', '--chromium-deny-list=^file:(?!//\/tmp/).*',
            ],
            'type' => 'png',
        ],
    ], $command);
});

it('can dismiss dialogs', function () {
    $command = Browsershot::url('https://example.com')
        ->dismissDialogs()
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'dismissDialogs' => true,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can disable javascript', function () {
    $command = Browsershot::url('https://example.com')
        ->disableJavascript()
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'disableJavascript' => true,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can disable images', function () {
    $command = Browsershot::url('https://example.com')
        ->disableImages()
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'disableImages' => true,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can disable capture urls', function () {
    $command = Browsershot::url('https://example.com')
        ->disableCaptureURLS()
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'disableCaptureURLS' => true,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can block urls', function () {
    $command = Browsershot::url('https://example.com')
        ->blockUrls([
            'example.com/cm-notify?pi=outbrain',
            'sync.outbrain.com/cookie-sync?p=bidswitch',
        ])
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'blockUrls' => [
                'example.com/cm-notify?pi=outbrain',
                'sync.outbrain.com/cookie-sync?p=bidswitch',
            ],
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can block domains', function () {
    $command = Browsershot::url('https://example.com')
        ->blockDomains([
            'googletagmanager.com',
            'google-analytics.com',
        ])
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'blockDomains' => [
                'googletagmanager.com',
                'google-analytics.com',
            ],
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can ignore https errors', function () {
    $command = Browsershot::url('https://example.com')
        ->ignoreHttpsErrors()
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'acceptInsecureCerts' => true,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can use a proxy server', function () {
    $command = Browsershot::url('https://example.com')
        ->setProxyServer('1.2.3.4:8080')
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
            'args' => ['--proxy-server=1.2.3.4:8080', '--chromium-deny-list=^file:(?!//\/tmp/).*',
            ],
            'type' => 'png',
        ],
    ], $command);
});

it('can set arbitrary options', function () {
    $command = Browsershot::url('https://example.com')
        ->setOption('foo.bar', 100)
        ->setOption('foo.bar', 150)
        ->setOption('foo.baz', 200)
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
            'foo' => [
                'bar' => 150,
                'baz' => 200,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can get the output of a pdf', function () {
    $output = Browsershot::url('https://example.com')
        ->pdf();

    $finfo = finfo_open();

    $mimeType = finfo_buffer($finfo, $output, FILEINFO_MIME_TYPE);

    expect('application/pdf')->toEqual($mimeType);
});

it('can wait until network idle', function () {
    $command = Browsershot::url('https://example.com')
        ->waitUntilNetworkIdle()
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'waitUntil' => 'networkidle0',
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);

    $command = Browsershot::url('https://example.com')
        ->waitUntilNetworkIdle($strict = false)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'waitUntil' => 'networkidle2',
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can send extra http headers', function () {
    $command = Browsershot::url('https://example.com')
        ->setExtraHttpHeaders(['extra-http-header' => 'extra-http-header'])
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'extraHTTPHeaders' => ['extra-http-header' => 'extra-http-header'],
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can send extra navigation http headers', function () {
    $command = Browsershot::url('https://example.com')
        ->setExtraNavigationHttpHeaders(['extra-http-header' => 'extra-http-header'])
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'extraNavigationHTTPHeaders' => ['extra-http-header' => 'extra-http-header'],
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can authenticate', function () {
    $command = Browsershot::url('https://example.com')
        ->authenticate('username1', 'password1')
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'authentication' => ['username' => 'username1', 'password' => 'password1'],
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can send cookies', function () {
    $command = Browsershot::url('https://example.com')
        ->useCookies(['theme' => 'light', 'sessionToken' => 'abc123'])
        ->useCookies(['theme' => 'dark'], 'ui.example.com')
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'cookies' => [
                [
                    'name' => 'theme',
                    'value' => 'light',
                    'domain' => 'example.com',
                ],
                [
                    'name' => 'sessionToken',
                    'value' => 'abc123',
                    'domain' => 'example.com',
                ],
                [
                    'name' => 'theme',
                    'value' => 'dark',
                    'domain' => 'ui.example.com',
                ],
            ],
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can send post request', function () {
    $command = Browsershot::url('https://example.com')
        ->post(['foo' => 'bar'])
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'postParams' => ['foo' => 'bar'],
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can process post request', function () {
    $html = Browsershot::url('https://httpbin.org/post')
        ->post(['foo' => 'bar'])
        ->bodyHtml();

    expect($html)->toContain('"foo": "bar"');
});

it('can click on the page', function () {
    $command = Browsershot::url('https://example.com')
        ->click('#selector1')
        ->click('#selector2', 'right', 2, 1)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'clicks' => [
                [
                    'selector' => '#selector1',
                    'button' => 'left',
                    'clickCount' => 1,
                    'delay' => 0,
                ],
                [
                    'selector' => '#selector2',
                    'button' => 'right',
                    'clickCount' => 2,
                    'delay' => 1,
                ],
            ],
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can evaluate page', function () {
    $result = Browsershot::url('https://example.com')
        ->evaluate('1 + 1');

    expect($result)->toEqual('2');
});

it('can add a timeout to puppeteer', function () {
    $command = Browsershot::url('https://example.com')
        ->timeout(123)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'timeout' => 123000,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can add a wait for function to puppeteer', function () {
    $command = Browsershot::url('https://example.com')
        ->waitForFunction('window.innerWidth < 100')
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'function' => 'window.innerWidth < 100',
            'functionPolling' => 'raf',
            'functionTimeout' => 0,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can add a wait for selector', function () {
    $command = Browsershot::url('https://example.com')
        ->waitForSelector('.wait_for_me')
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'waitForSelector' => '.wait_for_me',
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can add a wait for selector and provide options', function () {
    $command = Browsershot::url('https://example.com')
        ->waitForSelector('.wait_for_me', ['visibile' => true])
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'waitForSelector' => '.wait_for_me',
            'waitForSelectorOptions' => ['visibile' => true],
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can input form fields and post and get the body html', function () {
    $delay = 2000;

    $command = Browsershot::url('http://example.com')
        ->type('#selector1', 'Hello, is it me you are looking for?')
        ->click('#selector2')
        ->setDelay($delay)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'http://example.com',
        'action' => 'screenshot',
        'options' => [
            'clicks' => [
                [
                    'selector' => '#selector2',
                    'button' => 'left',
                    'clickCount' => 1,
                    'delay' => 0,
                ],
            ],
            'types' => [
                [
                    'selector' => '#selector1',
                    'text' => 'Hello, is it me you are looking for?',
                    'delay' => 0,
                ],
            ],
            'delay' => 2000,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can input form fields and post to ssl and get the body html', function () {
    $delay = 2000;

    $command = Browsershot::url('https://example.com')
        ->type('#selector1', 'Hello, is it me you are looking for?')
        ->click('#selector2')
        ->setDelay($delay)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'clicks' => [
                [
                    'selector' => '#selector2',
                    'button' => 'left',
                    'clickCount' => 1,
                    'delay' => 0,
                ],
            ],
            'types' => [
                [
                    'selector' => '#selector1',
                    'text' => 'Hello, is it me you are looking for?',
                    'delay' => 0,
                ],
            ],
            'delay' => 2000,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('can change select fields and post and get the body html', function () {
    $delay = 2000;

    $command = Browsershot::url('http://example.com')
        ->selectOption('#selector1', 'option_one')
        ->click('#selector2')
        ->setDelay($delay)
        ->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'http://example.com',
        'action' => 'screenshot',
        'options' => [
            'clicks' => [
                [
                    'selector' => '#selector2',
                    'button' => 'left',
                    'clickCount' => 1,
                    'delay' => 0,
                ],
            ],
            'selects' => [
                [
                    'selector' => '#selector1',
                    'value' => 'option_one',
                ],
            ],
            'delay' => 2000,
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
        ],
    ], $command);
});

it('will auto prefix chromium arguments', function () {
    $command = Browsershot::url('https://example.com')
        ->addChromiumArguments(['please-autoprefix-me'])
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
            'args' => [
                '--please-autoprefix-me',
                '--chromium-deny-list=^file:(?!//\/tmp/).*',
            ],
            'type' => 'png',
        ],
    ], $command);
});

it('will allow many chromium arguments', function () {
    $command = Browsershot::url('https://example.com')
        ->addChromiumArguments([
            'my-custom-arg',
            'another-argument' => 'some-value',
            'yet-another-arg' => 'foo',
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
            'args' => [
                '--my-custom-arg',
                '--another-argument=some-value',
                '--yet-another-arg=foo',
                '--chromium-deny-list=^file:(?!//\/tmp/).*',
            ],
            'type' => 'png',
        ],
    ], $command);
});

it('will set user data dir arg flag', function () {
    $dataDir = __DIR__.'/temp/session';
    $command = Browsershot::url('https://example.com')
        ->userDataDir($dataDir)
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
            'args' => [
                "--user-data-dir={$dataDir}",
                '--chromium-deny-list=^file:(?!//\/tmp/).*',
            ],
            'type' => 'png',
        ],
    ], $command);
});

it('will allow passing environment variables', function () {
    $instance = Browsershot::url('https://example.com')
        ->setEnvironmentOptions([
            'TZ' => 'Pacific/Auckland',
        ]);

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
            'env' => [
                'TZ' => 'Pacific/Auckland',
            ],
        ],
    ], $instance->createScreenshotCommand('screenshot.png'));
});

it('can throw an error when response is unsuccessful', function () {
    $url = 'https://google.com/404';

    $this->expectException(UnsuccessfulResponse::class);
    $this->expectExceptionMessage("The given url `{$url}` responds with code 404");

    $targetPath = __DIR__.'/temp/notExists.pdf';

    Browsershot::url($url)
        ->preventUnsuccessfulResponse()
        ->save($targetPath);

    $this->assertFileDoesNotExist($targetPath);
})->skip();

it('will allow passing a content url', function () {
    $instance = Browsershot::html('<h1>Hello world!!</h1>')
        ->setContentUrl('https://example.com');

    $response = $instance->createScreenshotCommand('screenshot.png');

    $responseUrl = $response['url'];
    unset($response['url']);

    $this->assertEquals([
        'action' => 'screenshot',
        'options' => [
            'path' => 'screenshot.png',
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'type' => 'png',
            'displayHeaderFooter' => false,
            'contentUrl' => 'https://example.com',
        ],
    ], $response);

    $this->assertStringContainsString('file://', $responseUrl);
});

it('can get the console messages', function () {
    $consoleMessages = Browsershot::url('https://bitsofco.de/styling-broken-images/')->consoleMessages();

    expect($consoleMessages)->toBeArray()
        ->and($consoleMessages[0]['type'])->toBeString()
        ->and($consoleMessages[0]['message'])->toBeString()
        ->and($consoleMessages[0]['location']['url'])->toBeString();
});

it('can get the failed requests', function () {
    $failedRequests = Browsershot::url('https://bitsofco.de/styling-broken-images/')->failedRequests();

    expect($failedRequests)->toBeArray()
        ->and($failedRequests[0]['status'])->toBe(404)
        ->and($failedRequests[0]['url'])->toBe('https://bitsofco.de/broken.jpg');
});

it('can set the custom temp path', function () {
    $output = Browsershot::url('https://example.com')
        ->setCustomTempPath(__DIR__.'/temp/')
        ->pdf();

    $finfo = finfo_open();

    $mimeType = finfo_buffer($finfo, $output, FILEINFO_MIME_TYPE);

    expect('application/pdf')->toEqual($mimeType);
});

it('should set the new headless flag when using the new method', function () {
    $command = Browsershot::url('https://example.com')->newHeadless()->createScreenshotCommand('screenshot.png');

    $this->assertEquals([
        'url' => 'https://example.com',
        'action' => 'screenshot',
        'options' => [
            'newHeadless' => true,
            'type' => 'png',
            'path' => 'screenshot.png',
            'args' => ['--chromium-deny-list=^file:(?!//\/tmp/).*'],
            'viewport' => [
                'width' => 800,
                'height' => 600,
            ],
        ],
    ], $command);
});

it('can get the body html and full output data', function () {
    $instance = Browsershot::url('https://example.com');
    $html = $instance->bodyHtml();

    expect($html)->toContain($expectedContent = '<h1>Example Domain</h1>');

    $output = $instance->getOutput();

    expect($output)->not()->toBeNull();
    expect($output)->toBeInstanceOf(ChromiumResult::class);
    expect($output->getResult())->toContain($expectedContent);
    expect($output->getConsoleMessages())->toBe([]);
    expect($output->getRequestsList())->toMatchArray([[
        'url' => 'https://example.com/',
    ]]);
    expect($output->getFailedRequests())->toBe([]);
    expect($output->getPageErrors())->toBe([]);
});

it('can handle a permissions error with full output', function () {
    $targetPath = '/cantWriteThisPdf.png';

    $this->expectException(ProcessFailedException::class);

    // Block the favicon request to prevent randomness in the output.
    $instance = Browsershot::url('https://example.com')
        ->blockUrls(['https://example.com/favicon.ico']);

    try {
        $instance->save($targetPath);
    } catch (\Throwable $th) {
        $output = $instance->getOutput();

        expect($output)->not()->toBeNull();
        expect($output)->toBeInstanceOf(ChromiumResult::class);
        expect($output->getException())->not()->toBeEmpty();
        expect($output->getConsoleMessages())->toBe([
            [
                'type' => 'error',
                'message' => 'Failed to load resource: net::ERR_FAILED',
                'location' => ['url' => 'https://example.com/favicon.ico'],
                'stackTrace' => [['url' => 'https://example.com/favicon.ico']],
            ],
        ]);
        expect($output->getRequestsList())->toMatchArray([[
            'url' => 'https://example.com/',
        ]]);
        expect($output->getFailedRequests())->toBe([]);
        expect($output->getPageErrors())->toBe([]);

        throw $th;
    }
});

it('should be able to fetch page errors with pageErrors method', function () {
    $errors = Browsershot::html('<!DOCTYPE html>
    <html lang="en">
      <body>
        <script type="text/javascript">
            throw "this is not right!";
        </script>
      </body>
    </html>')->pageErrors();

    expect($errors)->toBeArray();
    expect(count($errors))->toBe(1);
    expect($errors[0]['name'])->toBeString();
    expect($errors[0]['message'])->toBeString();
});

it('will apply manipulations when taking screenshots', function () {
    $screenShot = Browsershot::url('https://example.com')
        ->windowSize(1920, 1080)
        ->fit(Fit::Fill, 200, 200)
        ->screenshot();

    $targetPath = __DIR__.'/temp/testScreenshot.png';

    file_put_contents($targetPath, $screenShot);

    expect($targetPath)->toBeFile();
    expect(getimagesize($targetPath)[0])->toEqual(200);
    expect(getimagesize($targetPath)[1])->toEqual(200);
});
