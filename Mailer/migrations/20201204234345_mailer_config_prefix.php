<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MailerConfigPrefix extends AbstractMigration
{
    public function up(): void
    {
        $sql = <<<SQL
update configs
set name = replace(name, "remp_mailermodule_mailer_smtpmailer", "remp-smtp")
where name like 'remp_mailermodule_mailer_smtpmailer%';

update configs
set name = replace(name, "remp_mailermodule_mailer_mailgunmailer", "remp-mailgun")
where name like 'remp_mailermodule_mailer_mailgunmailer%';
SQL;
        $this->execute($sql);
    }

    public function down(): void
    {
        $sql = <<<SQL
update configs
set name = replace(name, "remp-smtp", "remp_mailermodule_mailer_smtpmailer")
where name like 'remp-smtp%';

update configs
set name = replace(name, "remp-mailgun", "remp_mailermodule_mailer_mailgunmailer")
where name like 'remp-mailgun%';
SQL;
        $this->execute($sql);
    }
}
