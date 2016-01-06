<?php

// Manually run with the following command (db:seed excludes this since it's temporary)
// cd /vagrant/dropbox;
// php artisan db:seed --class=DropboxExactCopySeeder

class DropboxExactCopySeeder extends Seeder {

	public function run()
	{

		// This is rough, but we're duct-taping.
		$sql_insert = <<< EOSQL
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS `dropboxes`;

CREATE TABLE `dropboxes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dropbox_authorized_id` int(11) NOT NULL,
  `dropbox_token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `delta` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trees_user_id_unique` (`dropbox_authorized_id`),
  UNIQUE KEY `trees_token_unique` (`dropbox_token`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `dropboxes` WRITE;
/*!40000 ALTER TABLE `dropboxes` DISABLE KEYS */;

INSERT INTO `dropboxes` (`id`, `dropbox_authorized_id`, `dropbox_token`, `created_at`, `updated_at`, `delta`)
VALUES
	(1,123456789,'NopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNope','2014-01-31 20:29:27','2014-01-31 20:29:27','NopeNopeNopeNopeNo-eNopeNopeNopeN-peNopeNopeNo-eNopeNopeNopeNopeNopeNopeN-peNope_opeNo-eNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNo_eNopeNopeNopeNopeNopeNopeNopeN_peNopeNopeNopeNopeNopeNopeNopeNopeNo-eNopeNopeNopeNopeNope_NopeN-peNopeNo_eNopeNopeNopeNopeN_p-NopeNopeNopeNopeNopeNopeNopeNopeNopeNopeNopeN');

/*!40000 ALTER TABLE `dropboxes` ENABLE KEYS */;
UNLOCK TABLES;

DROP TABLE IF EXISTS `entries`;

CREATE TABLE `entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dropbox_id` int(10) unsigned NOT NULL,
  `parent_id` int(10) unsigned NOT NULL,
  `path` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `rev` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `size` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `bytes` bigint(20) unsigned NOT NULL,
  `icon` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `mime_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `root` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `file_modified` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `client_modified` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `is_dir` int(11) NOT NULL,
  `folder_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `entries_tree_id_index` (`dropbox_id`),
  CONSTRAINT `entries_dropbox_id_foreign` FOREIGN KEY (`dropbox_id`) REFERENCES `dropboxes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13115 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `entries` WRITE;
/*!40000 ALTER TABLE `entries` DISABLE KEYS */;

INSERT INTO `entries` (`id`, `dropbox_id`, `parent_id`, `path`, `rev`, `size`, `bytes`, `icon`, `mime_type`, `root`, `file_modified`, `client_modified`, `is_dir`, `folder_hash`, `created_at`, `updated_at`)
VALUES
	(1,1,0,'/','','0 bytes',0,'folder','','dropbox','','',1,'UNKNOWN','2014-01-30 21:50:45','2014-01-30 21:50:45');

/*!40000 ALTER TABLE `entries` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table migrations
# ------------------------------------------------------------

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;

INSERT INTO `migrations` (`migration`, `batch`)
VALUES
	('2014_01_22_044327_create_entries_table',1),
	('2014_01_22_164038_create_trees_table',1),
	('2014_01_22_172939_add_tree_id_foreign_key_to_entries_table',1),
	('2014_01_27_220421_add_delta_to_trees_table',1),
	('2014_01_28_173317_rename_trees_table_to_dropboxes',1),
	('2014_01_28_175349_update_entries_table_schema',1),
	('2014_01_28_200430_update_entries_bytes_column_type',1),
	('2014_01_28_210425_rename_entries_table_tree_id_to_dropbox_id',1);

/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
EOSQL;

	DB::connection()->disableQueryLog();

	DB::connection()->getpdo()->exec($sql_insert);

	}

}