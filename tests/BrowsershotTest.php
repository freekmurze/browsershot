<?php

namespace Spatie\Browsershot\Test;

use PHPUnit\Framework\TestCase;
use Spatie\Browsershot\Browsershot;

class BrowsershotTest extends TestCase
{
    /** @test */
    public function it_can_take_a_screenshot()
    {
        Browsershot::url('https://spatie.be')
            ->windowsize(100, 200)
            ->save('tests/my_screenshot.png');
    }
}