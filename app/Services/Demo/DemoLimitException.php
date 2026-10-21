<?php

namespace App\Services\Demo;

use RuntimeException;

/** A demo cannot be started right now; the message is safe to show the visitor. */
final class DemoLimitException extends RuntimeException
{
}
