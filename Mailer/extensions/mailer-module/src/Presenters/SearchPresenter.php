<?php
declare(strict_types=1);

namespace Remp\MailerModule\Presenters;

use Remp\MailerModule\Models\Config\SearchConfig;
use Remp\MailerModule\Repositories\JobsRepository;
use Remp\MailerModule\Repositories\LayoutsRepository;
use Remp\MailerModule\Repositories\ListsRepository;
use Remp\MailerModule\Repositories\TemplatesRepository;

final class SearchPresenter extends BasePresenter
{
    public function __construct(
        private TemplatesRepository $templatesRepository,
        private LayoutsRepository $layoutsRepository,
        private ListsRepository $listsRepository,
        private JobsRepository $jobsRepository,
        private SearchConfig $searchConfig,
    ) {
        parent::__construct();
    }

    public function actionDefault($term): void
    {
        $limit = $this->searchConfig->getMaxResultCount();
        $layouts = array_values($this->layoutsRepository->search($term, $limit));
        $lists = array_values($this->listsRepository->search($term, $limit));

        $emails = [];
        $templateIds = [];

        foreach ($this->templatesRepository->search($term, $limit) as $mailTemplate) {
            $templateIds[$mailTemplate->id] = $mailTemplate->id;
            $emails[] = [
                'type' => 'email',
                'search_result_url' => $this->link('Template:show', ['id' => $mailTemplate->id]),
                'name' => $mailTemplate->subject,
                'tags' => [$mailTemplate->code],
                'mail_types' => [$mailTemplate->mail_type->title],
            ];
        }
        $jobs = [];
        $query = $this->jobsRepository->getTable()->where([
            ':mail_job_batch_templates.mail_template_id' => array_values($templateIds),
        ]);
        foreach ($query as $job) {
            $templates = [];
            $mailTypes = [];
            foreach ($job->related('mail_job_batch_templates') as $mailJobBatchTemplate) {
                $templates[] = $mailJobBatchTemplate->mail_template->subject;
                $mailTypes[] = $mailJobBatchTemplate->mail_template->mail_type->title;
            }
            $jobs[] = [
                'type' => 'job',
                'search_result_url' => $this->link('Job:show', ['id' => $job->id]),
                'name' => $templates,
                'date' => $job->created_at->format(DATE_RFC3339),
                'mail_types' => $mailTypes,
            ];
        }

        $layouts = $this->addTypeAndLink($layouts, 'layout', 'Layout:edit');
        $lists = $this->addTypeAndLink($lists, 'list', 'List:show');

        $result = array_merge($emails, $layouts, $lists, $jobs);

        $this->sendJson($result);
    }

    protected function addTypeAndLink(array $items, string $type, string $destination): array
    {
        foreach ($items as &$item) {
            $item['type'] = $type;
            $item['search_result_url'] = $this->link($destination, ['id' => $item['id']]);
        }
        return $items;
    }
}
