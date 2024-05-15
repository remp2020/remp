<?php

namespace Remp\MailerModule\Models;

trait DataRetentionTrait
{
    private string $retentionThreshold = '2 months';

    private string $retentionRemovingField = 'created_at';

    private bool $retentionForever = false;

    public function getRetentionRemovingField(): string
    {
        return $this->retentionRemovingField;
    }

    public function setRetentionThreshold(string $threshold): void
    {
        $this->retentionThreshold = $threshold;
    }

    public function setRetentionRemovingField(string $removingField): void
    {
        $this->retentionRemovingField = $removingField;
    }

    public function setRetentionForever(): void
    {
        $this->retentionForever = true;
    }

    public function getRetentionThreshold(): string
    {
        return $this->retentionThreshold;
    }

    public function removeData(): ?int
    {
        if ($this->retentionForever) {
            return null;
        }

        $threshold = (new \DateTime())->modify('-' . $this->retentionThreshold);
        return $this->getTable()
            ->where("{$this->getRetentionRemovingField()} < ?", $threshold)
            ->delete();
    }
}
