<?php

namespace Remp\MailerModule\Forms;

use Nette\Application\UI\Form;
use Nette\Object;
use Remp\MailerModule\Repository\ChannelsRepository;

class ChannelFormFactory extends Object
{
    /** @var ChannelsRepository */
    private $channelsRepository;

    public $onCreate;

    public $onUpdate;

    public function __construct(ChannelsRepository $channelsRepository)
    {
        $this->channelsRepository = $channelsRepository;
    }

    public function create($id)
    {
        $defaults = [];
        if (isset($id)) {
            $channel = $this->channelsRepository->find($id);
            $defaults = $channel->toArray();
        }

        $form = new Form;
        $form->addProtection();

        $form->addHidden('id', $id);

        $form->addText('name', 'Name')
            ->setRequired('Required');

        $form->addCheckbox('consent_required', 'Required user consent');

        $form->setDefaults($defaults);

        $form->addSubmit('save', 'Save')
            ->getControlPrototype()
            ->setName('button')
            ->setHtml('<i class="zmdi zmdi-mail-send"></i> Save');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded($form, $values)
    {
        if (!empty($values['id'])) {
            $row = $this->channelsRepository->find($values['id']);
            $this->channelsRepository->update($row, $values);
            ($this->onUpdate)($row);
        } else {
            $row = $this->channelsRepository->add($values['name'], $values['consent_required']);
            ($this->onCreate)($row);
        }
    }
}
