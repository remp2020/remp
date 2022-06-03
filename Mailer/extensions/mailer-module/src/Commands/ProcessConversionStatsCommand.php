<?php
declare(strict_types=1);

namespace Remp\MailerModule\Commands;

use Nette\Utils\DateTime;
use Remp\MailerModule\Models\Users\IUser;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\IConversionsRepository;
use Remp\MailerModule\Repositories\LogConversionsRepository;
use Remp\MailerModule\Repositories\LogsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessConversionStatsCommand extends Command
{
    private $conversionsRepository;

    private $templatesRepository;

    private $userProvider;

    private $logsRepository;

    private $logConversionsRepository;

    private BatchesRepository $batchesRepository;

    public function __construct(
        IConversionsRepository $conversionsRepository,
        TemplatesRepository $templatesRepository,
        IUser $userProvider,
        LogsRepository $logsRepository,
        LogConversionsRepository $logConversionsRepository,
        BatchesRepository $batchesRepository
    ) {
        parent::__construct();
        $this->conversionsRepository = $conversionsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->userProvider = $userProvider;
        $this->logsRepository = $logsRepository;
        $this->logConversionsRepository = $logConversionsRepository;
        $this->batchesRepository = $batchesRepository;
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
            );
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
        if (in_array('job_batch', $input->getOption('mode'), true)) {
            $this->processBatchTemplateConversions($input, $output);
        }

        // non batch template conversions (direct sends)
        if (in_array('direct', $input->getOption('mode'), true)) {
            $this->processNonBatchTemplateConversions($input, $output);
        }

        $output->writeln('Done!');
        $output->writeln('');
        return 0;
    }

    private function processBatchTemplateConversions(InputInterface $input, OutputInterface $output)
    {
        $batchTemplatesConversions = $this->conversionsRepository
            ->getBatchTemplatesConversionsSince(DateTime::from($input->getOption('since')));

        $progressBar = new ProgressBar($output, count($batchTemplatesConversions));
        $progressBar->setFormat('processStats');
        $progressBar->start();

        $validMailJobBatchIds = $this->getValidMailJobBatchIds(array_keys($batchTemplatesConversions));
        foreach ($batchTemplatesConversions as $mailJobBatchId => $mailTemplateCodes) {
            if (!in_array($mailJobBatchId, $validMailJobBatchIds, true)) {
                continue;
            }
            foreach ($mailTemplateCodes as $mailTemplateCode => $userIds) {
                $userData = $this->getUserData(array_keys($userIds));
                $mailTemplate = $this->templatesRepository->getByCode($mailTemplateCode);
                if (!$mailTemplate) {
                    continue;
                }
                foreach ($userIds as $userId => $time) {
                    if (!isset($userData[$userId])) {
                        // this might be incorrectly tracker userId; throwing warning won't probably help at this point
                        // as it's not in Beam and the tracking might be already fixed
                        continue;
                    }
                    $latestLog = $this->logsRepository->getTable()
                        ->where([
                            'email' => $userData[$userId],
                            'mail_template_id' => $mailTemplate->id,
                            'mail_job_batch_id' => $mailJobBatchId
                        ])
                        ->where('created_at < ?', DateTime::from($time))
                        ->order('id DESC')
                        ->fetch();

                    if (!$latestLog) {
                        continue;
                    }
                    $this->logConversionsRepository->upsert($latestLog, DateTime::from($time));
                }
            }
            $progressBar->advance();
        }

        $progressBar->setMessage('done');
        $progressBar->finish();
        $output->writeln("");
    }

    private function getValidMailJobBatchIds($mailJobBatchIds): array
    {
        $result = [];
        foreach ($mailJobBatchIds as $mailJobBatchId) {
            if (!is_numeric($mailJobBatchId)) {
                continue;
            }

            $mailJobBatch = $this->batchesRepository->find($mailJobBatchId);
            if ($mailJobBatch) {
                $result[] = $mailJobBatchId;
            }
        }

        return $result;
    }

    private function processNonBatchTemplateConversions(InputInterface $input, OutputInterface $output)
    {
        $nonBatchTemplatesConversions = $this->conversionsRepository
            ->getNonBatchTemplatesConversionsSince(DateTime::from($input->getOption('since')));

        $progressBar = new ProgressBar($output, count($nonBatchTemplatesConversions));
        $progressBar->setFormat('processStats');
        $progressBar->start();

        foreach ($nonBatchTemplatesConversions as $mailTemplateCode => $userIds) {
            $userData = $this->getUserData(array_keys($userIds));
            $mailTemplate = $this->templatesRepository->getByCode($mailTemplateCode);
            if (!$mailTemplate) {
                continue;
            }
            foreach ($userIds as $userId => $time) {
                if (!isset($userData[$userId])) {
                    // this might be incorrectly tracker userId; throwing warning won't probably help at this point
                    // as it's not in Beam and the tracking might be already fixed
                    continue;
                }
                $latestLog = $this->logsRepository->getTable()
                    ->where([
                        'email' => $userData[$userId],
                        'mail_template_id' => $mailTemplate->id,
                    ])
                    ->where('created_at < ?', DateTime::from($time))
                    ->order('id DESC')
                    ->fetch();

                if (!$latestLog) {
                    continue;
                }
                $this->logConversionsRepository->upsert($latestLog, DateTime::from($time));
            }

            $progressBar->advance();
        }

        $progressBar->setMessage('done');
        $progressBar->finish();
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
