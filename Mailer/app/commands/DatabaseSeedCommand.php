<?php

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\Repository\ConfigsRepository;
use Remp\MailerModule\Repository\ListCategoriesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseSeedCommand extends Command
{
    private $configsRepository;

    private $listCategoriesRepository;

    public function __construct(
        ConfigsRepository $configsRepository,
        ListCategoriesRepository $listCategoriesRepository
    ) {
        parent::__construct();
        $this->configsRepository = $configsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
    }

    protected function configure()
    {
        $this->setName('db:seed')
            ->setDescription('Seed database with required values');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** APPLICATION SEEDER *****</info>');
        $output->writeln('');

        $output->writeln('Required configuration: ');
        $configValues = [
            ['default_mailer', 'Default Mailer', null, '', 'string'],
        ];
        foreach ($configValues as $configValue) {
            $config = $this->configsRepository->findBy('name', $configValue['0']);
            if (!$config) {
                $config = $this->configsRepository->add(
                    $configValue[0],
                    $configValue[1],
                    $configValue[2],
                    $configValue[3],
                    $configValue[4]
                );
                $output->writeln(" * Config <info>{$configValue['0']}</info> created");
            } else {
                $output->writeln(" * Config <info>{$configValue['0']}</info> exists");
            }
        }

        $listCategories = [
            ['title' => 'Newsletters', 'sorting' => 100],
            ['title' => 'System', 'sorting' => 999],
        ];
        $output->writeln('Newsletter list categories:');
        foreach ($listCategories as $category) {
            if ($this->listCategoriesRepository->getTable()->where(['title' => $category['title']])->count('*') > 0) {
                $output->writeln(" * Newsletter list <info>{$category['title']}</info> exists");
                continue;
            }
            $this->listCategoriesRepository->add($category['title'], $category['sorting']);
            $output->writeln(" * Newsletter list <info>{$category['title']}</info> created");
        }

        $output->writeln('<info>OK!</info>');
    }
}
