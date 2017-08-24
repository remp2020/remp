<?php

namespace Remp\MailerModule\Filters;

use Nette\Utils\Html;
use Remp\MailerModule\Repository\BatchesRepository;

class JobLabelFilter
{
    public function process($status)
    {
        $classes = [
            BatchesRepository::STATE_CREATED         => 'info',
            BatchesRepository::STATE_READY           => 'primary',
            BatchesRepository::STATE_PREPARING       => 'warning',
            BatchesRepository::STATE_PROCESSING      => 'primary',
            BatchesRepository::STATE_PROCESSED       => 'primary',
            BatchesRepository::STATE_SENDING         => 'primary',
            BatchesRepository::STATE_DONE            => 'success',
            BatchesRepository::STATE_WORKER_STOP     => 'danger',
        ];

        if (isset($classes[$status])) {
            $class = $classes[$status];
        } else {
            $class = 'primary';
        }

        return Html::el('span', ['class' => 'label label-' . $class])->setText($status);
    }
}
