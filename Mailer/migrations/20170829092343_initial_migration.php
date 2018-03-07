<?php

use Phinx\Migration\AbstractMigration;

class InitialMigration extends AbstractMigration
{
    public function change()
    {
        $this->table('configs')
            ->addColumn('name', 'string')
            ->addColumn('display_name', 'string')
            ->addColumn('value', 'text', ['null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('type', 'string', ['default' => 'text'])
            ->addColumn('sorting', 'integer', ['default' => 10])
            ->addColumn('autoload', 'boolean', ['default' => true])
            ->addColumn('locked', 'boolean', ['default' => false])
            ->addTimestamps()
            ->create();

        $this->table('mail_type_categories')
            ->addColumn('title', 'string')
            ->addColumn('sorting', 'integer')
            ->addTimestamps()
            ->create();

        $this->table('mail_layouts')
            ->addColumn('name', 'string')
            ->addColumn('layout_text', 'text')
            ->addColumn('layout_html', 'text')
            ->addTimestamps()
            ->create();

        $this->table('mail_types')
            ->addColumn('code', 'string')
            ->addColumn('locked', 'boolean', ['default' => false])
            ->addColumn('is_public', 'boolean')
            ->addColumn('auto_subscribe', 'boolean', ['default' => false])
            ->addColumn('title', 'string')
            ->addColumn('sorting', 'integer', ['default' => 10])
            ->addColumn('description', 'text')
            ->addColumn('priority', 'integer', ['default' => 100])
            ->addColumn('mail_type_category_id', 'integer', ['null' => true])
            ->addColumn('image_url', 'string', ['null' => true])
            ->addColumn('preview_url', 'string', ['null' => true])
            ->addColumn('default_variant_id', 'integer', ['null' => true])
            ->addForeignKey('mail_type_category_id', 'mail_type_categories', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $this->table('mail_type_variants')
            ->addColumn('mail_type_id', 'integer')
            ->addColumn('title', 'string')
            ->addColumn('code', 'string')
            ->addColumn('sorting', 'integer')
            ->addForeignKey('mail_type_id', 'mail_types', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $this->table('mail_types')
            ->addForeignKey('default_variant_id', 'mail_type_variants', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->save();

        $this->table('mail_templates')
            ->addColumn('name', 'string')
            ->addColumn('code', 'string')
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('from', 'string')
            ->addColumn('subject', 'string', ['null' => true])
            ->addColumn('mail_body_text', 'text')
            ->addColumn('mail_body_html', 'text')
            ->addColumn('mail_layout_id', 'integer', ['null' => true])
            ->addColumn('copy_from', 'integer', ['null' => true])
            ->addColumn('autologin', 'integer')
            ->addColumn('mail_type_id', 'integer')
            ->addForeignKey('mail_layout_id', 'mail_layouts', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('mail_type_id', 'mail_types', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('copy_from', 'mail_templates', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $this->table('mail_jobs')
            ->addColumn('segment_code', 'string')
            ->addColumn('segment_provider', 'string')
            ->addColumn('mail_type_variant_id', 'integer', ['null' => true])
            ->addColumn('status', 'string')
            ->addColumn('emails_sent_count', 'integer', ['default' => 0])
            ->addForeignKey('mail_type_variant_id', 'mail_type_variants', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $this->table('mail_job_batch')
            ->addColumn('mail_job_id', 'integer')
            ->addColumn('method', 'string')
            ->addColumn('max_emails', 'integer', ['null' => true])
            ->addColumn('start_at', 'timestamp', ['null' => true])
            ->addColumn('sent_emails', 'integer', ['default' => 0])
            ->addColumn('status', 'string', ['default' => 'created'])
            ->addColumn('pid', 'integer', ['null' => true])
            ->addColumn('last_ping', 'timestamp', ['null' => true])
            ->addColumn('errors_count', 'integer', ['default' => 0])
            ->addColumn('first_email_sent_at', 'timestamp', ['null' => true])
            ->addColumn('last_email_sent_at', 'timestamp', ['null' => true])
            ->addForeignKey('mail_job_id', 'mail_jobs', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $this->table('mail_job_batch_templates')
            ->addColumn('mail_job_id', 'integer')
            ->addColumn('mail_job_batch_id', 'integer')
            ->addColumn('mail_template_id', 'integer')
            ->addColumn('weight', 'integer')
            ->addForeignKey('mail_job_id', 'mail_jobs', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('mail_template_id', 'mail_templates', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('mail_job_batch_id', 'mail_job_batch', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $this->table('mail_job_queue')
            ->addColumn('email', 'string')
            ->addColumn('status', 'string', ['default' => 'queued'])
            ->addColumn('sorting', 'integer', ['default' => 1000])
            ->addColumn('mail_batch_id', 'integer')
            ->addColumn('mail_template_id', 'integer')
            ->addForeignKey('mail_batch_id', 'mail_job_batch', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('mail_template_id', 'mail_templates', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addTimestamps()
            ->create();

        $this->table('mail_user_subscriptions')
            ->addColumn('user_id', 'integer')
            ->addColumn('user_email', 'integer')
            ->addColumn('mail_type_id', 'integer')
            ->addColumn('subscribed', 'boolean')
            ->addColumn('mail_type_variant_id', 'integer', ['null' => true])
            ->addForeignKey('mail_type_id', 'mail_types', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addForeignKey('mail_type_variant_id', 'mail_type_variants', 'id', ['delete' => 'RESTRICT', 'update' => 'CASCADE'])
            ->addTimestamps()
            ->create();
    }
}
