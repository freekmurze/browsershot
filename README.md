# Convert a webpage to an image or pdf using headless Chrome

[![Latest Version](https://img.shields.io/github/release/spatie/browsershot.svg?style=flat-square)](https://github.com/spatie/browsershot/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/spatie/browsershot/master.svg?style=flat-square)](https://travis-ci.org/spatie/browsershot)
[![StyleCI](https://styleci.io/repos/19386515/shield?branch=master)](https://styleci.io/repos/19386515)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/9c1184fb-1edb-41d5-9d30-2620d99447c7.svg?style=flat-square)](https://insight.sensiolabs.com/projects/9c1184fb-1edb-41d5-9d30-2620d99447c7)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/browsershot.svg?style=flat-square)](https://packagist.org/packages/spatie/browsershot)

The package can convert a webpage to an image or pdf. The conversion is done behind the screens by Google Chrome.

Here's a quick example:

```php
use Spatie\Browsershot\Browsershot;

// an image will be saved
Browsershot::url('https://example.com')->save($pathToImage);
```

It will save a pdf if the path passed to the `save` method has a `pdf` extension.

```php
// a pdf will be saved
Browsershot::url('https://example.com')->save('example.pdf');
```

You can also use an arbitrary html input, simply replace the `url` method with `html`:

```php
Browsershot::html('<h1>Hello world!!</h1>')->save('example.pdf');
```

Browsershot also can get the body of an html page after JavaScript has been executed:

```php
Browsershot::url('https://example.com')->bodyHtml(); // returns the html of the body
```

Spatie is a webdesign agency in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## Postcardware

You're free to use this package (it's [MIT-licensed](LICENSE.md)), but if it makes it to your production environment we highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using.

Our address is: Spatie, Samberstraat 69D, 2060 Antwerp, Belgium.

All postcards are published [on our website](https://spatie.be/en/opensource/postcards).

## Requirements

This package requires Node 7.6.0 or higher and the Puppeteer Node library installable via NPM.

On a [Forge](https://forge.laravel.com) provisioned Ubuntu 16.04 server you can install the latest stable version of Chrome like this:

```bash
curl -sL https://deb.nodesource.com/setup_8.x | sudo -E bash -
sudo apt-get install -y nodejs gconf-service libasound2 libatk1.0-0 libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgcc1 libgconf-2-4 libgdk-pixbuf2.0-0 libglib2.0-0 libgtk-3-0 libnspr4 libpango-1.0-0 libpangocairo-1.0-0 libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 ca-certificates fonts-liberation libappindicator1 libnss3 lsb-release xdg-utils wget
sudo npm install --global --unsafe-perm puppeteer
sudo chmod -R o+rx /usr/lib/node_modules/puppeteer/.local-chromium
```

## Installation

This package can be installed through Composer.

```bash
composer require spatie/browsershot
```

## Usage

In all examples it is assumed that you imported this namespace at the top of your file

```php
use Spatie\Browsershot\Browsershot;
```

Here's the easiest way to create an image of a webpage:

```php
Browsershot::url('https://example.com')->save($pathToImage);
```

By default the screenshot will be a `png` and it's size will match the resolution you use for your desktop. Want another size of screenshot? No problem!

```php
Browsershot::url('https://example.com')
    ->windowSize(640, 480)
    ->save($pathToImage);
```

You can also set the size of the output image independently of the size of window. Here's how to resize a screenshot take with a resolution of 1920x1080 and scale that down to something that fits inside 200x200.

```php
Browsershot::url('https://example.com')
    ->windowSize(1920, 1080)
    ->fit(Manipulations::FIT_CONTAIN, 200, 200)
    ->save($pathToImage);
```

You can also capture the webpage at higher pixel densities by passing a device scale factor value of 2 or 3. This mimics how the webpage would be displayed on a retina/xhdpi display.

```php
Browsershot::url('https://example.com')
    ->deviceScaleFactor(2)
    ->save($pathToImage);
```

In fact, you can use all the methods [spatie/image](https://docs.spatie.be/image/v1) provides. Here's an example where we create a greyscale image:

```php
Browsershot::url('https://example.com')
    ->windowSize(640, 480)
    ->greyscale()
    ->save($pathToImage);
```

If, for some reason, you want to set the user agent Google Chrome should use when taking the screenshot you can do so:

```php
Browsershot::url('https://example.com')
    ->userAgent('My Special Snowflake Browser 1.0')
    ->save($pathToImage);
```

The default timeout of Browsershot is set to 60 seconds. Of course, you can modify this timeout:

```php
Browsershot::url('https://example.com')
    ->timeout(120)
    ->save($pathToImage);
```

Browsershot will save a pdf if the path passed to the `save` method has a `pdf` extension.

```php
// a pdf will be saved
Browsershot::url('https://example.com')->save('example.pdf');
```

Alternatively you can explicitly use the `savePdf` method:

```php
Browsershot::url('https://example.com')->savePdf('example.pdf');
```

Browsershot also can get the body of an html page after JavaScript has been executed:

```php
Browsershot::url('https://example.com')->bodyHtml(); // returns the html of the body
```

### Additional options supported by Browsershot

#### Screenshots

- `clip(int $x, int $y, int $width, int $height)` - clip a region of the page
- `fullPage()` - takes screenshot of the full scrollable page

#### PDFs

- `browserHeaderAndFooter(bool $show = true)` - show/hide the browser header and footer
- `includeBackground(bool $include = true)` - include/exclude backgrounds
- `landscape()` - landscape page orientation
- `pages(string $pages)` - printed page ranges, eg. `1-5, 8, 11-13`
- `pageSize(int $width, int $height)` - page width/height in millimetres
- `margins(int $top, int $right, int $bottom, int $left)` - page top/right/bottom/left margin in millimetres

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Alternatives

If you're not able to install Node and Puppeteer, take a look at [v2 of browserhot](https://github.com/spatie/browsershot/tree/2.4.1), which uses Chrome headless CLI to take a screenshot. `v2` is not maintained anymore, but should work pretty well

## Credits

- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## About Spatie
Spatie is a webdesign agency in Antwerp, Belgium. You'll find an overview of all our open source projects [on our website](https://spatie.be/opensource).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
