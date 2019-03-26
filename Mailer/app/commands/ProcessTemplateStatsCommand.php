<?php

namespace Remp\MailerModule\Commands;

use Nette\Database\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessTemplateStatsCommand extends Command
{
    private $database;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();

        $this->database = $connection;
    }

    protected function configure()
    {
        $this->setName('mail:template-stats')
            ->setDescription('Process template stats based on batch stats and mail logs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** UPDATE EMAIL TEMPLATE STATS *****</info>');
        $output->writeln('');

        $this->database->query('
            UPDATE mail_templates as dest,
              (SELECT mts.mail_template_id,
                      SUM(mts.sent)            as sent,
                      SUM(mts.delivered)       as delivered,
                      SUM(mts.opened)          as opened,
                      SUM(mts.clicked)         as clicked,
                      SUM(mts.dropped)         as dropped,
                      SUM(mts.spam_complained) as spam_complained
            
               FROM mail_template_stats mts
               GROUP BY mts.mail_template_id
              ) src
            SET dest.sent            = src.sent,
                dest.delivered       = src.delivered,
                dest.opened          = src.opened,
                dest.clicked         = src.clicked,
                dest.dropped         = src.dropped,
                dest.spam_complained = src.spam_complained
            WHERE dest.id = src.mail_template_id;
        ');

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }
}
