<?php

namespace Remp\MailerModule\Presenters;

use Carbon\Carbon;
use DateTime;
use DateInterval;
use IntlDateFormatter;
use Remp\MailerModule\Formatters\DateFormatterFactory;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\BatchesRepository;
use Remp\MailerModule\Repository\MailTypeStatsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;

final class DashboardPresenter extends BasePresenter
{
    private $batchTemplatesRepository;

    private $templatesRepository;
    
    private $batchesRepository;

    private $listsRepository;

    private $dateFormatter;

    /**
     * @var MailTypeStatsRepository
     */
    private $mailTypeStatsRepository;

    public function __construct(
        BatchTemplatesRepository $batchTemplatesRepository,
        MailTypeStatsRepository $mailTypeStatsRepository,
        DateFormatterFactory $dateFormatterFactory,
        TemplatesRepository $templatesRepository,
        BatchesRepository $batchesRepository,
        ListsRepository $listsRepository
    ) {
        parent::__construct();

        $this->dateFormatter = $dateFormatterFactory
            ->getInstance(IntlDateFormatter::SHORT, IntlDateFormatter::NONE);

        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->mailTypeStatsRepository = $mailTypeStatsRepository;
        $this->templatesRepository = $templatesRepository;
        $this->batchesRepository = $batchesRepository;
        $this->listsRepository = $listsRepository;
    }

    public function renderDefault()
    {
        $numOfDays = 30;
        $graphLabels = [];
        $typeDataSets = [];

        $now = new DateTime();
        $from = (clone $now)->sub(
            new DateInterval('P' . $numOfDays . 'D')
        );

        // init graph data set settings
        $defaualtGraphSettings = [
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
            ] + $defaualtGraphSettings;
        }

        $typeSubscriberDataSets = $typeDataSets;

        $allSentMailsData = $this->batchTemplatesRepository->getDashboardAllMailsGraphData($from, $now);

        $allSentEmailsDataSet = [] + $defaualtGraphSettings;

        // parse all sent mails data to chart.js format
        foreach ($allSentMailsData as $row) {
            $foundAt = array_search(
                $this->dateFormatter->format($row->first_email_sent_at),
                $graphLabels
            );

            if ($foundAt !== false) {
                $allSentEmailsDataSet['data'][$foundAt] = $row->sent_mails;
                $allSentEmailsDataSet['count'] += $row->sent_mails;
            }
        }

        $typesData = $this->batchTemplatesRepository->getDashboardGraphDataForTypes($from, $now);

        // parse sent mails by type data to chart.js format
        foreach ($typesData as $row) {
            $foundAt = array_search(
                $this->dateFormatter->format($row->first_email_sent_at->getTimestamp()),
                $graphLabels
            );

            if ($foundAt !== false) {
                $typeDataSets[$row->mail_type_id]['count'] += $row->sent_mails;
                $typeDataSets[$row->mail_type_id]['data'][$foundAt] = $row->sent_mails;
            }
        }

        // parse previous period data (counts)
        $prevPeriodFrom = (clone $from)->sub(new DateInterval('P' . $numOfDays . 'D'));

        $prevPeriodTypesData = $this->batchTemplatesRepository->getDashboardGraphDataForTypes($prevPeriodFrom, $from);

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

        $prevPeriodSubscribersTypeData = $this->mailTypeStatsRepository->getDashboardDataGroupedByTypes($prevPeriodFrom, $from);
        foreach ($prevPeriodSubscribersTypeData as $row) {
            $typeSubscriberDataSets[$row->mail_type_id]['prevPeriodCount'] += $row->count;
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

    public function renderSentEmailsDetail($id)
    {
        $mailType = $this->listsRepository->find($id);

        $this->template->mailTypeId = $mailType->id;
        $this->template->mailTypeTitle = $mailType->title;

        if (!$this->isAjax()) {
            $from = 'now - 30 days';
            $to = 'now';
            $this->template->from = $from;
            $this->template->to = $to;

            $data = $this->emailsDetailData($id, $from, $to);

            $this->template->labels = $data['labels'];
            $this->template->sentDataSet = $data['sentDataSet'];
            $this->template->openedDataSet = $data['openedDataSet'];
            $this->template->clickedDataSet = $data['clickedDataSet'];
        }
    }

    public function emailsDetailData($id, $from, $to)
    {
        $labels = [];
        $from = Carbon::parse($from);
        $to = Carbon::parse($to);
        $numOfDays = $from->diffInDays($to);

        // fill graph columns
        for ($i = $numOfDays - 1; $i >= 0; $i--) {
            $labels[] = $this->dateFormatter->format(strtotime('-' . $i . ' days'));
        }

        $sentDataSet = [
            'label' => 'sent',
            'data' => array_fill(0, $numOfDays, 0),
            'fill' => false,
            'borderColor' => 'rgb(0,150,136)',
            'lineTension' => 0.5
        ];

        $openedDataSet = [
            'label' => 'opened',
            'data' => array_fill(0, $numOfDays, 0),
            'fill' => false,
            'borderColor' => 'rgb(33,150,243)',
            'lineTension' => 0.5
        ];

        $clickedDataSet = [
            'label' => 'clicked',
            'data' => array_fill(0, $numOfDays, 0),
            'fill' => false,
            'borderColor' => 'rgb(230,57,82)',
            'lineTension' => 0.5
        ];

        $data = $this->templatesRepository->getDashboardDetailGraphData($id, $from, $to)->fetchAll();

        // parse sent mails by type data to chart.js format
        foreach ($data as $row) {
            $foundAt = array_search(
                $this->dateFormatter->format($row->first_email_sent_at),
                $labels
            );

            if ($foundAt !== false) {
                $sentDataSet['data'][$foundAt] = $row->sent_mails;
                $openedDataSet['data'][$foundAt] = $row->opened_mails;
                $clickedDataSet['data'][$foundAt] = $row->clicked_mails;
            }
        }

        return [
            'labels' => $labels,
            'sentDataSet' => $sentDataSet,
            'openedDataSet' => $openedDataSet,
            'clickedDataSet' => $clickedDataSet,
        ];
    }

    public function handleFilterChanged($id, $from, $to)
    {
        $data = $this->emailsDetailData($id, $from, $to);

        $this->template->labels = $data['labels'];
        $this->template->sentDataSet = $data['sentDataSet'];
        $this->template->openedDataSet = $data['openedDataSet'];
        $this->template->clickedDataSet = $data['clickedDataSet'];

        $this->redrawControl('graph');
    }
}
