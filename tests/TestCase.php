<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI runs the test job without building frontend assets, so pages
        // rendered through the vite manifest would 500 there.
        $this->withoutVite();
    }
}
