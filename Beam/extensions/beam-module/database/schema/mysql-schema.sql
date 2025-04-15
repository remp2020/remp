/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounts_uuid_index` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_aggregated_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_aggregated_views` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `user_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date NOT NULL,
  `pageviews` int NOT NULL DEFAULT '0',
  `timespent` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_index` (`article_id`,`user_id`,`browser_id`,`date`),
  KEY `article_aggregated_views_user_id_index` (`user_id`),
  KEY `article_aggregated_views_browser_id_index` (`browser_id`),
  KEY `article_aggregated_views_date_index` (`date`),
  CONSTRAINT `fk_article_id` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_author` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `author_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_author_article_id_foreign` (`article_id`),
  KEY `article_author_author_id_foreign` (`author_id`),
  CONSTRAINT `article_author_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  CONSTRAINT `article_author_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_meta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `key` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `article_meta_article_id_foreign` (`article_id`),
  CONSTRAINT `article_meta_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_pageviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_pageviews` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `time_from` timestamp NULL DEFAULT NULL,
  `time_to` timestamp NULL DEFAULT NULL,
  `sum` int NOT NULL,
  `signed_in` int NOT NULL DEFAULT '0',
  `subscribers` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `article_pageviews_time_from_index` (`time_from`),
  KEY `article_pageviews_time_to_index` (`time_to`),
  KEY `article_pageviews_article_id_time_from_sum_index` (`article_id`,`time_from`,`sum`),
  KEY `article_pageviews_time_from_article_id_sum_index` (`time_from`,`article_id`,`sum`),
  CONSTRAINT `article_pageviews_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_section` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `section_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_section_article_id_foreign` (`article_id`),
  KEY `article_section_section_id_foreign` (`section_id`),
  CONSTRAINT `article_section_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  CONSTRAINT `article_section_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_tag` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `tag_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_tag_article_id_foreign` (`article_id`),
  KEY `article_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `article_tag_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  CONSTRAINT `article_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_timespents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_timespents` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `time_from` timestamp NULL DEFAULT NULL,
  `time_to` timestamp NULL DEFAULT NULL,
  `sum` int NOT NULL,
  `signed_in` int NOT NULL DEFAULT '0',
  `subscribers` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `article_timespents_article_id_foreign` (`article_id`),
  KEY `article_timespents_time_from_index` (`time_from`),
  KEY `article_timespents_time_to_index` (`time_to`),
  CONSTRAINT `article_timespents_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_titles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_titles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `variant` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(768) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `article_titles_article_id_foreign` (`article_id`),
  CONSTRAINT `article_titles_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `article_views_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `article_views_snapshots` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `time` timestamp NOT NULL,
  `property_token` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_article_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `referer_medium` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `count` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `article_views_snapshots_time_index` (`time`),
  KEY `article_views_snapshots_external_article_id_index` (`external_article_id`),
  KEY `article_views_snapshots_property_token_index` (`property_token`),
  KEY `article_views_snapshots_time_referer_medium_index` (`time`,`referer_medium`),
  KEY `article_views_snapshots_time_property_token_index` (`time`,`property_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `articles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `property_uuid` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(768) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(768) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_url` varchar(768) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `pageviews_all` bigint NOT NULL DEFAULT '0',
  `pageviews_signed_in` bigint NOT NULL DEFAULT '0',
  `pageviews_subscribers` bigint NOT NULL DEFAULT '0',
  `timespent_all` bigint NOT NULL DEFAULT '0',
  `timespent_signed_in` bigint NOT NULL DEFAULT '0',
  `timespent_subscribers` bigint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `articles_external_id_unique` (`external_id`),
  KEY `articles_property_uuid_foreign` (`property_uuid`),
  KEY `articles_published_at_index` (`published_at`),
  KEY `articles_content_type_index` (`content_type`),
  CONSTRAINT `articles_property_uuid_foreign` FOREIGN KEY (`property_uuid`) REFERENCES `properties` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `authors_external_id_index` (`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `config_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `config_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_categories_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `config_category_id` int unsigned NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `property_id` int unsigned DEFAULT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `sorting` int NOT NULL DEFAULT '10',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `configs_name_property_id_unique` (`name`,`property_id`),
  KEY `configs_property_id_foreign` (`property_id`),
  KEY `configs_config_category_id_foreign` (`config_category_id`),
  CONSTRAINT `configs_config_category_id_foreign` FOREIGN KEY (`config_category_id`) REFERENCES `config_categories` (`id`),
  CONSTRAINT `configs_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversion_commerce_event_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversion_commerce_event_products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversion_commerce_event_id` int unsigned NOT NULL,
  `product_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `event_foreign` (`conversion_commerce_event_id`),
  CONSTRAINT `event_foreign` FOREIGN KEY (`conversion_commerce_event_id`) REFERENCES `conversion_commerce_events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversion_commerce_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversion_commerce_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversion_id` int unsigned NOT NULL,
  `time` timestamp NOT NULL,
  `minutes_to_conversion` int NOT NULL,
  `event_prior_conversion` int unsigned NOT NULL,
  `step` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `funnel_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` double(8,2) DEFAULT NULL,
  `currency` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_campaign` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_content` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_medium` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversion_commerce_events_conversion_id_foreign` (`conversion_id`),
  CONSTRAINT `conversion_commerce_events_conversion_id_foreign` FOREIGN KEY (`conversion_id`) REFERENCES `conversions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversion_general_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversion_general_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversion_id` int unsigned NOT NULL,
  `time` timestamp NOT NULL,
  `minutes_to_conversion` int NOT NULL,
  `event_prior_conversion` int unsigned NOT NULL,
  `action` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_campaign` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_content` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_medium` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversion_general_events_conversion_id_foreign` (`conversion_id`),
  CONSTRAINT `conversion_general_events_conversion_id_foreign` FOREIGN KEY (`conversion_id`) REFERENCES `conversions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversion_pageview_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversion_pageview_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversion_id` int unsigned NOT NULL,
  `time` timestamp NOT NULL,
  `minutes_to_conversion` int NOT NULL,
  `event_prior_conversion` int unsigned NOT NULL,
  `article_id` int unsigned NOT NULL,
  `locked` tinyint(1) DEFAULT NULL,
  `signed_in` tinyint(1) DEFAULT NULL,
  `timespent` int unsigned DEFAULT NULL,
  `rtm_campaign` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_content` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_medium` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rtm_source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversion_pageview_events_conversion_id_foreign` (`conversion_id`),
  KEY `conversion_pageview_events_article_id_foreign` (`article_id`),
  CONSTRAINT `conversion_pageview_events_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  CONSTRAINT `conversion_pageview_events_conversion_id_foreign` FOREIGN KEY (`conversion_id`) REFERENCES `conversions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversion_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversion_sources` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `conversion_id` int unsigned NOT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `referer_medium` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `referer_source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer_host_with_path` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `article_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversion_sources_conversion_id_foreign` (`conversion_id`),
  KEY `conversion_sources_article_id_foreign` (`article_id`),
  CONSTRAINT `conversion_sources_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`),
  CONSTRAINT `conversion_sources_conversion_id_foreign` FOREIGN KEY (`conversion_id`) REFERENCES `conversions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `user_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` double(8,2) NOT NULL,
  `currency` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid_at` timestamp NOT NULL,
  `transaction_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `events_aggregated` tinyint(1) NOT NULL DEFAULT '0',
  `source_processed` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversions_transaction_id_unique` (`transaction_id`),
  KEY `conversions_article_id_foreign` (`article_id`),
  KEY `conversions_user_id_index` (`user_id`),
  KEY `conversions_paid_at_index` (`paid_at`),
  CONSTRAINT `conversions_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_articles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_articles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `article_id` int unsigned NOT NULL,
  `unique_browsers` int DEFAULT NULL,
  `last_dashboard_time` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dashboard_articles_article_id_foreign` (`article_id`),
  KEY `dashboard_articles_last_dashboard_time_index` (`last_dashboard_time`),
  CONSTRAINT `dashboard_articles_article_id_foreign` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int unsigned DEFAULT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entity_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entity_params` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `entity_id` int unsigned NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entity_params_entity_id_foreign` (`entity_id`),
  CONSTRAINT `entity_params_entity_id_foreign` FOREIGN KEY (`entity_id`) REFERENCES `entities` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `newsletters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletters` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mailer_generator_id` int unsigned NOT NULL,
  `segment` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mail_type_code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criteria` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `articles_count` int unsigned NOT NULL,
  `recurrence_rule` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `personalized_content` tinyint(1) NOT NULL,
  `timespan` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `state` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_subject` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_from` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_sent_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `properties` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `properties_account_id_foreign` (`account_id`),
  KEY `properties_uuid_index` (`uuid`),
  CONSTRAINT `properties_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `referer_medium_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `referer_medium_labels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `referer_medium` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `referer_medium_labels_referer_medium_unique` (`referer_medium`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sections_external_id_index` (`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `segment_browsers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segment_browsers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `segment_id` int unsigned NOT NULL,
  `browser_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `segment_browsers_segment_id_foreign` (`segment_id`),
  CONSTRAINT `segment_browsers_segment_id_foreign` FOREIGN KEY (`segment_id`) REFERENCES `segments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `segment_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segment_groups` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sorting` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `segment_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segment_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `segment_id` int unsigned NOT NULL,
  `event_category` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_action` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timespan` int DEFAULT NULL,
  `count` int NOT NULL,
  `fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON encoded fields',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `operator` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'JSON encoded flags',
  PRIMARY KEY (`id`),
  KEY `segment_rules_segment_id_foreign` (`segment_id`),
  CONSTRAINT `segment_rules_segment_id_foreign` FOREIGN KEY (`segment_id`) REFERENCES `segments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `segment_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segment_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `segment_id` int unsigned NOT NULL,
  `user_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `segment_users_segment_id_foreign` (`segment_id`),
  CONSTRAINT `segment_users_segment_id_foreign` FOREIGN KEY (`segment_id`) REFERENCES `segments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `segment_group_id` int unsigned NOT NULL,
  `criteria` json DEFAULT NULL COMMENT 'JSON encoded segment criteria',
  PRIMARY KEY (`id`),
  UNIQUE KEY `segments_code_unique` (`code`),
  KEY `segments_segment_group_id_foreign` (`segment_group_id`),
  KEY `segments_name_index` (`name`),
  CONSTRAINT `segments_segment_group_id_foreign` FOREIGN KEY (`segment_group_id`) REFERENCES `segment_groups` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tag_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tag_tag_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tag_tag_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `tag_id` int unsigned NOT NULL,
  `tag_category_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `tag_tag_category_tag_id_foreign` (`tag_id`),
  KEY `tag_tag_category_tag_category_id_foreign` (`tag_category_id`),
  CONSTRAINT `tag_tag_category_tag_category_id_foreign` FOREIGN KEY (`tag_category_id`) REFERENCES `tag_categories` (`id`),
  CONSTRAINT `tag_tag_category_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tags_external_id_index` (`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `views_per_browser_mv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `views_per_browser_mv` (
  `browser_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_views_last_30_days` int unsigned NOT NULL DEFAULT '0',
  `total_views_last_60_days` int unsigned NOT NULL DEFAULT '0',
  `total_views_last_90_days` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`browser_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `views_per_user_mv`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `views_per_user_mv` (
  `user_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_views_last_30_days` int unsigned NOT NULL DEFAULT '0',
  `total_views_last_60_days` int unsigned NOT NULL DEFAULT '0',
  `total_views_last_90_days` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2017_03_29_134957_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2017_04_04_060513_properties_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2017_06_27_072000_create_segments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2017_08_23_071800_event_name_to_action',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2017_09_07_124520_uuid_indices',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2017_09_20_135555_segment_rule_operator',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2017_09_29_080257_segment_rule_flags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2017_12_01_094140_unique_codes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2018_02_12_112807_articles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2018_02_14_074236_article_conversions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2018_02_15_065425_article_pageviews',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2018_02_16_115820_unique_article_ids',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2018_02_20_083104_article_timespent',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2018_02_21_070055_article_sum_cache_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2018_03_22_105849_pageview_devices_and_referers',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2018_03_26_221138_article_pageviews_signed_in_subscriber_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2018_03_29_122457_article_timespent_signed_subscribed',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2018_05_18_132351_add_missing_session_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2018_06_08_073818_create_newsletters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2018_07_11_133723_create_article_aggregated_views_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2018_07_12_075004_create_segment_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2018_07_20_102942_create_segment_groups_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2018_07_24_111455_create_segment_browsers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2018_08_01_112202_create_views_per_browser_mv_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2018_08_02_065721_create_views_per_user_mv_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2018_08_15_162705_create_segment_entities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2018_08_22_075217_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2018_08_22_075309_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2018_09_03_133431_add_user_id_to_conversions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2018_10_01_071044_create_entity_params_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2018_10_01_071934_drop_schema_column_from_entities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2018_10_25_124435_add_personalized_content_to_newsletters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2018_11_14_125025_increase_title_length_in_articles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2018_11_29_091254_create_configs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2018_12_07_145030_article_prolong_image_url',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2018_12_10_114711_create_conversion_commerce_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2018_12_10_114720_create_conversion_pageview_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2018_12_10_122114_create_conversion_general_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2018_12_10_123942_create_conversion_commerce_event_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2018_12_19_093646_add_events_aggregated_to_conversions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2019_01_03_142538_create_article_titles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2019_01_24_152049_segments_add_criteria',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2019_01_29_142418_seed_config_values',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2019_01_31_142723_create_article_aggregated_views_with_id_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2019_02_06_144306_lock_author_segments_configuration',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2019_02_20_113557_alter_newsletters_change_timespan_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2019_06_13_111140_create_article_views_snapshots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2019_07_02_082320_removing_paid_at_defaults',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2019_08_15_124622_seed_config_values2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2019_09_05_085126_make_configs_property_specific',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2019_09_05_093404_create_config_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2019_09_05_223346_seed_config_values3',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2019_10_03_181106_make_title_nullable_in_article_titles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2019_11_07_153448_add_indexes_to_article_views_snapshots',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2019_12_03_123733_create_referer_medium_labels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2020_01_31_140157_article_tags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2020_02_18_201353_remove_unused_columns_in_article_views_snapshots',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2020_03_18_122311_add_external_id_column_to_sections_tags_authors_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2020_04_06_120804_add_indexes_to_segments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2020_07_28_091554_create_conversion_sources_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2020_08_07_114042_remove_locked_column_in_configs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2020_08_07_122341_seed_author_segments_config_category',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2020_08_11_082125_alter_configs_remove_nullable_config_category',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2020_08_27_111750_add_content_type_to_articles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2020_09_18_114850_alter_segment_rules_pageview_load_flags',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2020_10_09_131803_alter_conversions_remove_on_update_from_paid_at_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2020_11_09_081647_create_sections_segment_group',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2020_11_13_093901_seed_section_segment_config_category_and_values',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2020_12_02_090920_change_authors_segment_days_threshold_description',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2020_12_04_145757_convert_utm_to_rtm',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2020_12_16_102140_article_content_type_index',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2021_05_06_135333_create_tag_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2021_05_06_142318_create_tag_tag_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2021_05_11_113758_article_pageviews_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2021_05_31_000000_add_uuid_to_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2021_06_10_141729_purge_invalid_aggregated_data',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2021_11_08_084315_add_index_to_conversions_paid_at_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2022_02_25_130852_dashboard_articles',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2023_04_04_070759_article_views_snapshots_add_index_time_property_token',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2023_09_07_080805_create_article_meta_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2024_04_15_131551_remove_configs_autoload_flag',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2024_07_29_045343_drop_pageview_devices_and_referers',2);
