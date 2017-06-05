<?php

namespace Remp\MailerModule\Presenters;

use Remp\MailerModule\Forms\ConfigFormFactory;

final class ConfigPresenter extends BasePresenter
{
    public function renderDefault()
    {

    }

    public function createComponentConfigForm(ConfigFormFactory $configFormFactory)
    {
        $form = $configFormFactory->create();

        $configFormFactory->onSuccess = function () {
            $this->flashMessage('Config was updated.');
            $this->redirect('Config:default');
        };
        return $form;
    }
}
