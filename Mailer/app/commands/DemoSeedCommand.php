<?php

namespace Remp\MailerModule\Commands;

use Nette\Database\Table\ActiveRow;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\ListCategoriesRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DemoSeedCommand extends Command
{
    private $layoutsRepository;

    private $templatesRepository;

    private $listsRepository;

    private $listCategoriesRepository;

    public function __construct(
        LayoutsRepository $layoutsRepository,
        TemplatesRepository $templatesRepository,
        ListsRepository $listsRepository,
        ListCategoriesRepository $listCategoriesRepository
    ) {
        parent::__construct();
        $this->layoutsRepository = $layoutsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->listsRepository = $listsRepository;
        $this->listCategoriesRepository = $listCategoriesRepository;
    }

    protected function configure()
    {
        $this->setName('seed:demo')
            ->setDescription('Seed database with demo values');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** DEMO SEEDER *****</info>');
        $output->writeln('');

        // list category
        $output->write('List categories: ');
        /** @var ActiveRow $category */
        $category = $this->listCategoriesRepository->findBy('title', 'Newsletters');
        if (!$category) {
            $category = $this->listCategoriesRepository->insert([
                'title' => 'Newsletters',
                'sorting' => 100,
                'created_at' => new \DateTime(),
            ]);
        }
        $output->writeln('<info>OK!</info>');

        // list
        $output->write('Lists: ');
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
                true,
                'Example mail list'
            );
        }
        $output->writeln('<info>OK!</info>');

        // layout
        $output->write('Layouts: ');
        /** @var ActiveRow $layout */
        $layout = $this->layoutsRepository->findBy('name', 'DEMO layout');
        if (!$layout) {
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
	</body>
</html>
HTML;
            $layout = $this->layoutsRepository->add('DEMO layout', $text, $html);
        }
        $output->writeln('<info>OK!</info>');

        // email
        $output->write('Emails: ');
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
        $output->writeln('<info>OK!</info>');
    }
}
