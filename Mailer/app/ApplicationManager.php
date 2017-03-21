<?php

namespace Remp\MailerModule;

class ApplicationManager
{
    private $commands = [];

    public function addCommand($command)
    {
        $this->commands[] = $command;
    }

    public function getCommands()
    {
        return $this->commands;
    }
}
