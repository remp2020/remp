<?php
declare(strict_types=1);

namespace Remp\MailerModule\Components\BatchExperimentEvaluation;

interface IBatchExperimentEvaluationFactory
{
    /** @return BatchExperimentEvaluation */
    public function create(): BatchExperimentEvaluation;
}
