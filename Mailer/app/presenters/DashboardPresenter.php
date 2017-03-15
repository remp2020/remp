<?php

namespace Remp\MailerModule\Presenters;

use Remp\MailerModule\Repository\LayoutsRepository;

final class DashboardPresenter extends BasePresenter
{
    /** @var LayoutsRepository */
    private $layoutsRepository;

    public function __construct(LayoutsRepository $layoutsRepository)
    {
        parent::__construct();
        $this->layoutsRepository = $layoutsRepository;
    }

    public function renderDefault()
    {

    }
}
