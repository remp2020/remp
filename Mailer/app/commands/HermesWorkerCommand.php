<?php

namespace Remp\MailerModule\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        // we store the handlers too so we can print list of them later; dispatcher doesn't provide a list method
        if (!isset($this->handlers[$type])) {
            $this->handlers[$type] = [];
        }
        $this->handlers[$type][] = $handler;
    }

    protected function configure()
    {
        $this->setName('worker:hermes')
            ->setDescription('Start hermes offline worker')
            ->addOption(
                'types',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Filter type of events to handle by this worker.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** REMP MAILER HERMES WORKER *****</info>');
        $output->writeln('');

        $filteredTypes = $input->getOption('types');
        $registered = [];
        foreach ($this->handlers as $type => $handlers) {
            if (!empty($filteredTypes) && !in_array($type, $filteredTypes)) {
                continue;
            }

            foreach ($handlers as $handler) {
                $this->dispatcher->registerHandler($type, $handler);
                $registered[] = sprintf('  - <info>%s</info>: %s', $type, get_class($handler));
            }
        }

        if (count($registered) === 0) {
            $output->writeln('No handler matched the filtered event types, nothing to do. Bye bye!');
            $output->writeln('');
            return 0;
        }

        $output->writeln('Listening to:');
        $output->writeln(implode("\n", $registered));
        $output->writeln('');

        $this->dispatcher->handle();
        return 0;
    }
}
