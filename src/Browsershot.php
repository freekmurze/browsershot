<?php

namespace Spatie\Browsershot;

use Spatie\Browsershot\Enums\Polling;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;
use Spatie\Browsershot\Exceptions\ElementNotFound;
use Spatie\Browsershot\Exceptions\FileDoesNotExistException;
use Spatie\Browsershot\Exceptions\FileUrlNotAllowed;
use Spatie\Browsershot\Exceptions\HtmlIsNotAllowedToContainFile;
use Spatie\Browsershot\Exceptions\UnsuccessfulResponse;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/** @mixin \Spatie\Image\Image */
class Browsershot
{
    protected ?string $nodeBinary = null;

    protected ?string $npmBinary = null;

    protected ?string $nodeModulePath = null;

    protected string $includePath = '$PATH:/usr/local/bin:/opt/homebrew/bin';

    protected ?string $binPath = null;

    protected string $html = '';

    protected bool $noSandbox = false;

    protected string $proxyServer = '';

    protected bool $showBackground = false;

    protected bool $showScreenshotBackground = true;

    protected ?float $scale = null;

    protected string $screenshotType = 'png';

    protected ?int $screenshotQuality = null;

    protected bool $taggedPdf = false;

    protected ?TemporaryDirectory $temporaryHtmlDirectory = null;

    protected int $timeout = 60;

    protected bool $transparentBackground = false;

    protected string $url = '';

    protected array $postParams = [];

    protected array $additionalOptions = [];

    protected ?TemporaryDirectory $temporaryOptionsDirectory = null;

    protected string $tempPath = '';

    protected bool $writeOptionsToFile = false;

    protected array $chromiumArguments = [];

    protected ?ChromiumResult $chromiumResult = null;

    protected ImageManipulations $imageManipulations;

    protected array $unsafeProtocols = [
        'file:',
        'file:/',
        'file://',
        'file:\\',
        'file:\\\\',
        'view-source',
    ];

    protected string $deniedUrlsRegex = '^file:(?!//\/tmp/).*';

    public static function url(string $url): static
    {
        return (new static)->setUrl($url);
    }

    public static function html(string $html): static
    {
        return (new static)->setHtml($html);
    }

    public static function htmlFromFilePath(string $filePath): static
    {
        return (new static)->setHtmlFromFilePath($filePath);
    }

    public function __construct(string $url = '', bool $deviceEmulate = false)
    {
        $this->url = $url;

        if (! $deviceEmulate) {
            $this->windowSize(800, 600);
        }

        $this->imageManipulations = new ImageManipulations;
    }

    public function setDeniedUrlsRegex(string $deniedUrlsRegex): static
    {
        $this->deniedUrlsRegex = $deniedUrlsRegex;

        return $this;
    }

    public function setNodeBinary(string $nodeBinary): static
    {
        $this->nodeBinary = $nodeBinary;

        return $this;
    }

    public function setNpmBinary(string $npmBinary): static
    {
        $this->npmBinary = $npmBinary;

        return $this;
    }

    public function setIncludePath(string $includePath): static
    {
        $this->includePath = $includePath;

        return $this;
    }

    public function setBinPath(string $binPath): static
    {
        $this->binPath = $binPath;

        return $this;
    }

    public function setNodeModulePath(string $nodeModulePath): static
    {
        $this->nodeModulePath = $nodeModulePath;

        return $this;
    }

    public function setChromePath(string $executablePath): static
    {
        $this->setOption('executablePath', $executablePath);

        return $this;
    }

    public function setCustomTempPath(string $tempPath): static
    {
        $this->tempPath = $tempPath;

        return $this;
    }

    public function post(array $postParams = []): static
    {
        $this->postParams = $postParams;

        return $this;
    }

    public function useCookies(array $cookies, ?string $domain = null): static
    {
        if (! count($cookies)) {
            return $this;
        }

        if (is_null($domain)) {
            $domain = parse_url($this->url)['host'];
        }

        $cookies = array_map(function ($value, $name) use ($domain) {
            return compact('name', 'value', 'domain');
        }, $cookies, array_keys($cookies));

        if (isset($this->additionalOptions['cookies'])) {
            $cookies = array_merge($this->additionalOptions['cookies'], $cookies);
        }

        $this->setOption('cookies', $cookies);

        return $this;
    }

    public function setExtraHttpHeaders(array $extraHTTPHeaders): static
    {
        $this->setOption('extraHTTPHeaders', $extraHTTPHeaders);

        return $this;
    }

    public function setExtraNavigationHttpHeaders(array $extraNavigationHTTPHeaders): static
    {
        $this->setOption('extraNavigationHTTPHeaders', $extraNavigationHTTPHeaders);

        return $this;
    }

    public function authenticate(string $username, string $password): static
    {
        $this->setOption('authentication', compact('username', 'password'));

        return $this;
    }

    public function click(string $selector, string $button = 'left', int $clickCount = 1, int $delay = 0): static
    {
        $clicks = $this->additionalOptions['clicks'] ?? [];

        $clicks[] = compact('selector', 'button', 'clickCount', 'delay');

        $this->setOption('clicks', $clicks);

        return $this;
    }

    public function selectOption(string $selector, string $value = ''): static
    {
        $dropdownSelects = $this->additionalOptions['selects'] ?? [];

        $dropdownSelects[] = compact('selector', 'value');

        $this->setOption('selects', $dropdownSelects);

        return $this;
    }

    public function type(string $selector, string $text = '', int $delay = 0): static
    {
        $types = $this->additionalOptions['types'] ?? [];

        $types[] = compact('selector', 'text', 'delay');

        $this->setOption('types', $types);

        return $this;
    }

    public function waitUntilNetworkIdle(bool $strict = true): static
    {
        $this->setOption('waitUntil', $strict ? 'networkidle0' : 'networkidle2');

        return $this;
    }

    public function waitForFunction(string $function, ?Polling $polling = null, int $timeout = 0): static
    {
        $polling ??= Polling::RequestAnimationFrame;

        $this->setOption('functionPolling', $polling->value);
        $this->setOption('functionTimeout', $timeout);

        return $this->setOption('function', $function);
    }

    public function waitForSelector(string $selector, array $options = []): static
    {
        $this->setOption('waitForSelector', $selector);

        if (! empty($options)) {
            $this->setOption('waitForSelectorOptions', $options);
        }

        return $this;
    }

    public function setUrl(string $url): static
    {
        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw FileUrlNotAllowed::urlCannotBeParsed($url);
        }

        foreach ($this->unsafeProtocols as $unsupportedProtocol) {
            if (str_starts_with(strtolower($url), $unsupportedProtocol)) {
                throw FileUrlNotAllowed::make();
            }
        }

        $this->url = $url;
        $this->html = '';

        return $this;
    }

    public function setHtmlFromFilePath(string $filePath): static
    {

        if (! file_exists($filePath)) {
            throw FileDoesNotExistException::make($filePath);
        }

        $this->url = 'file://'.$filePath;
        $this->html = '';

        return $this;
    }

    public function setProxyServer(string $proxyServer): static
    {
        $this->proxyServer = $proxyServer;

        return $this;
    }

    public function setHtml(string $html): static
    {
        if (str_contains(strtolower($html), 'view-source:')) {
            throw HtmlIsNotAllowedToContainFile::make();
        }

        $this->html = $html;
        $this->url = '';

        $this->hideBrowserHeaderAndFooter();

        return $this;
    }

    public function clip(int $x, int $y, int $width, int $height): static
    {
        return $this->setOption('clip', compact('x', 'y', 'width', 'height'));
    }

    public function preventUnsuccessfulResponse(bool $preventUnsuccessfulResponse = true): static
    {
        return $this->setOption('preventUnsuccessfulResponse', $preventUnsuccessfulResponse);
    }

    public function select($selector, $index = 0): static
    {
        $this->selectorIndex($index);

        return $this->setOption('selector', $selector);
    }

    public function selectorIndex(int $index): static
    {
        return $this->setOption('selectorIndex', $index);
    }

    public function showBrowserHeaderAndFooter(): static
    {
        return $this->setOption('displayHeaderFooter', true);
    }

    public function hideBrowserHeaderAndFooter(): static
    {
        return $this->setOption('displayHeaderFooter', false);
    }

    public function hideHeader(): static
    {
        return $this->headerHtml('<p></p>');
    }

    public function hideFooter(): static
    {
        return $this->footerHtml('<p></p>');
    }

    public function headerHtml(string $html): static
    {
        return $this->setOption('headerTemplate', $html);
    }

    public function footerHtml(string $html): static
    {
        return $this->setOption('footerTemplate', $html);
    }

    public function deviceScaleFactor(int $deviceScaleFactor): static
    {
        // Google Chrome currently supports values of 1, 2, and 3.
        return $this->setOption('viewport.deviceScaleFactor', max(1, min(3, $deviceScaleFactor)));
    }

    public function fullPage(): static
    {
        return $this->setOption('fullPage', true);
    }

    public function showBackground(): static
    {
        $this->showBackground = true;
        $this->showScreenshotBackground = true;

        return $this;
    }

    public function hideBackground(): static
    {
        $this->showBackground = false;
        $this->showScreenshotBackground = false;

        return $this;
    }

    public function transparentBackground(): static
    {
        $this->transparentBackground = true;

        return $this;
    }

    public function taggedPdf(): static
    {
        $this->taggedPdf = true;

        return $this;
    }

    public function setScreenshotType(string $type, ?int $quality = null): static
    {
        $this->screenshotType = $type;

        if (! is_null($quality)) {
            $this->screenshotQuality = $quality;
        }

        return $this;
    }

    public function ignoreHttpsErrors(): static
    {
        return $this->setOption('acceptInsecureCerts', true);
    }

    public function mobile(bool $mobile = true): static
    {
        return $this->setOption('viewport.isMobile', $mobile);
    }

    public function touch(bool $touch = true): static
    {
        return $this->setOption('viewport.hasTouch', $touch);
    }

    public function landscape(bool $landscape = true): static
    {
        return $this->setOption('landscape', $landscape);
    }

    public function margins(float $top, float $right, float $bottom, float $left, string $unit = 'mm'): static
    {
        return $this->setOption('margin', [
            'top' => $top.$unit,
            'right' => $right.$unit,
            'bottom' => $bottom.$unit,
            'left' => $left.$unit,
        ]);
    }

    public function noSandbox(): static
    {
        $this->noSandbox = true;

        return $this;
    }

    public function dismissDialogs(): static
    {
        return $this->setOption('dismissDialogs', true);
    }

    public function disableJavascript(): static
    {
        return $this->setOption('disableJavascript', true);
    }

    public function disableImages(): static
    {
        return $this->setOption('disableImages', true);
    }

    public function disableCaptureURLS(): static
    {
        return $this->setOption('disableCaptureURLS', true);
    }

    public function blockUrls($array): static
    {
        return $this->setOption('blockUrls', $array);
    }

    public function blockDomains($array): static
    {
        return $this->setOption('blockDomains', $array);
    }

    public function disableRedirects(): static
    {
        return $this->setOption('disableRedirects', true);
    }

    public function pages(string $pages): static
    {
        return $this->setOption('pageRanges', $pages);
    }

    public function paperSize(float $width, float $height, string $unit = 'mm'): static
    {
        return $this
            ->setOption('width', $width.$unit)
            ->setOption('height', $height.$unit);
    }

    // paper format
    public function format(string $format): static
    {
        return $this->setOption('format', $format);
    }

    public function scale(float $scale): static
    {
        $this->scale = $scale;

        return $this;
    }

    public function timeout(int $timeout): static
    {
        $this->timeout = $timeout;

        return $this->setOption('timeout', $timeout * 1000);
    }

    public function protocolTimeout(int $protocolTimeout): static
    {
        return $this->setOption('protocolTimeout', $protocolTimeout * 1000);
    }

    public function userAgent(string $userAgent): static
    {
        return $this->setOption('userAgent', $userAgent);
    }

    public function device(string $device): static
    {
        return $this->setOption('device', $device);
    }

    public function emulateMedia(?string $media): static
    {
        return $this->setOption('emulateMedia', $media);
    }

    public function emulateMediaFeatures(array $features): static
    {
        return $this->setOption('emulateMediaFeatures', json_encode($features));
    }

    public function newHeadless(): self
    {
        return $this->setOption('newHeadless', true);
    }

    public function windowSize(int $width, int $height): static
    {
        return $this
            ->setOption('viewport.width', $width)
            ->setOption('viewport.height', $height);
    }

    public function setDelay(int $delayInMilliseconds): static
    {
        return $this->setOption('delay', $delayInMilliseconds);
    }

    public function delay(int $delayInMilliseconds): static
    {
        return $this->setDelay($delayInMilliseconds);
    }

    public function setUserDataDir(string $absolutePath): static
    {
        return $this->addChromiumArguments(['user-data-dir' => $absolutePath]);
    }

    public function userDataDir(string $absolutePath): static
    {
        return $this->setUserDataDir($absolutePath);
    }

    public function writeOptionsToFile(): static
    {
        $this->writeOptionsToFile = true;

        return $this;
    }

    public function setOption($key, $value): static
    {
        $this->arraySet($this->additionalOptions, $key, $value);

        return $this;
    }

    public function addChromiumArguments(array $arguments): static
    {
        foreach ($arguments as $argument => $value) {
            if (is_numeric($argument)) {
                $this->chromiumArguments[] = "--$value";
            } else {
                $this->chromiumArguments[] = "--$argument=$value";
            }
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        $this->imageManipulations->$name(...$arguments);

        return $this;
    }

    public function save(string $targetPath): void
    {
        $extension = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

        if ($extension === '') {
            throw CouldNotTakeBrowsershot::outputFileDidNotHaveAnExtension($targetPath);
        }

        if ($extension === 'pdf') {
            $this->savePdf($targetPath);

            return;
        }

        $command = $this->createScreenshotCommand($targetPath);

        $output = $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        if (! file_exists($targetPath)) {
            throw CouldNotTakeBrowsershot::chromeOutputEmpty($targetPath, $output, $command);
        }

        if (! $this->imageManipulations->isEmpty()) {
            $this->imageManipulations->apply($targetPath);
        }
    }

    public function bodyHtml(): string
    {
        $command = $this->createBodyHtmlCommand();

        $html = $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return $html;
    }

    public function base64Screenshot(): string
    {
        $command = $this->createScreenshotCommand();

        $encodedImage = $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return $encodedImage;
    }

    public function screenshot(): string
    {
        if ($this->imageManipulations->isEmpty()) {

            $command = $this->createScreenshotCommand();

            $encodedImage = $this->callBrowser($command);

            $this->cleanupTemporaryHtmlFile();

            return base64_decode($encodedImage);
        }

        $temporaryDirectory = (new TemporaryDirectory($this->tempPath))->create();

        $this->save($temporaryDirectory->path('screenshot.png'));

        $screenshot = file_get_contents($temporaryDirectory->path('screenshot.png'));

        $temporaryDirectory->delete();

        return $screenshot;

    }

    public function pdf(): string
    {
        $command = $this->createPdfCommand();

        $encodedPdf = $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return base64_decode($encodedPdf);
    }

    public function savePdf(string $targetPath)
    {
        $command = $this->createPdfCommand($targetPath);

        $output = $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        if (! file_exists($targetPath)) {
            throw CouldNotTakeBrowsershot::chromeOutputEmpty($targetPath, $output);
        }
    }

    public function base64pdf(): string
    {
        $command = $this->createPdfCommand();

        $encodedPdf = $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return $encodedPdf;
    }

    public function evaluate(string $pageFunction): string
    {
        $command = $this->createEvaluateCommand($pageFunction);

        $evaluation = $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return $evaluation;
    }

    /**
     * @return null|array{url: string}
     */
    public function triggeredRequests(): ?array
    {
        $requests = $this->chromiumResult?->getRequestsList();

        if (! is_null($requests)) {
            return $requests;
        }

        $command = $this->createTriggeredRequestsListCommand();

        $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return $this->chromiumResult?->getRequestsList();
    }

    /**
     * @return null|array{
     *     url: string,
     *     status: int,
     *     statusText: string,
     *     headers: array
     * }
     */
    public function redirectHistory(): ?array
    {
        $redirectHistory = $this->chromiumResult?->getRedirectHistory();

        if (! is_null($redirectHistory)) {
            return $redirectHistory;
        }

        $command = $this->createRedirectHistoryCommand();

        $this->callBrowser($command);

        return $this->chromiumResult?->getRedirectHistory();
    }

    /**
     * @return null|array{
     *     type: string,
     *     message: string,
     *     location:array
     * }
     */
    public function consoleMessages(): ?array
    {
        $messages = $this->chromiumResult?->getConsoleMessages();

        if (! is_null($messages)) {
            return $messages;
        }

        $command = $this->createConsoleMessagesCommand();

        $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return $this->chromiumResult?->getConsoleMessages();
    }

    /**
     * @return null|array{status: int, url: string}
     */
    public function failedRequests(): ?array
    {
        $requests = $this->chromiumResult?->getFailedRequests();

        if (! is_null($requests)) {
            return $requests;
        }

        $command = $this->createFailedRequestsCommand();

        $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return $this->chromiumResult?->getFailedRequests();
    }

    /**
     * @return null|array{name: string, message: string}
     */
    public function pageErrors(): ?array
    {
        $pageErrors = $this->chromiumResult?->getPageErrors();

        if (! is_null($pageErrors)) {
            return $pageErrors;
        }

        $command = $this->createPageErrorsCommand();

        $this->callBrowser($command);

        $this->cleanupTemporaryHtmlFile();

        return $this->chromiumResult?->getPageErrors();
    }

    public function createBodyHtmlCommand(): array
    {
        $url = $this->getFinalContentsUrl();

        return $this->createCommand($url, 'content');
    }

    public function createScreenshotCommand($targetPath = null): array
    {
        $url = $this->getFinalContentsUrl();

        $options = [
            'type' => $this->screenshotType,
        ];
        if ($targetPath) {
            $options['path'] = $targetPath;
        }

        if ($this->screenshotQuality) {
            $options['quality'] = $this->screenshotQuality;
        }

        $command = $this->createCommand($url, 'screenshot', $options);

        if (! $this->showScreenshotBackground) {
            $command['options']['omitBackground'] = true;
        }

        return $command;
    }

    public function createPdfCommand($targetPath = null): array
    {
        $url = $this->getFinalContentsUrl();

        $options = [];

        if ($targetPath) {
            $options['path'] = $targetPath;
        }

        $command = $this->createCommand($url, 'pdf', $options);

        if ($this->showBackground) {
            $command['options']['printBackground'] = true;
        }

        if ($this->transparentBackground) {
            $command['options']['omitBackground'] = true;
        }

        if ($this->taggedPdf) {
            $command['options']['tagged'] = true;
        }

        if ($this->scale) {
            $command['options']['scale'] = $this->scale;
        }

        return $command;
    }

    public function createEvaluateCommand(string $pageFunction): array
    {
        $url = $this->getFinalContentsUrl();

        $options = [
            'pageFunction' => $pageFunction,
        ];

        return $this->createCommand($url, 'evaluate', $options);
    }

    public function createTriggeredRequestsListCommand(): array
    {
        $url = $this->html
            ? $this->createTemporaryHtmlFile()
            : $this->url;

        return $this->createCommand($url, 'requestsList');
    }

    public function createRedirectHistoryCommand(): array
    {
        $url = $this->html
            ? $this->createTemporaryHtmlFile()
            : $this->url;

        return $this->createCommand($url, 'redirectHistory');
    }

    public function createConsoleMessagesCommand(): array
    {
        $url = $this->html
            ? $this->createTemporaryHtmlFile()
            : $this->url;

        return $this->createCommand($url, 'consoleMessages');
    }

    public function createFailedRequestsCommand(): array
    {
        $url = $this->html
            ? $this->createTemporaryHtmlFile()
            : $this->url;

        return $this->createCommand($url, 'failedRequests');
    }

    public function createPageErrorsCommand(): array
    {
        $url = $this->html
            ? $this->createTemporaryHtmlFile()
            : $this->url;

        return $this->createCommand($url, 'pageErrors');
    }

    public function setRemoteInstance(string $ip = '127.0.0.1', int $port = 9222): self
    {
        // assuring that ip and port does actually contains a value
        if ($ip && $port) {
            $this->setOption('remoteInstanceUrl', 'http://'.$ip.':'.$port);
        }

        return $this;
    }

    public function setWSEndpoint(string $endpoint): self
    {
        if (! is_null($endpoint)) {
            $this->setOption('browserWSEndpoint', $endpoint);
        }

        return $this;
    }

    public function usePipe(): self
    {
        $this->setOption('pipe', true);

        return $this;
    }

    public function setEnvironmentOptions(array $options = []): self
    {
        return $this->setOption('env', $options);
    }

    public function setContentUrl(string $contentUrl): self
    {
        return $this->html ? $this->setOption('contentUrl', $contentUrl) : $this;
    }

    protected function getOptionArgs(): array
    {
        $args = $this->chromiumArguments;

        if ($this->noSandbox) {
            $args[] = '--no-sandbox';
        }

        if ($this->proxyServer) {
            $args[] = '--proxy-server='.$this->proxyServer;
        }

        if ($this->deniedUrlsRegex) {
            $args[] = '--chromium-deny-list='.$this->deniedUrlsRegex;
        }

        return $args;
    }

    protected function createCommand(string $url, string $action, array $options = []): array
    {
        $command = compact('url', 'action', 'options');

        $command['options']['args'] = $this->getOptionArgs();

        if (! empty($this->postParams)) {
            $command['postParams'] = $this->postParams;
        }

        if (! empty($this->additionalOptions)) {
            $command['options'] = array_merge_recursive($command['options'], $this->additionalOptions);
        }

        return $command;
    }

    protected function createTemporaryHtmlFile(): string
    {
        $this->temporaryHtmlDirectory = (new TemporaryDirectory($this->tempPath))->create();

        file_put_contents($temporaryHtmlFile = $this->temporaryHtmlDirectory->path('index.html'), $this->html);

        return "file://{$temporaryHtmlFile}";
    }

    protected function cleanupTemporaryHtmlFile(): void
    {
        if ($this->temporaryHtmlDirectory) {
            $this->temporaryHtmlDirectory->delete();
        }
    }

    protected function createTemporaryOptionsFile(string $command): string
    {
        $this->temporaryOptionsDirectory = (new TemporaryDirectory($this->tempPath))->create();

        file_put_contents($temporaryOptionsFile = $this->temporaryOptionsDirectory->path('command.js'), $command);

        return "file://{$temporaryOptionsFile}";
    }

    protected function cleanupTemporaryOptionsFile(): void
    {
        if ($this->temporaryOptionsDirectory) {
            $this->temporaryOptionsDirectory->delete();
        }
    }

    protected function callBrowser(array $command): string
    {
        $fullCommand = $this->getFullCommand($command);

        $process = $this->isWindows() ? new Process($fullCommand, null, $this->getWindowsEnv()) : Process::fromShellCommandline($fullCommand);

        $process->setTimeout($this->timeout);

        // clear additional output data fetched on last browser request
        $this->chromiumResult = null;

        $process->run();

        $rawOutput = rtrim($process->getOutput());

        $this->chromiumResult = new ChromiumResult(json_decode($rawOutput, true));

        if ($process->isSuccessful()) {
            $result = $this->chromiumResult?->getResult();

            $this->cleanupTemporaryOptionsFile();

            return $result;
        }

        $this->cleanupTemporaryOptionsFile();
        $process->clearOutput();
        $exitCode = $process->getExitCode();
        $errorOutput = $process->getErrorOutput();

        if ($exitCode === 3) {
            throw UnsuccessfulResponse::make($this->url, $errorOutput ?? '');
        }

        if ($exitCode === 2) {
            throw ElementNotFound::make($this->additionalOptions['selector']);
        }

        throw new ProcessFailedException($process);
    }

    protected function getWindowsEnv(): array
    {
        return [
            'LOCALAPPDATA' => getenv('LOCALAPPDATA'),
            'Path' => getenv('Path'),
            'SystemRoot' => getenv('SystemRoot'),
            'USERPROFILE' => getenv('USERPROFILE'),
        ];
    }

    protected function getFullCommand(array $command): array|string
    {
        $nodeBinary = $this->nodeBinary ?: 'node';

        $binPath = $this->binPath ?: __DIR__.'/../bin/browser.cjs';

        $optionsCommand = $this->getOptionsCommand(json_encode($command));

        if ($this->isWindows()) {
            // on Windows we will let Symfony/process handle the command escaping
            // by passing an array to the process instance
            return [
                $nodeBinary,
                $binPath,
                $optionsCommand,
            ];
        }

        $setIncludePathCommand = "PATH={$this->includePath}";

        $setNodePathCommand = $this->getNodePathCommand($nodeBinary);

        return
            $setIncludePathCommand.' '
            .$setNodePathCommand.' '
            .$nodeBinary.' '
            .escapeshellarg($binPath).' '
            .$optionsCommand;
    }

    protected function getNodePathCommand(string $nodeBinary): string
    {
        if ($this->nodeModulePath) {
            return "NODE_PATH='{$this->nodeModulePath}'";
        }
        if ($this->npmBinary) {
            return "NODE_PATH=`{$nodeBinary} {$this->npmBinary} root -g`";
        }

        return 'NODE_PATH=`npm root -g`';
    }

    protected function getOptionsCommand(string $command): string
    {
        if ($this->writeOptionsToFile) {
            $temporaryOptionsFile = $this->createTemporaryOptionsFile($command);
            $command = "-f {$temporaryOptionsFile}";
        }

        if ($this->isWindows()) {
            return $command;
        }

        return escapeshellarg($command);
    }

    protected function arraySet(array &$array, string $key, mixed $value): array
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public function initialPageNumber(int $initialPage = 1): static
    {
        return $this
            ->setOption('initialPageNumber', ($initialPage - 1))
            ->pages("{$initialPage}-");
    }

    public function getOutput(): ?ChromiumResult
    {
        return $this->chromiumResult;
    }

    protected function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    protected function getFinalContentsUrl(): string
    {
        return $this->html
            ? $this->createTemporaryHtmlFile()
            : $this->url;
    }
}
