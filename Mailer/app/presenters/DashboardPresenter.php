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
        $dataSets = [];
        $numOfDays = 30;

        // fill graph columns
        for ($i = $numOfDays; $i > 0; $i--) {
            $labels[] = date("d. m. Y", strtotime('-' . $i . ' days'));
        }

        $allMailTypes = $this->listsRepository->all();

        foreach ($allMailTypes as $mailType) {
            $dataSets[$mailType->id] = [
                'id' => $mailType->id,
                'label' => $mailType->title,
                'data' => array_fill(0, $numOfDays, 0),
                'fill' => false,
                'borderColor' => 'rgb(75, 192, 192)',
                'lineTension' => 0.5
            ];
        }

        $data = $this->batchTemplatesRepository->getDashboardGraphsData($numOfDays);

        foreach ($data as $row) {
            $dataSets[$row->mail_type_id]['data'][
                array_search(
                    $row->first_email_sent_at->format('d. m. Y'),
                    $labels
                )
            ] = $row->sent_mails;
        }

        $this->template->emailsSentLastWeek = $this->batchTemplatesRepository->countMailsSent(7)->fetch()->count;
        $this->template->emailsSentToday = $this->batchTemplatesRepository->countMailsSent(1)->fetch()->count;
        $this->template->templatesSentLastWeek = $this->templatesRepository->countSent(7)->fetch()->count;
        $this->template->labels = $labels;
        $this->template->dataSets = array_values($dataSets);
    }

    public function renderDetail($id)
    {
        $labels = [];
        $dataSet = [];
        $numOfDays = 30;

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
