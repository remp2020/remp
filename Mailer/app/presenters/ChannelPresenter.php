<?php

namespace Remp\MailerModule\Presenters;

use Nette\Application\BadRequestException;
use Remp\MailerModule\Forms\ChannelFormFactory;
use Remp\MailerModule\Forms\LayoutFormFactory;
use Remp\MailerModule\Repository\ChannelsRepository;
use Remp\MailerModule\Repository\LayoutsRepository;

final class ChannelPresenter extends BasePresenter
{
    /** @var ChannelsRepository */
    private $channelsRepository;

    /** @var ChannelFormFactory */
    private $channelFormFactory;

    public function __construct(
        ChannelsRepository $channelsRepository,
        ChannelFormFactory $channelFormFactory
    )
    {
        parent::__construct();
        $this->channelsRepository = $channelsRepository;
        $this->channelFormFactory = $channelFormFactory;
    }

    public function renderDefault()
    {
        $this->template->channels = $this->channelsRepository->all();
    }

    public function renderEdit($id)
    {
        $channel = $this->channelsRepository->find($id);
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

        $form = $this->channelFormFactory->create($id);

        $presenter = $this;
        $this->channelFormFactory->onCreate = function ($channel) use ($presenter) {
            $presenter->flashMessage('Channel was created');
            $presenter->redirect('Edit', $channel->id);
        };
        $this->channelFormFactory->onUpdate = function ($channel) use ($presenter) {
            $presenter->flashMessage('Channel was updated');
            $presenter->redirect('Edit', $channel->id);
        };

        return $form;
    }
}
