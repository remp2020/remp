<?php

namespace Remp\MailerModule\Components;

interface IBatchExperimentEvaluationFactory
{
    /** @return BatchExperimentEvaluation */
    public function create();
}
