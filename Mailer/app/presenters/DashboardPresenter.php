<?php

namespace Remp\MailerModule\Presenters;

use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;
use Remp\MailerModule\Repository\BatchTemplatesRepository;

final class DashboardPresenter extends BasePresenter
{
    private $batchTemplatesRepository;
    
    private $templatesRepository;
    
    private $listsRepository;

    public function __construct(
        BatchTemplatesRepository $batchTemplatesRepository,
        TemplatesRepository $templatesRepository,
        ListsRepository $listsRepository
    ) {
        parent::__construct();

        $this->batchTemplatesRepository = $batchTemplatesRepository;
        $this->templatesRepository = $templatesRepository;
        $this->listsRepository = $listsRepository;
    }

    public function renderDefault()
    {
        $ct = 0;
        $labels = [];
        $typeDataSets = [];
        $numOfDays = 30;

        // fill graph columns
        for ($i = $numOfDays; $i > 0; $i--) {
            $labels[] = date("d. m. Y", strtotime('-' . $i . ' days'));
        }

        $allMailTypes = $this->listsRepository->all();

        // fill datasets meta info
        foreach ($allMailTypes as $mailType) {
            $typeDataSets[$mailType->id] = [
                'id' => $mailType->id,
                'label' => $mailType->title,
                'data' => array_fill(0, $numOfDays, 0),
                'fill' => true,
                'backgroundColor' => '#FDECB7',
                'strokeColor' => '#FDECB7',
                'borderColor' => '#FDECB7',
                'lineColor' => '#FDECB7',
                'count' => 0
            ];
        }

        $allSentMailsData = $this->batchTemplatesRepository->getDashboardGraphData($numOfDays);

        $allSentEmailsDataSet = [
            'data' => array_fill(0, $numOfDays, 0),
            'fill' => true,
            'backgroundColor' => '#FDECB7',
            'strokeColor' => '#FDECB7',
            'borderColor' => '#FDECB7',
            'lineColor' => '#FDECB7',
            'count' => 0
        ];

        // parse all sent mails data to chart.js format
        foreach($allSentMailsData as $row) {
            $allSentEmailsDataSet['data'][array_search(
                $row->first_email_sent_at->format('d. m. Y'),
                $labels
            )] = $row->sent_mails;
        }


        $typesData = $this->batchTemplatesRepository->getDashboardGraphDataForTypes($numOfDays);

        // parse sent mails by type data to chart.js format
        foreach ($typesData as $row) {
            $typeDataSets[$row->mail_type_id]['count'] += $row->sent_mails;

            $typeDataSets[$row->mail_type_id]['data'][
                array_search(
                    $row->first_email_sent_at->format('d. m. Y'),
                    $labels
                )
            ] = $row->sent_mails;
        }

        // order sets by sent count
        usort($typeDataSets, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        // remove sets with zero sent count
        $typeDataSets = array_filter($typeDataSets, function ($a) {
            return ($a['count'] == 0) ? null : $a;
        });

        // $this->template->emailsSentLastWeek = $this->batchTemplatesRepository->countMailsSent(7)->fetch()->count;
        // $this->template->emailsSentToday = $this->batchTemplatesRepository->countMailsSent(1)->fetch()->count;
        // $this->template->templatesSentLastWeek = $this->templatesRepository->countSent(7)->fetch()->count;
        $this->template->typeDataSets = array_values($typeDataSets);
        $this->template->allSentEmailsDataSet = $allSentEmailsDataSet;
        $this->template->labels = $labels;
    }

    public function renderDetail($id)
    {
        $labels = [];
        $dataSet = [];
        $numOfDays = 14;

        // fill graph columns
        for ($i = $numOfDays; $i > 0; $i--) {
            $labels[] = date("d. m. Y", strtotime('-' . $i . ' days'));
        }

        $mailType = $this->listsRepository->find($id);

        $dataSet = [
            'label' => $mailType->title,
            'data' => array_fill(0, $numOfDays, 0),
            'fill' => false,
            'borderColor' => 'rgb(75, 192, 192)',
            'lineTension' => 0.5
        ];

        $data = $this->batchTemplatesRepository->getDashboardDetailGraphData($id, $numOfDays)->fetchAll();

        foreach ($data as $row) {
            $dataSet['data'][
                array_search(
                    $row->first_email_sent_at->format('d. m. Y'),
                    $labels
                )
            ] = $row->sent_mails;
        }

        $this->template->dataSet = $dataSet;
        $this->template->labels = $labels;
    }
}
