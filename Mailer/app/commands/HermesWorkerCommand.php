<?php

namespace Remp\MailerModule\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tomaj\Hermes\Dispatcher;
use Tomaj\Hermes\Handler\HandlerInterface;

class HermesWorkerCommand extends Command
{
    private $dispatcher;

    private $handlers = [];

    public function __construct(Dispatcher $dispatcher)
    {
        parent::__construct();
        $this->dispatcher = $dispatcher;
    }

    public function add($type, HandlerInterface $handler)
    {
        $this->handlers[$type] = get_class($handler);
        $this->dispatcher->registerHandler($type, $handler);
    }

    protected function configure()
    {
        $this->setName('hermes:worker')
            ->setDescription('Hermes offline worker');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** REMP MAILER HERMES WORKER *****</info>');
        $output->writeln('');
        $output->writeln('Listening to:');

        foreach ($this->handlers as $type => $handler) {
            $output->writeln(sprintf('  - <info>%s</info>: %s', $type, $handler));
        }
        $output->writeln('');

        $this->dispatcher->handle();
    }
}
