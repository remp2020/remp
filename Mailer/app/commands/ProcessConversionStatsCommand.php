<?php

namespace Remp\MailerModule\Commands;

use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\IConversionsRepository;
use Remp\MailerModule\Repository\LogConversionsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\User\IUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessConversionStatsCommand extends Command
{
    private $conversionsRepository;

    private $templatesRepository;

    private $batchTemplatesRepository;

    private $userProvider;

    private $logsRepository;

    private $logConversionsRepository;

    public function __construct(
        IConversionsRepository $conversionsRepository,
        TemplatesRepository $templatesRepository,
        BatchTemplatesRepository $batchTemplatesRepository,
        IUser $userProvider,
        LogsRepository $logsRepository,
        LogConversionsRepository $logConversionsRepository
    ) {
        parent::__construct();
        $this->conversionsRepository = $conversionsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->userProvider = $userProvider;
        $this->logsRepository = $logsRepository;
        $this->logConversionsRepository = $logConversionsRepository;
    }

    protected function configure()
    {
        $this->setName('mail:conversion-stats')
            ->setDescription('Process job stats based on conversion data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('<info>***** UPDATE EMAIL CONVERSION STATS *****</info>');
        $output->writeln('');

        ProgressBar::setFormatDefinition(
            'processStats',
            "%processing% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%"
        );

        // batch template conversions (from jobs)

        $batchTemplates = $this->batchTemplatesRepository->getTable()
            ->where('created_at > ?', DateTime::from('-1 month'))
            ->fetchAll();

        $progressBar = new ProgressBar($output, count($batchTemplates));
        $progressBar->setFormat('processStats');
        $progressBar->start();

        /** @var ActiveRow $batchTemplate */
        foreach ($batchTemplates as $batchTemplate) {
            $progressBar->setMessage('Processing jobBatchTemplate <info>' . $batchTemplate->id . '</info>', 'processing');

            $batchTemplateConversions = $this->conversionsRepository->getBatchTemplateConversions($batchTemplate->mail_job_batch->id, $batchTemplate->mail_template->code);
            $userData = $this->getUserData(array_keys($batchTemplateConversions));

            foreach ($batchTemplateConversions as $userId => $time) {
                $log = $this->logsRepository->find(
                    $this->logsRepository->getTable()
                        ->select('MAX(id)')
                        ->where([
                            'email' => $userData[$userId],
                            'mail_template_id' => $batchTemplate->mail_template_id,
                            'mail_job_batch_id' => $batchTemplate->mail_job_batch_id,
                        ])
                        ->where('created_at < ?', DateTime::from($time))
                );
                if (!$log) {
                    continue;
                }
                $this->logConversionsRepository->upsert($log, DateTime::from($time));
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('done');
        $progressBar->finish();
        $output->writeln("");

        // non batch template conversions (direct sends)

        $templates = $this->templatesRepository->getTable()
            ->where(':mail_job_batch_templates.id IS NULL')
            ->fetchAll();

        $progressBar = new ProgressBar($output, count($templates));
        $progressBar->setFormat('processStats');
        $progressBar->start();

        /** @var ActiveRow $template */
        foreach ($templates as $template) {
            $progressBar->setMessage('Processing template <info>' . $template->id . '</info>', 'processing');

            $nonBatchTemplateConversions = $this->conversionsRepository->getNonBatchTemplateConversions($template->code);
            $userData = $this->getUserData(array_keys($nonBatchTemplateConversions));


            foreach ($nonBatchTemplateConversions as $userId => $time) {
                $log = $this->logsRepository->find(
                    $this->logsRepository->getTable()
                        ->select('MAX(id)')
                        ->where([
                            'email' => $userData[$userId],
                            'mail_template_id' => $template->id,
                            'mail_job_batch_id' => null,
                        ])
                        ->where('created_at < ?', DateTime::from($time))
                );
                if (!$log) {
                    continue;
                }
                $this->logConversionsRepository->upsert($log, DateTime::from($time));
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('done');
        $progressBar->finish();

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
    }

    private function getUserData($userIds)
    {
        $userData = [];
        foreach (array_chunk($userIds, 1000, true) as $userIdsChunk) {
            $page = 1;
            while ($users = $this->userProvider->list($userIdsChunk, $page)) {
                foreach ($users as $user) {
                    $userData[$user['id']] = $user['email'];
                }
                $page++;
            }
        }
        return $userData;
    }
}
