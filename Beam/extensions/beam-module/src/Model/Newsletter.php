<?php

namespace Remp\BeamModule\Model;

use Remp\BeamModule\Model\BaseModel;
use Recurr\Rule;

class Newsletter extends BaseModel
{
    const STATE_STARTED = 'started';
    const STATE_PAUSED = 'paused';
    const STATE_FINISHED = 'finished';

    protected $casts = [
        'personalized_content' => 'boolean',
        'starts_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    protected $attributes = [
        'personalized_content' => false,
    ];

    protected $fillable = [
        'name',
        'mailer_generator_id',
        'segment',
        'mail_type_code',
        'criteria',
        'articles_count',
        'personalized_content',
        'recurrence_rule',
        'state',
        'timespan',
        'email_from',
        'email_subject',
        'last_sent_at',
        'starts_at',
    ];

    /**
     * Get the RRule object
     * access using $newsletter->rule_object
     *
     * @return Rule
     * @throws \Recurr\Exception\InvalidRRule
     */
    public function getRuleObjectAttribute(): ?Rule
    {
        return $this->recurrence_rule ? new Rule($this->recurrence_rule) : null;
    }

    public function getSegmentCodeAttribute()
    {
        return explode('::', $this->segment)[1];
    }

    public function getSegmentProviderAttribute()
    {
        return explode('::', $this->segment)[0];
    }

    public function getRecurrenceRuleInlineAttribute($value)
    {
        return str_replace("\r\n", " ", $value);
    }

    public function isFinished()
    {
        return $this->state === self::STATE_FINISHED;
    }

    public function isStarted()
    {
        return $this->state === self::STATE_STARTED;
    }

    public function isPaused()
    {
        return $this->state === self::STATE_PAUSED;
    }
}
