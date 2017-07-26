<?php

namespace Remp\MailerModule\Components;

use Nette\Application\UI\Control;
use Nette\Database\Table\IRow;
use Remp\MailerModule\Repository\LogsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

class TemplateStats extends Control
{
    /** @var LogsRepository */
    private $logsRepository;

    /** @var TemplatesRepository */
    private $templatesRepository;

    private $templates = [];

    private $showTotal = false;

    private $showConversions = false;

    public function __construct(
        LogsRepository $mailLogsRepository,
        TemplatesRepository $templatesRepository
    ) {
        parent::__construct();

        $this->logsRepository = $mailLogsRepository;
        $this->templatesRepository = $templatesRepository;
    }

    public function setTemplate(IRow $mailTemplate)
    {
        $this->templates[] = $mailTemplate;
        return $this;
    }

    public function showTotal()
    {
        $this->showTotal = true;
    }

    public function showConversions()
    {
        $this->showConversions = true;
    }

    public function render($templatesIds = null, $startTime = null, $endTime = null)
    {
        if (is_array($templatesIds)) {
            foreach ($templatesIds as $id) {
                $this->templates[] = $this->templatesRepository->find($id);
            }
        }

        $this->template->delivered_stat = $this->logsRepository->getStatsRate($this->templates, 'delivered_at', $startTime, $endTime);
        $this->template->opened_stat = $this->logsRepository->getStatsRate($this->templates, 'opened_at', $startTime, $endTime);
        $this->template->clicked_stat = $this->logsRepository->getStatsRate($this->templates, 'clicked_at', $startTime, $endTime);
        $this->template->dropped_stat = $this->logsRepository->getStatsRate($this->templates, 'dropped_at', $startTime, $endTime);
        $this->template->spam_stat = $this->logsRepository->getStatsRate($this->templates, 'spam_complained_at', $startTime, $endTime);

        if ($this->showTotal) {
            $this->template->total_stat = $this->template->delivered_stat['total'];
        }

        if ($this->showConversions) {
            $this->template->conversions = $this->logsRepository->getConversion($this->templates, $startTime, $endTime);
        }


        $this->template->setFile(__DIR__ . '/template_stats.latte');
        $this->template->render();
    }
}
