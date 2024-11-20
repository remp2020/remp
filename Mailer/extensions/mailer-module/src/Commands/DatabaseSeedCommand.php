<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\Models\ContentGenerator\Replace\RtmClickReplace;
use Remp\MailerModule\Models\Mailer\SmtpMailer;
use Remp\MailerModule\Repositories\ConfigsRepository;
use Remp\MailerModule\Repositories\ListCategoriesRepository;
use Remp\MailerModule\Repositories\SourceTemplatesRepository;
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

    protected function configure(): void
    {
        $this->setName('db:seed')
            ->setDescription('Seed database with required values');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<info>***** APPLICATION SEEDER *****</info>');
        $output->writeln('');

        $output->writeln('Required configuration: ');
        $configValues = [
            ['default_mailer', 'Default Mailer', SmtpMailer::ALIAS, '', 'string'],
            [RtmClickReplace::CONFIG_NAME, 'Mail click tracker', false, '', 'boolean'],
            ['one_click_unsubscribe', 'One-Click Unsubscribe', false, 'If enabled, adds <code class="muted">List-Unsubscribe=One-Click</code> header to emails that signals support for One-Click unsubscribe from the newsletters according to RFC 8058. The unsubscribe URL must support the POST method, must immediately unsubscribe the user from the newsletter, must not use redirection or any additional user verification; all the necessary data for unsubscribing must be part of the URL address.', 'boolean']
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
            ['title' => 'Newsletters', 'code' => 'newsletters', 'sorting' => 100],
            ['title' => 'System', 'code' => 'system', 'sorting' => 999],
        ];
        $output->writeln('Newsletter list categories:');
        foreach ($listCategories as $category) {
            if ($this->listCategoriesRepository->getTable()->where(['code' => $category['code']])->count('*') > 0) {
                $output->writeln(" * Newsletter list <info>{$category['title']}</info> exists");
                continue;
            }
            $this->listCategoriesRepository->add($category['title'], $category['code'], $category['sorting']);
            $output->writeln(" * Newsletter list <info>{$category['title']}</info> created");
        }

        $output->writeln('Generator templates:');
        $bestPerformingArticleHtml = <<<HTML
            <table cellpadding="10">        
            {% for url,item in items %}
                <tr>
                    <td>
                        <table cellpadding="10" style="border-bottom: 2px solid #efe5e5;">
                            <tr>
                                <td colspan="2"><strong>{{ item.title }}</strong></td>
                            </tr>
                            <tr>
                                <td><img style="max-height: 100px;" src="{{item.image}}"></td>
                                <td>{{ item.description }}</td>
                            </tr>
                            <tr>
                                <td colspan="2"><a href="{{ url }}">{{ url }}</a></td>
                            </tr>
                        </table>
                    </td>
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
            [
                'title' => 'Best performing articles',
                'code' => 'best-performing-articles',
                'generator' => 'best_performing_articles',
                'sorting' => 100,
                'html' => $bestPerformingArticleHtml,
                'text' => $bestPerformingArticleText
            ]
        ];
        foreach ($generatorTemplates as $template) {
            if ($this->sourceTemplatesRepository->getTable()->where(['title' => $template['title']])->count('*') > 0) {
                $output->writeln(" * Generator template <info>{$template['title']}</info> exists");
                continue;
            }
            $this->sourceTemplatesRepository->add(
                $template['title'],
                $template['code'],
                $template['generator'],
                $template['html'],
                $template['text'],
                $template['sorting']
            );
            $output->writeln(" * Generator template <info>{$template['title']}</info> created");
        }

        $output->writeln('<info>OK!</info>');

        return Command::SUCCESS;
    }
}
