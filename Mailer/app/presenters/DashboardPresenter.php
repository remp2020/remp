<?php

namespace Remp\MailerModule\Presenters;

final class DashboardPresenter extends BasePresenter
{
    public function renderDefault()
    {
        return $this->redirect('Template:Default');
    }
}
