<?php

namespace Remp\MailerModule\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelloWorldCommand extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('hello')
            ->setDescription('Displays <info>Hello world</info> message')
            ->addArgument('name', InputArgument::OPTIONAL, 'Your name', 'WORLD');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        
        $output->writeln('');
        $output->writeln('<info>***** HELLO ' . strtoupper($name) . ' *****</info>');
        $output->writeln('');
    }
}
