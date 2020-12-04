<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tomaj\Hermes\Emitter;
use Tomaj\Hermes\Message;

class HeartbeatCommand extends Command
{
    private $emitter;

    public function __construct(Emitter $emitter)
    {
        parent::__construct();
        $this->emitter = $emitter;
    }

    protected function configure()
    {
        $this->setName('application:heartbeat')
            ->setDescription('Run heartbeat hermes worker')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->emitter->emit(new Message('heartbeat', ['executed' => time()]));
        return 0;
    }
}
