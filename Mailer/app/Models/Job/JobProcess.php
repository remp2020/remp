<?php
declare(strict_types=1);

namespace Remp\MailerModule\Models\Job;

class JobProcess
{
    public function kill($pid)
    {
        if (!is_numeric($pid)) {
            return false;
        }
        return posix_kill($pid, 9); //SIGKILL
    }

    public function pid()
    {
        return getmypid();
    }

    public function isRunning($pid)
    {
        return file_exists("/proc/{$pid}");
    }
}
