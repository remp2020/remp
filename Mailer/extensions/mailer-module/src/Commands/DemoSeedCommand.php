<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Users\Dummy;
use Remp\MailerModule\Repositories\ActiveRow;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListCategoriesRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\SnippetsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Remp\MailerModule\Repositories\UserSubscriptionsRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DemoSeedCommand extends Command
{
    private $layoutsRepository;

    private $templatesRepository;

    private $snippetsRepository;

    private $listsRepository;

    private $listCategoriesRepository;

    private UserSubscriptionsRepository $userSubscriptionsRepository;

    private Dummy $dummyUserProvider;

    public function __construct(
        LayoutsRepository $layoutsRepository,
        TemplatesRepository $templatesRepository,
        SnippetsRepository $snippetRepository,
        ListsRepository $listsRepository,
        ListCategoriesRepository $listCategoriesRepository,
        UserSubscriptionsRepository $userSubscriptionsRepository
    ) {
        parent::__construct();
        $this->layoutsRepository = $layoutsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->snippetsRepository = $snippetRepository;
        $this->listsRepository = $listsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
        $this->userSubscriptionsRepository = $userSubscriptionsRepository;
        $this->dummyUserProvider = new Dummy();
    }

    protected function configure(): void
    {
        $this->setName('demo:seed')
            ->setDescription('Seed database with demo values')
            ->addArgument(
                'delete',
                InputArgument::OPTIONAL,
                'Remove seed data?',
                false
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deleteSeedData = $input->getArgument('delete');
        if ($deleteSeedData === 'delete') {
            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to delete seed data? ', false);
            if ($helper->ask($input, $output, $question)) {
                $output->writeln('<info>***** WARNING: Deleting Seed Data *****</info>');
                $this->deleteSeedData();
            }
            return 0;
        }

        $output->writeln('');
        $output->writeln('<info>***** DEMO SEEDER *****</info>');
        $output->writeln('');

        $output->write('List categories: ');
        $category = $this->seedListCategories();
        $output->writeln('<info>OK!</info>');

        $output->write('Lists: ');
        $list = $this->seedLists($category);
        $output->writeln('<info>OK!</info>');

        $output->write('Snippets: ');
        $snippet = $this->seedSnippets();
        $output->writeln('<info>OK!</info>');

        $output->write('Layouts: ');
        $layout = $this->seedLayouts();
        $output->writeln('<info>OK!</info>');
        
        $output->write('Emails: ');
        $this->seedEmails($layout, $list);
        $output->writeln('<info>OK!</info>');

        $output->write('User subscriptions: ');
        $this->seedUserSubscriptions($list);
        $output->writeln('<info>OK!</info>');

        return Command::SUCCESS;
    }

    protected function seedListCategories()
    {
        /** @var ActiveRow $category */
        $category = $this->listCategoriesRepository->findBy('title', 'Newsletters');
        if (!$category) {
            $category = $this->listCategoriesRepository->insert([
                'title' => 'Newsletters',
                'sorting' => 100,
                'created_at' => new DateTime(),
            ]);
        }

        return $category;
    }

    protected function seedLists($category)
    {
        /** @var ActiveRow $list */
        $list = $this->listsRepository->findBy('code', 'demo-weekly-newsletter');
        if (!$list) {
            $list = $this->listsRepository->add(
                $category->id,
                100,
                'demo-weekly-newsletter',
                'DEMO Weekly newsletter',
                100,
                false,
                false,
                'Example mail list'
            );
        }

        return $list;
    }

    protected function seedSnippets()
    {
        $snippet = $this->snippetsRepository->findBy('code', 'demo-snippet');
        $snippetEmail = $this->snippetsRepository->findBy('code', 'demo-snippet-email');
        if ($snippet && $snippetEmail) {
            return true;
        }
        $snippetText = <<<EOF
            Remp co.
            Street 123
            Lorem City
            987 654
            EOF;
        $snippetHtml = <<<HTML
            <p>
                <strong>Remp co.</strong> <br>
                Street <em>123</em> <br>
                Lorem City <br>
                987 654 <br>
            </p>
            HTML;

        $emailSnippetText = <<<EOF
            Written by: John Editor in Chief  Doe
            EOF;
        $emailSnippetHtml = <<<HTML
            <p>Written by: <em>John Editor in Chief Doe</em></p>
            HTML;

        $snippet = $this->snippetsRepository->add('Demo snippet', 'demo-snippet', $snippetText, $snippetHtml, null);
        $snippetEmail = $this->snippetsRepository->add('Demo Snippet Email', 'demo-snippet-email', $emailSnippetText, $emailSnippetHtml, null);

        return true;
    }

    protected function seedLayouts()
    {
        /** @var ActiveRow $layout */
        $layout = $this->layoutsRepository->findBy('name', 'DEMO layout');
        if ($layout) {
            return $layout;
        }

        $text = <<<EOF
            REMP - Reader's Engagement and Monetization Platform
            
            {{ content|raw }}
            
            &copy; REMP, {{ time|date('Y') }}
            EOF;
        $html = <<<HTML
            <!DOCTYPE html>
            <html lang="en" dir="ltr" xmlns="http://www.w3.org/1999/xhtml">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <meta charset="utf-8" />
                    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                    <title>REMP - Demo email</title>
                </head>
                <body style="-webkit-size-adjust: none; -ms-text-size-adjust: 100%; width: 400px; max-width: 400px;">
                    <h2>REMP - Reader's Engagement and Monetization Platform</h2>
                    {{ content|raw }}     
                    <p><em><small>&copy; REMP, {{ time|date('Y') }}</small></em></p>
                    {{ include('demo-snippet') }}
                </body>
            </html>
            HTML;
        $layout = $this->layoutsRepository->add('DEMO layout', 'demo_layout', $text, $html);

        return $layout;
    }

    protected function seedEmails($layout, $list)
    {
        /** @var ActiveRow $email */
        $email = $this->templatesRepository->findBy('code', 'demo-email');
        if (!$email) {
            $text = <<<EOF
                Text content of DEMO email.
                EOF;
            $html = <<<HTML
                <table style="width: 400px; background-color: #eee">
                    <tr>
                        <td>Text content of <em>DEMO email</em>.</td>
                    </tr>
                    <tr>
                        <td>{{ include('demo-snippet-email') }}</td>
                    </tr>
                </table>
                HTML;

            $this->templatesRepository->add(
                'DEMO email',
                'demo-email',
                'Demonstration-purpose email',
                'REMP <info@remp2020.com>',
                'REMP - DEMO',
                $text,
                $html,
                $layout->id,
                $list->id
            );
        }

        return $email;
    }

    protected function seedUserSubscriptions($list)
    {
        $users = $this->dummyUserProvider->list([], 1);
        foreach ($users as $userId => $userData) {
            $this->userSubscriptionsRepository->subscribeUser($list, $userId, $userData['email']);
        }
    }

    protected function deleteSeedData()
    {
        // user subscriptions
        $users = $this->dummyUserProvider->list([], 1);
        foreach ($users as $userId => $userData) {
            $this->userSubscriptionsRepository->getTable()->where([
                'user_id' => $userId,
                'user_email' => $userData['email'],
            ])->delete();
        }
        
        if ($email = $this->templatesRepository->findBy('code', 'demo-email')) {
            $this->templatesRepository->delete($email);
        }
        if ($layout = $this->layoutsRepository->findBy('name', 'DEMO layout')) {
            $this->layoutsRepository->delete($layout);
        }
        if ($list = $this->listsRepository->findBy('code', 'demo-weekly-newsletter')) {
            $this->listsRepository->delete($list);
        }
        if ($category = $this->listCategoriesRepository->findBy('title', 'Newsletters')) {
            $this->listCategoriesRepository->delete($category);
        }
        if ($snippet = $this->snippetsRepository->findBy('name', 'demo-snippet')) {
            $this->snippetsRepository->delete($snippet);
        }
        if ($snippetEmail = $this->snippetsRepository->findBy('name', 'demo-snippet-email')) {
            $this->snippetsRepository->delete($snippetEmail);
        }
    }
}
