<?php

namespace Remp\MailerModule\Repository;

use Remp\MailerModule\Repository;

class BatchesRepository extends Repository
{
    const STATE_CREATED = 'created';            // prva faza ked sa vytvori v admine
    const STATE_UPDATED = 'updated';            // ked sa v admine batch upravi a je nutne ho znovu nastartovat
    const STATE_READY = 'ready';                // ked sa v admine povie ze sa to moze odosielat, tento stav sa autoamticky zachyti a zacne sa generovat queue
    const STATE_PREPARING = 'preparing';        // ked je v tomto stave tak sa generuje queueu, po skonceni sa prepne na processing
    const STATE_PROCESSING = 'processing';      // ked je v totom stave tak sa zacne posielat jobom
    const STATE_PROCESSED = 'processed';        // job queues naplnene a caka na posielanie
    const STATE_SENDING = 'sending';            // posiela sa
    const STATE_DONE = 'done';                  // doposielane
    const STATE_USER_STOP = 'user_stopped';     // zastavene userom
    const STATE_WORKER_STOP = 'worker_stopped'; // v pripade chyby s komunikacie s SMTP je posielanie zastavene

    protected $tableName = 'mail_job_batch';

    public function add($jobId, $email_count, $startAt = null, $method = 'random')
    {
        $result = $this->insert([
            'mail_job_id' => $jobId,
            'method' => $method,
            'max_emails' => $email_count,
            'start_at' => new \DateTime($startAt),
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        ]);

        if (is_numeric($result)) {
            return $this->getTable()->where('id', $result)->fetch();
        }

        return $result;
    }
}
