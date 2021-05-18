<?php

use Phinx\Migration\AbstractMigration;

class HermesRetry extends AbstractMigration
{
    public function up()
    {
        // probably the only feasible way how to change this without breaking running instances
        if (!$this->hasTable('hermes_tasks_old')) {
            $this->table("hermes_tasks")
                ->rename("hermes_tasks_old")
                ->update();

            $sql = <<<SQL
CREATE TABLE `hermes_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `retry` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `processed_at` datetime NOT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `execute_at` datetime DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `execute_at` (`processed_at`),
  KEY `created_at` (`created_at`),
  KEY `state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

            $this->execute($sql);
        }
    }

    public function down()
    {
        $this->output->writeln('Down migration is not available.');
    }
}
