<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Remp\MailerModule\ActiveRow;
use Nette\Utils\DateTime;
use Remp\MailerModule\Repository\BatchTemplatesRepository;
use Remp\MailerModule\Repository\IConversionsRepository;
use Remp\MailerModule\Repository\LogConversionsRepository;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\User\IUser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption(
                'since',
                null,
                InputOption::VALUE_OPTIONAL,
                'date string specifying which mailJobBatches (since when until now) should be processed',
                '-1 month'
            )
            ->addOption(
                'mode',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'processing mode (job_batch - processing newsletters, direct - processing system emails)',
                ['job_batch']
            )
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

        if (in_array('job_batch', $input->getOption('mode'))) {
            $batchTemplates = $this->batchTemplatesRepository->getTable()
                ->where('created_at > ?', DateTime::from($input->getOption('since')))
                ->fetchAll();

            $progressBar = new ProgressBar($output, count($batchTemplates));
            $progressBar->setFormat('processStats');
            $progressBar->start();

            $jobBatchIds = [];
            $mailTemplateCodes = [];

            foreach ($batchTemplates as $batchTemplate) {
                $jobBatchIds[$batchTemplate->mail_job_batch_id] = true;
                $mailTemplateCodes[$batchTemplate->mail_template->code] = true;
            }
            $batchTemplatesConversions = $this->conversionsRepository->getBatchTemplatesConversions(
                array_keys($jobBatchIds),
                array_keys($mailTemplateCodes)
            );

            /** @var ActiveRow $batchTemplate */
            foreach ($batchTemplates as $batchTemplate) {
                $progressBar->setMessage('Processing jobBatchTemplate <info>' . $batchTemplate->id . '</info>', 'processing');

                if (!isset($batchTemplatesConversions[$batchTemplate->mail_job_batch_id][$batchTemplate->mail_template->code])) {
                    $progressBar->advance();
                    continue;
                }

                $batchTemplateConversions = $batchTemplatesConversions[$batchTemplate->mail_job_batch->id][$batchTemplate->mail_template->code] ?? [];
                $userData = $this->getUserData(array_keys($batchTemplateConversions));

                foreach ($batchTemplateConversions as $userId => $time) {
                    if (!isset($userData[$userId])) {
                        // this might be incorrectly tracker userId; throwing warning won't probably help at this point
                        // as it's not in Beam and the tracking might be already fixed
                        continue;
                    }
                    $latestLog = $this->logsRepository->getTable()
                        ->select('MAX(id) AS id')
                        ->where([
                            'email' => $userData[$userId],
                            'mail_template_id' => $batchTemplate->mail_template_id,
                            'mail_job_batch_id' => $batchTemplate->mail_job_batch_id,
                        ])
                        ->where('created_at < ?', DateTime::from($time))
                        ->fetch();

                    $log = $this->logsRepository->find($latestLog->id);
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
        }

        // non batch template conversions (direct sends)

        if (in_array('direct', $input->getOption('mode'))) {
            $templates = $this->templatesRepository->getTable()
                ->where(':mail_job_batch_templates.id IS NULL')
                ->fetchAll();

            $progressBar = new ProgressBar($output, count($templates));
            $progressBar->setFormat('processStats');
            $progressBar->start();

            $mailTemplateCodes = [];

            foreach ($templates as $template) {
                $mailTemplateCodes[$template->code] = true;
            }
            $nonBatchTemplatesConversions = $this->conversionsRepository->getNonBatchTemplateConversions(
                array_keys($mailTemplateCodes)
            );

            /** @var ActiveRow $template */
            foreach ($templates as $template) {
                $progressBar->setMessage('Processing template <info>' . $template->id . '</info>', 'processing');

                if (!isset($nonBatchTemplatesConversions[$template->code])) {
                    $progressBar->advance();
                    continue;
                }

                $nonBatchTemplateConversions = $nonBatchTemplatesConversions[$template->code] ?? [];
                $userData = $this->getUserData(array_keys($nonBatchTemplateConversions));

                foreach ($nonBatchTemplateConversions as $userId => $time) {
                    if (!isset($userData[$userId])) {
                        // this might be incorrectly tracker userId; throwing warning won't probably help at this point
                        // as it's not in Beam and the tracking might be already fixed
                        continue;
                    }
                    $latestLog = $this->logsRepository->getTable()
                        ->select('MAX(id) AS id')
                        ->where([
                            'email' => $userData[$userId],
                            'mail_template_id' => $template->id,
                            'mail_job_batch_id' => null,
                        ])
                        ->where('created_at < ?', DateTime::from($time))
                        ->fetch();

                    $log = $this->logsRepository->find($latestLog->id);
                    if (!$log) {
                        continue;
                    }
                    $this->logConversionsRepository->upsert($log, DateTime::from($time));
                }

                $progressBar->advance();
            }

            $progressBar->setMessage('done');
            $progressBar->finish();
        }

        $output->writeln('');
        $output->writeln('Done');
        $output->writeln('');
        return 0;
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
