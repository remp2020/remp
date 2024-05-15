<?php

namespace Remp\MailerModule\Commands;

use Nette\DI\Container;
use Nette\Utils\DateTime;
use Remp\MailerModule\Models\DataRetentionInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupCommand extends Command
{
    public function __construct(private Container $container)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('application:cleanup')
            ->setDescription('Cleanup old data based on configured retention rules');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('%s: <info>DATA RETENTION</info>', new DateTime()));

        $dataRetentionServices = $this->container->findByType(DataRetentionInterface::class);

        foreach ($dataRetentionServices as $dataRetentionService) {
            /** @var DataRetentionInterface $dataRetentionHandler */
            $dataRetentionHandler = $this->container->getService($dataRetentionService);
            $output->write(sprintf(
                '%s:   * %s',
                new DateTime(),
                get_class($dataRetentionHandler)
            ),);
            $deletedRowsCount = $dataRetentionHandler->removeData();
            $output->writeln(sprintf(" OK! (%s)", $deletedRowsCount ?? 'keeping data forever'));
        }

        return self::SUCCESS;
    }
}
