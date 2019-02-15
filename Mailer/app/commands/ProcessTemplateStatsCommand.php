<?php

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessTemplateStatsCommand extends Command
{
    /**
     * @var LogsRepository
     */
    private $logsRepository;

    /**
     * @var TemplatesRepository
     */
    private $templatesRepository;

    public function __construct(
        LogsRepository $logsRepository,
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();
        $this->logsRepository = $logsRepository;
        $this->templatesRepository = $templatesRepository;
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

        $templates = $this->templatesRepository->getTable()
            ->select('id')
            ->fetchAll();

        ProgressBar::setFormatDefinition(
            'processStats',
            "%processing% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
        );

        $progressBar = new ProgressBar($output, count($templates));
        $progressBar->setFormat('processStats');
        $progressBar->start();

        foreach ($templates as $template) {
            $progressBar->setMessage('Processing template <info>' . $template->id . '</info>', 'processing');
            $toUpdate = [
                'sent' => 0,
                'delivered' => 0,
                'opened' => 0,
                'clicked' => 0,
                'dropped' => 0,
                'spam_complained' => 0,
            ];

            $jobBatchTemplates = $template->related('mail_job_batch_templates')->fetchAll();
            foreach ($jobBatchTemplates as $jobBatchTemplate) {
                $toUpdate['sent'] += $jobBatchTemplate['sent'];
                $toUpdate['delivered'] += $jobBatchTemplate['delivered'];
                $toUpdate['opened'] += $jobBatchTemplate['opened'];
                $toUpdate['clicked'] += $jobBatchTemplate['clicked'];
                $toUpdate['dropped'] += $jobBatchTemplate['dropped'];
                $toUpdate['spam_complained'] += $jobBatchTemplate['spam_complained'];
            }

            $nonBatchTemplateStats = $this->logsRepository->getNonBatchTemplateStats($template->id);
            $toUpdate['sent'] += $nonBatchTemplateStats['sent'];
            $toUpdate['delivered'] += $nonBatchTemplateStats['delivered'];
            $toUpdate['opened'] += $nonBatchTemplateStats['opened'];
            $toUpdate['clicked'] += $nonBatchTemplateStats['clicked'];
            $toUpdate['dropped'] += $nonBatchTemplateStats['dropped'];
            $toUpdate['spam_complained'] += $nonBatchTemplateStats['spam_complained'];

            $this->templatesRepository->update($template, $toUpdate);
            $progressBar->advance();
        }

        $progressBar->setMessage('done');
        $progressBar->finish();

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }
}
