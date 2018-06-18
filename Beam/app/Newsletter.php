<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Recurr\Rule;

class Newsletter extends Model
{
    const STATE_STARTED = 'started';
    const STATE_PAUSED = 'paused';
    const STATE_FINISHED = 'finished';

    protected $dates = [
        'starts_at',
        'created_at',
        'updated_at',
        'last_sent_at'
    ];
    protected $fillable = [
        'name',
        'mailer_generator_id',
        'segment',
        'mail_type_code',
        'criteria',
        'articles_count',
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
}
