<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * ConsoleOutput that prefixes every non-empty line with a `[Y-m-d H:i:s]` timestamp,
 * so long-running commands can be paced from their CLI output without every
 * call site having to time-stamp itself. Blank lines are left untouched.
 */
class TimestampedConsoleOutput extends ConsoleOutput
{
    protected function doWrite(string $message, bool $newline): void
    {
        if ($message !== '') {
            $message = '[' . (new \DateTime())->format('Y-m-d H:i:s') . '] ' . $message;
        }
        parent::doWrite($message, $newline);
    }
}
