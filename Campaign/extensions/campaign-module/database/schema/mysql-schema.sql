/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banners` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_id` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transition` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `target_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offset_horizontal` int NOT NULL,
  `offset_vertical` int NOT NULL,
  `display_delay` int DEFAULT NULL,
  `closeable` tinyint(1) NOT NULL,
  `close_timeout` int DEFAULT NULL,
  `display_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_selector` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `close_text` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `js` text COLLATE utf8mb4_unicode_ci,
  `js_includes` json DEFAULT NULL,
  `css_includes` json DEFAULT NULL,
  `manual_events_tracking` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bar_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bar_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `main_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `button_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_scheme` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bar_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `bar_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_banner_purchase_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_banner_purchase_stats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `campaign_banner_id` int unsigned NOT NULL,
  `time_from` timestamp NULL DEFAULT NULL,
  `time_to` timestamp NULL DEFAULT NULL,
  `sum` decimal(10,2) NOT NULL,
  `currency` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_banner_purchase_stats_campaign_banner_id_foreign` (`campaign_banner_id`),
  KEY `campaign_banner_purchase_stats_time_from_index` (`time_from`),
  KEY `campaign_banner_purchase_stats_time_to_index` (`time_to`),
  CONSTRAINT `campaign_banner_purchase_stats_campaign_banner_id_foreign` FOREIGN KEY (`campaign_banner_id`) REFERENCES `campaign_banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_banner_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_banner_stats` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `campaign_banner_id` int unsigned NOT NULL,
  `time_from` timestamp NULL DEFAULT NULL,
  `time_to` timestamp NULL DEFAULT NULL,
  `click_count` int NOT NULL,
  `show_count` int NOT NULL,
  `payment_count` int NOT NULL,
  `purchase_count` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_banner_stats_campaign_banner_id_foreign` (`campaign_banner_id`),
  KEY `campaign_banner_stats_time_from_index` (`time_from`),
  KEY `campaign_banner_stats_time_to_index` (`time_to`),
  CONSTRAINT `campaign_banner_stats_campaign_banner_id_foreign` FOREIGN KEY (`campaign_banner_id`) REFERENCES `campaign_banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_banners` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int unsigned NOT NULL,
  `banner_id` int unsigned DEFAULT NULL,
  `control_group` int DEFAULT '0',
  `proportion` int NOT NULL,
  `weight` int NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_id` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_banners_campaign_id_foreign` (`campaign_id`),
  KEY `campaign_banners_banner_id_foreign` (`banner_id`),
  CONSTRAINT `campaign_banners_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`),
  CONSTRAINT `campaign_banners_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_id` bigint unsigned NOT NULL,
  `campaign_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_collections_collection_id_foreign` (`collection_id`),
  KEY `campaign_collections_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `campaign_collections_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`),
  CONSTRAINT `campaign_collections_collection_id_foreign` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_country`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_country` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int unsigned NOT NULL,
  `country_iso_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `blacklisted` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_country_unique` (`campaign_id`,`country_iso_code`),
  KEY `campaign_country_country_iso_code_foreign` (`country_iso_code`),
  CONSTRAINT `campaign_country_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`),
  CONSTRAINT `campaign_country_country_iso_code_foreign` FOREIGN KEY (`country_iso_code`) REFERENCES `countries` (`iso_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaign_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_segments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int unsigned NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `inclusive` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `campaign_segments_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `campaign_segments_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaigns` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_id` char(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `signed_in` tinyint(1) DEFAULT NULL,
  `once_per_session` tinyint(1) NOT NULL,
  `devices` json NOT NULL,
  `operating_systems` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `pageview_rules` json DEFAULT NULL,
  `pageview_attributes` json DEFAULT NULL,
  `using_adblock` tinyint(1) DEFAULT NULL,
  `url_filter` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url_patterns` json DEFAULT NULL,
  `source_filter` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_patterns` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaigns_created_at_index` (`created_at`),
  KEY `campaigns_updated_at_index` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collapsible_bar_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collapsible_bar_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `header_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `main_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `button_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `initial_state` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_scheme` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `collapse_text` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expand_text` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `force_initial_state` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `collapsible_bar_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `collapsible_bar_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `collections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `iso_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`iso_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `html_overlay_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `html_overlay_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_align` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `font_size` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `background_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `css` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `html_overlay_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `html_overlay_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `html_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `html_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `dimensions` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_align` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `font_size` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `background_color` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `css` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `html_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `html_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `medium_rectangle_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medium_rectangle_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `header_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `main_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `button_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_scheme` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `width` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `medium_rectangle_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `medium_rectangle_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `newsletter_rectangle_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `newsletter_rectangle_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `newsletter_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `btn_submit` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text` text COLLATE utf8mb4_unicode_ci,
  `success` text COLLATE utf8mb4_unicode_ci,
  `failure` text COLLATE utf8mb4_unicode_ci,
  `terms` text COLLATE utf8mb4_unicode_ci,
  `width` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_scheme` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `newsletter_rectangle_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `newsletter_rectangle_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `overlay_rectangle_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overlay_rectangle_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `header_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `main_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `button_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `width` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_link` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_scheme` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `overlay_rectangle_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `overlay_rectangle_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `overlay_two_buttons_signature_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `overlay_two_buttons_signature_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `text_before` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_after` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_btn_primary` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_btn_primary_minor` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_btn_secondary` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_btn_secondary_minor` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_url_secondary` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_image_url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_signature` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `overlay_two_buttons_signature_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `overlay_two_buttons_signature_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `schedules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int unsigned NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `status` enum('ready','executed','paused','stopped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ready',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedules_campaign_id_foreign` (`campaign_id`),
  CONSTRAINT `schedules_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `short_message_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `short_message_templates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int unsigned NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `color_scheme` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `short_message_templates_banner_id_foreign` (`banner_id`),
  CONSTRAINT `short_message_templates_banner_id_foreign` FOREIGN KEY (`banner_id`) REFERENCES `banners` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `snippets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `snippets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `variables_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2017_04_06_065921_banners_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2017_04_19_084922_transforming_banner_to_text',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2017_05_25_094215_banner_closability',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2017_05_26_090050_banner_campaigns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2017_06_28_132802_campaign_segments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2017_08_28_062959_banner_display_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2017_09_18_143615_banner_templates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2017_09_21_090625_medium_rectangle_color_schemes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2017_09_22_073840_bar_banner_template',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2017_09_27_075942_medium_rectangle_dimensions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2017_10_11_092115_campaign_user_state',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2017_10_12_084905_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2017_10_30_115547_campaign_scheduler',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2017_11_02_120230_campaing_once_per_session',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2017_11_09_092137_banner_closeable_required',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2017_11_20_084233_alternative_banner',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2017_12_07_133131_short_message_templates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2018_01_21_101505_campaign_banners_pivot_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2018_02_22_101337_campaign_geotargeting',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2018_03_02_132258_campaigns_pageview_rules',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2018_03_08_100602_campaign_devices',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2018_03_22_162154_campaign_drop_active_flag',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2018_03_26_143751_change_pageview_rules_column_type',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2018_04_04_100549_html_template_text_size',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2018_04_04_115717_html_templates_css',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2018_04_04_135617_precise_banner_position',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2018_04_04_214416_banner_close_text',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2018_04_25_115245_variant_control_group',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2018_04_25_115736_variant_proportion',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2018_04_25_134100_variants_weight',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2018_04_26_102135_variants_primary_key',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2018_04_26_104002_variant_soft_delete',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2018_05_15_081111_variants_uuid',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2018_05_23_124131_optional_banner_url',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2018_07_06_141409_drop_variant_name',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2018_10_22_091546_add_campaign_addblock_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2018_11_14_092905_campaign_url_filter_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2018_11_15_140341_rename_campaign_usingadblock_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2018_12_12_140812_overlay_rectangle_templates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2018_12_31_093613_collapsible_bar_template',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2019_02_19_113331_campaign_referer_filter',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2019_03_20_122709_create_campaign_banner_stats_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2019_03_26_092200_create_campaign_banner_purchase_stats_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2019_06_04_081518_rename_collapsible_bar_template_collapse_text_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2019_06_04_081634_add_expand_and_collapse_text_to_collapsible_bar_template',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2019_06_26_112541_add_custom_js_and_includes_columns_to_banners',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2019_07_11_085137_campaign_country_primary_key',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2019_07_15_100156_html_overlay_template',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2019_07_24_083210_remove_center_positions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2019_10_10_175000_add_inclusive_column_to_campaign',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2020_03_12_083931_make_texts_nullable_in_banners',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2020_06_08_115935_add_manual_events_tracking_to_banners',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2020_06_24_123600_overlay_two_buttons_signature',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2020_08_17_073631_migrate_old_campaign_pageview_rules_format_to_new',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2020_10_23_092701_newsletter_rectangle_template',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2021_02_22_084635_banner_template_texts',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2021_05_21_072312_add_public_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2021_05_27_000000_add_uuid_to_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2021_07_21_074210_add_variables_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2021_08_13_140150_add_pageview_attributes_column_to_campaigns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2023_02_15_131950_add_force_initial_to_collapsible_bar',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2023_03_20_121715_rename_variables_to_snippets',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2023_09_05_145623_add_languages_column_to_campaigns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2023_09_12_121136_create_collections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2023_09_12_121415_create_campaign_collections_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2023_11_21_084246_added_index_to_campaign_updated_at_and_created_at_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2024_04_17_091341_refactor_referer_related_columns_to_source_in_campaigns_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2024_12_03_095025_banner_use_color_scheme_and_drop_color_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_02_24_160123_add_operating_systems_column_to_campaigns',1);
