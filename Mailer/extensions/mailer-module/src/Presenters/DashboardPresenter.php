<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Nette\Utils\DateTime;
use DateInterval;
use IntlDateFormatter;
use Remp\MailerModule\Models\Formatters\DateFormatterFactory;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\BatchesRepository;
use Remp\MailerModule\Repositories\MailTemplateStatsRepository;
use Remp\MailerModule\Repositories\MailTypeStatsRepository;

final class DashboardPresenter extends BasePresenter
{
    private $batchesRepository;

    private $listsRepository;

    private $dateFormatter;

    /**
     * @var MailTypeStatsRepository
     */
    private $mailTypeStatsRepository;

    /** @var MailTemplateStatsRepository */
    private $mailTemplateStatsRepository;

    public function __construct(
        MailTemplateStatsRepository $mailTemplateStatsRepository,
        MailTypeStatsRepository $mailTypeStatsRepository,
        DateFormatterFactory $dateFormatterFactory,
        BatchesRepository $batchesRepository,
        ListsRepository $listsRepository
    ) {
        parent::__construct();

        $this->dateFormatter = $dateFormatterFactory
            ->getInstance(IntlDateFormatter::SHORT, IntlDateFormatter::NONE);

        $this->mailTemplateStatsRepository = $mailTemplateStatsRepository;
        $this->mailTypeStatsRepository = $mailTypeStatsRepository;
        $this->batchesRepository = $batchesRepository;
        $this->listsRepository = $listsRepository;
    }

    public function renderDefault(): void
    {
        $numOfDays = 30;
        $graphLabels = [];
        $typeDataSets = [];

        $now = new DateTime();
        $from = (clone $now)->sub(
            new DateInterval('P' . $numOfDays . 'D')
        );

        // init graph data set settings
        $defaultGraphSettings = [
            'data' => array_fill(0, $numOfDays, 0),
            'backgroundColor' => '#FDECB7',
            'strokeColor' => '#FDECB7',
            'borderColor' => '#FDECB7',
            'lineColor' => '#FDECB7',
            'prevPeriodCount' => 0,
            'fill' => true,
            'count' => 0
        ];

        // fill graph column labels
        for ($i = $numOfDays; $i >= 0; $i--) {
            $graphLabels[] = $this->dateFormatter->format(strtotime('-' . $i . ' days'));
        }

        $allMailTypes = $this->listsRepository->all();

        // fill datasets meta info
        foreach ($allMailTypes as $mailType) {
            $typeDataSets[$mailType->id] = [
                'id' => $mailType->id,
                'label' => $mailType->title
            ] + $defaultGraphSettings;
        }

        $typeSubscriberDataSets = $typeDataSets;

        $allSentMailsData = $this->mailTemplateStatsRepository->getAllMailTemplatesGraphData($from, $now);

        $allSentEmailsDataSet = [] + $defaultGraphSettings;

        // parse all sent mails data to chart.js format
        foreach ($allSentMailsData as $row) {
            $foundAt = array_search(
                $this->dateFormatter->format($row->date),
                $graphLabels
            );

            if ($foundAt !== false) {
                $allSentEmailsDataSet['data'][$foundAt] = $row->sent_mails;
                $allSentEmailsDataSet['count'] += $row->sent_mails;
            }
        }

        $typesData = $this->mailTemplateStatsRepository->getTemplatesGraphDataGroupedByMailType($from, $now);

        // parse sent mails by type data to chart.js format
        foreach ($typesData as $row) {
            $foundAt = array_search(
                $this->dateFormatter->format($row->date->getTimestamp()),
                $graphLabels
            );

            if ($foundAt !== false) {
                $typeDataSets[$row->mail_type_id]['count'] += $row->sent_mails;
                $typeDataSets[$row->mail_type_id]['data'][$foundAt] = $row->sent_mails;
            }
        }

        // parse previous period data (counts)
        $prevPeriodFrom = (clone $from)->sub(new DateInterval('P' . $numOfDays . 'D'));

        $prevPeriodTypesData = $this->mailTemplateStatsRepository->getTemplatesGraphDataGroupedByMailType($prevPeriodFrom, $from);

        foreach ($prevPeriodTypesData as $row) {
            $typeDataSets[$row->mail_type_id]['prevPeriodCount'] += $row->sent_mails;
        }

        // remove sets with zero sent count
        $typeDataSets = array_filter($typeDataSets, function ($a) {
            return ($a['count'] == 0) ? null : $a;
        });

        // order sets by sent count
        usort($typeDataSets, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        $typeSubscribersData = $this->mailTypeStatsRepository->getDashboardDataGroupedByTypes($from, $now);
        foreach ($typeSubscribersData as $row) {
            $foundAt = array_search(
                $this->dateFormatter->format($row->created_date),
                $graphLabels
            );

            if ($foundAt !== false) {
                $typeSubscriberDataSets[$row->mail_type_id]['data'][$foundAt] = $row->count;
                $typeSubscriberDataSets[$row->mail_type_id]['count'] = $row->count;
            }
        }

        // change subscriber data sets to start from zero because of graph readability
        foreach ($typeSubscriberDataSets as $mailTypeId => $typeSubscriberDataSet) {
            $minValue = min($typeSubscriberDataSet['data']);

            foreach ($typeSubscriberDataSet['data'] as $key => $value) {
                $typeSubscriberDataSets[$mailTypeId]['data'][$key] -= $minValue;
            }
        }

        $prevPeriodSubscribersTypeData = $this->mailTypeStatsRepository->getDashboardDataGroupedByTypes($prevPeriodFrom, $from);
        foreach ($prevPeriodSubscribersTypeData as $row) {
            $typeSubscriberDataSets[$row->mail_type_id]['prevPeriodCount'] = $row->count;
        }

        // remove sets with zero sent count
        $typeSubscriberDataSets = array_filter($typeSubscriberDataSets, function ($a) {
            return ($a['count'] == 0) ? null : $a;
        });

        // order sets by sent count
        usort($typeSubscriberDataSets, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        $inProgressBatches = $this->batchesRepository->getInProgressBatches(10);
        $lastDoneBatches = $this->batchesRepository->getLastDoneBatches(10);

        $this->template->typeSubscriberDataSets = array_values($typeSubscriberDataSets);
        $this->template->allSentEmailsDataSet = $allSentEmailsDataSet;
        $this->template->typeDataSets = array_values($typeDataSets);
        $this->template->inProgressBatches = $inProgressBatches;
        $this->template->lastDoneBatches = $lastDoneBatches;
        $this->template->labels = $graphLabels;
    }
}
