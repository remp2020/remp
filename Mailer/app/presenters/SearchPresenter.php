<?php

namespace Remp\MailerModule\Presenters;

use Remp\MailerModule\Repository\JobsRepository;
use Remp\MailerModule\Repository\LayoutsRepository;
use Remp\MailerModule\Repository\ListsRepository;
use Remp\MailerModule\Repository\TemplatesRepository;

final class SearchPresenter extends BasePresenter
{
    /** @var TemplatesRepository */
    private $templatesRepository;

    /** @var LayoutsRepository */
    private $layoutsRepository;

    /** @var ListsRepository */
    private $listsRepository;

    /** @var JobsRepository */
    private $jobsRepository;

    public function __construct(
        TemplatesRepository $templatesRepository,
        LayoutsRepository $layoutsRepository,
        ListsRepository $listsRepository,
        JobsRepository $jobsRepository
    ) {
        parent::__construct();

        $this->templatesRepository = $templatesRepository;
        $this->layoutsRepository = $layoutsRepository;
        $this->listsRepository = $listsRepository;
        $this->jobsRepository = $jobsRepository;
    }

    public function renderDefault($term)
    {
        $limit =  $this->environmentConfig->getParam('max_result_count');
        $emails = array_values($this->templatesRepository->search($term, $limit));
        $layouts = array_values($this->layoutsRepository->search($term, $limit));
        $lists = array_values($this->listsRepository->search($term, $limit));
        $jobs = array_values($this->jobsRepository->search($term, $limit));

        $emails = $this->addTypeAndLink($emails, 'email', 'Template:show');
        $layouts = $this->addTypeAndLink($layouts, 'layout', 'Layout:edit');
        $lists = $this->addTypeAndLink($lists, 'list', 'List:show');
        $jobs = $this->addTypeAndLink($jobs, 'job', 'Job:show');

        $result = array_merge($emails, $layouts, $lists, $jobs);

        $this->presenter->sendJson($result);
    }

    protected function addTypeAndLink(array $items, string $type, string $destination): array
    {
        foreach ($items as &$item) {
            $item = array_merge([
                'type' => $type,
                'search_result_url' => $this->link($destination, ['id' => $item['id']]),
            ], $item);
        }
        return $items;
    }

}
