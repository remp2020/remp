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
              (SELECT mail_template_id, sent, delivered, opened, clicked, dropped, spam_complained
               FROM (
                      SELECT mjbt.mail_template_id,
                             SUM(mjbt.sent)            as sent,
                             SUM(mjbt.delivered)       as delivered,
                             SUM(mjbt.opened)          as opened,
                             SUM(mjbt.clicked)         as clicked,
                             SUM(mjbt.dropped)         as dropped,
                             SUM(mjbt.spam_complained) as spam_complained
            
                      FROM mail_job_batch_templates mjbt
                      GROUP BY mjbt.mail_template_id
            
                      UNION ALL
            
                      SELECT mtad.mail_template_id,
                             SUM(mtad.sent)            as sent,
                             SUM(mtad.delivered)       as delivered,
                             SUM(mtad.opened)          as opened,
                             SUM(mtad.clicked)         as clicked,
                             SUM(mtad.dropped)         as dropped,
                             SUM(mtad.spam_complained) as spam_complained
            
                      FROM mail_templates_aggregated_data mtad
                      GROUP BY mtad.mail_template_id
                    ) a
            
               GROUP BY mail_template_id
               ORDER BY mail_template_id DESC) as src
            SET
              dest.sent = src.sent,
              dest.delivered = src.delivered,
              dest.opened = src.opened,
              dest.clicked = src.clicked,
              dest.dropped = src.dropped,
              dest.spam_complained = src.spam_complained
            WHERE
                dest.id = src.mail_template_id;
        ');

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }
}
