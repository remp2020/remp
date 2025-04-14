<?php

namespace App\Console;

use Illuminate\Console\Command as ConsoleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends ConsoleCommand
{
    public function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $memoryLimits = config('system.commands_memory_limits');
        if (isset($memoryLimits[$this->getName()])) {
            ini_set('memory_limit', $memoryLimits[$this->getName()]);
        }
    }
}
