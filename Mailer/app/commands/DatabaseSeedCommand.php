<?php

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\Repository\ConfigsRepository;
use Remp\MailerModule\Repository\ListCategoriesRepository;
use Remp\MailerModule\Repository\SourceTemplatesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseSeedCommand extends Command
{
    private $configsRepository;

    private $listCategoriesRepository;

    private $sourceTemplatesRepository;

    public function __construct(
        ConfigsRepository $configsRepository,
        ListCategoriesRepository $listCategoriesRepository,
        SourceTemplatesRepository $sourceTemplatesRepository
    ) {
        parent::__construct();
        $this->configsRepository = $configsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
        $this->sourceTemplatesRepository = $sourceTemplatesRepository;
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

        $output->writeln('Generator templates:');
        $bestPerformingArticleHtml = <<<HTML
<table>        
{% for url,item in items %}
    <tr>
        <td>{{ item.title }}</td>
        <td>{{ item.description }}</td>
        <td><img src="{{item.image}}"></td>
        <td>{{ url }}</td>
    </tr>
{% endfor %}
</table>
HTML;
        $bestPerformingArticleText = <<<TEXT
{% for url,item in items %}
{{ item.title }}
{{ item.description }}
{{ url}}
{% endfor %}
TEXT;
        $generatorTemplates = [
            ['title' => 'Best performing articles', 'generator' => 'best_performing_articles', 'sorting' => 100,
             'html' => $bestPerformingArticleHtml, 'text' => $bestPerformingArticleText]
        ];
        foreach ($generatorTemplates as $template) {
            if ($this->sourceTemplatesRepository->getTable()->where(['title' => $template['title']])->count('*') > 0) {
                $output->writeln(" * Generator template <info>{$template['title']}</info> exists");
                continue;
            }
            $this->sourceTemplatesRepository->add(
                $template['title'],
                $template['generator'],
                $template['html'],
                $template['text'],
                $template['sorting']
            );
            $output->writeln(" * Generator template <info>{$template['title']}</info> created");
        }

        $output->writeln('<info>OK!</info>');
    }
}
