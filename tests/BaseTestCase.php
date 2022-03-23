<?php
declare(strict_types=1);

namespace Bortoman\Tests;

use Orchestra\Testbench\TestCase;

class BaseTestCase extends TestCase
{
    use AccessibleProtectedMethodsTrait;
}
