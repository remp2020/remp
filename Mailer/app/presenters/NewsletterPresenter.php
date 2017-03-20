<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Remp\MailerModule\Forms\NewsletterFormFactory;
use Remp\MailerModule\Repository\NewslettersRepository;


final class NewsletterPresenter extends BasePresenter
{
    /** @var NewslettersRepository */
    private $newsletterRepository;

    /** @var NewsletterFormFactory */
    private $newsletterFormFactory;

    public function __construct(
        NewslettersRepository $newsletterRepository,
        NewsletterFormFactory $newsletterFormFactory
    )
    {
        parent::__construct();
        $this->newsletterRepository = $newsletterRepository;
        $this->newsletterFormFactory = $newsletterFormFactory;
    }

    public function renderDefaultJsonData()
    {
        $request = $this->request->getParameters();

        $channels = $this->newsletterRepository->tableFilter($request['search']['value'], $request['columns'][$request['order'][0]['column']]['name'], $request['order'][0]['dir']);
        $result = [
            'recordsTotal' => $this->newsletterRepository->totalCount(),
            'recordsFiltered' => count($channels),
            'data' => []
        ];

        $channels = array_slice($channels, $request['start'], $request['length']);

        // @todo get from SSO
        $totalUsers = 5;
        foreach ($channels as $channel) {
            $result['data'][] = [
                'RowId' => $channel->id,
                $channel->name,
                $channel->consent_required,
                $channel->created_at,
                $channel->consent_required == 1 ? $channel->subscribers : $totalUsers - $channel->subscribers
            ];
        }
        $this->presenter->sendJson($result);
    }

    public function renderEdit($id)
    {
        $channel = $this->newsletterRepository->find($id);
        if (!$channel) {
            throw new BadRequestException();
        }

        $this->template->channel = $channel;
    }

    public function createComponentChannelForm()
    {
        $id = null;
        if (isset($this->params['id'])) {
            $id = (int)$this->params['id'];
        }

        $form = $this->newsletterFormFactory->create($id);

        $presenter = $this;
        $this->newsletterFormFactory->onCreate = function ($channel) use ($presenter) {
            $presenter->flashMessage('Newsletter was created');
            $presenter->redirect('Edit', $channel->id);
        };
        $this->newsletterFormFactory->onUpdate = function ($channel) use ($presenter) {
            $presenter->flashMessage('Newsletter was updated');
            $presenter->redirect('Edit', $channel->id);
        };

        return $form;
    }
}
