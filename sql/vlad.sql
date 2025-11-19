/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.3-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: vlad
-- ------------------------------------------------------
-- Server version	11.8.3-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_category_url` (`url`),
  KEY `idx_categories_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `categories` VALUES
(3,'anekdoty','Анекдоты','2025-04-22 16:55:47','2025-04-24 12:10:59'),
(4,'veselaya-rifma','Веселая рифма','2025-04-22 16:57:05','2025-04-24 12:11:27'),
(5,'citatnik','Цитатник','2025-05-16 18:57:47','2025-05-16 18:57:47'),
(6,'istorii','Истории','2025-05-16 18:58:24','2025-05-16 19:01:05'),
(7,'kartinki','Картинки','2025-05-16 19:01:38','2025-05-16 19:01:38'),
(8,'video','Видео','2025-05-16 19:01:38','2025-05-16 19:01:38'),
(9,'tegi','Тэги','2025-05-16 19:02:23','2025-05-16 19:02:23'),
(10,'luchshee','Лучшее','2025-05-16 19:02:23','2025-05-16 19:02:23');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `visitor_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `status` enum('deleted','pending','published') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  KEY `comments_ibfk_3` (`visitor_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
set autocommit=0;
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Уникальный идентификатор файла',
  `user_id` int(11) DEFAULT NULL COMMENT 'ID пользователя, который загрузил файл',
  `file_name` varchar(255) NOT NULL COMMENT 'Имя файла (например, image.jpg)',
  `file_path` varchar(255) NOT NULL COMMENT 'Путь к файлу на сервере (например, /uploads/2025/04/image.jpg)',
  `mime_type` varchar(100) NOT NULL COMMENT 'MIME-тип файла (например, image/jpeg)',
  `file_size` int(11) NOT NULL COMMENT 'Размер файла в байтах',
  `alt_text` varchar(255) DEFAULT NULL COMMENT 'Альтернативный текст для изображений (SEO)',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'Дата и время загрузки',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Дата и время обновления',
  `status` enum('published','deleted') NOT NULL DEFAULT 'published',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_file_path` (`file_path`),
  KEY `idx_media_user_id` (`user_id`),
  KEY `idx_post_image` (`id`),
  CONSTRAINT `media_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=110 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `media` VALUES
(4,2,'aVUOus_1Tok.jpg','/assets/uploads/2025/06/aVUOus_1Tok.jpg','image/jpeg',384086,NULL,'2025-06-15 17:03:11','2025-08-06 15:53:35','published'),
(9,2,'06_1751660220.png','/assets/uploads/2025/07/06_1751660220.png','image/png',143624,NULL,'2025-07-06 13:40:47','2025-08-06 14:59:49','published'),
(10,2,'06_vverh-vpravo-2.jpg','/assets/uploads/2025/07/06_vverh-vpravo-2.jpg','image/jpeg',48241,'Вправо вверх','2025-07-06 13:41:09','2025-08-06 14:59:02','published'),
(25,2,'___.jpg','/assets/uploads/2025/08/___.jpg','image/jpeg',751966,NULL,'2025-08-09 11:14:09','2025-08-09 11:14:09','published'),
(27,2,'15440811102024_4e4118d0b87de93a05c5e95c8a3ff86d8d906f5d.jpg','/assets/uploads/2025/08/15440811102024_4e4118d0b87de93a05c5e95c8a3ff86d8d906f5d.jpg','image/jpeg',172744,'','2025-08-09 13:43:35','2025-08-09 13:43:35','published'),
(51,2,'kosmos-za-dveryu.jpg','/assets/uploads/2025/08/kosmos-za-dveryu.jpg','image/jpeg',46269,'космос за дверью','2025-08-11 17:23:40','2025-08-11 17:23:40','published'),
(52,2,'kosmos-za-dveryu_1.jpg','/assets/uploads/2025/08/kosmos-za-dveryu_1.jpg','image/jpeg',46269,'3534','2025-08-14 12:50:50','2025-08-14 12:50:50','published'),
(53,2,'1755117456.jpg','/assets/uploads/2025/08/1755117456.jpg','image/jpeg',79489,'werewr','2025-08-15 13:47:51','2025-08-15 13:47:51','published'),
(54,2,'1755117089.jpg','/assets/uploads/2025/08/1755117089.jpg','image/jpeg',77230,'уцкуцк','2025-08-15 16:00:49','2025-08-15 16:00:49','published'),
(55,2,'guh3o0t8dw4.jpg','/assets/uploads/2025/08/guh3o0t8dw4.jpg','image/jpeg',106514,'цуцк','2025-08-17 13:03:09','2025-08-17 13:03:09','published'),
(56,2,'novyy-hersones.jpg','/assets/uploads/2025/08/novyy-hersones.jpg','image/jpeg',118225,'sdfsd','2025-08-17 13:15:10','2025-08-17 13:15:10','published'),
(57,2,'kosmos-za-dveryu_2.jpg','/assets/uploads/2025/08/kosmos-za-dveryu_2.jpg','image/jpeg',46269,'ewrew','2025-08-24 12:08:55','2025-08-24 12:08:55','published'),
(58,2,'1756472718.jpg','/assets/uploads/2025/08/1756472718.jpg','image/jpeg',58909,'98uhj','2025-08-31 11:58:51','2025-08-31 11:58:51','published'),
(59,2,'1756472718_1.jpg','/assets/uploads/2025/08/1756472718_1.jpg','image/jpeg',58909,'erte','2025-08-31 14:38:43','2025-08-31 14:38:43','published'),
(60,2,'1756651325.jpg','/assets/uploads/2025/08/1756651325.jpg','image/jpeg',39803,'куеуеук','2025-08-31 14:42:42','2025-08-31 14:42:42','published'),
(61,2,'1756651325_1.jpg','/assets/uploads/2025/08/1756651325_1.jpg','image/jpeg',39803,'ываыа','2025-08-31 15:16:00','2025-08-31 15:16:00','published'),
(65,2,'1756651325_2.jpg','/assets/uploads/2025/08/1756651325_2.jpg','image/jpeg',39803,'sdfgfd','2025-08-31 15:35:14','2025-08-31 15:35:14','published'),
(66,2,'1756651325_3.jpg','/assets/uploads/2025/08/1756651325_3.jpg','image/jpeg',39803,'nmn','2025-08-31 15:35:45','2025-08-31 15:35:45','published'),
(67,2,'kosmos-za-dveryu.jpg','/assets/uploads/2025/09/kosmos-za-dveryu.jpg','image/jpeg',46269,'йцуйцу','2025-09-02 16:40:54','2025-09-02 16:40:54','published'),
(68,2,'kosmos-za-dveryu_1.jpg','/assets/uploads/2025/09/kosmos-za-dveryu_1.jpg','image/jpeg',46269,'фывфы','2025-09-06 11:44:05','2025-09-06 11:44:05','published'),
(69,2,'1757774152.jpg','/assets/uploads/2025/09/1757774152.jpg','image/jpeg',41443,'rewtwert','2025-09-13 16:11:48','2025-09-13 16:11:48','published'),
(70,2,'351c024f8c3011f09b591a9b03f9ba7b_1.jpg','/assets/uploads/2025/09/351c024f8c3011f09b591a9b03f9ba7b_1.jpg','image/jpeg',120483,'dstert','2025-09-13 16:18:56','2025-09-13 16:18:56','published'),
(71,2,'e65da2688c2f11f0990cae715528e67c_1.jpg','/assets/uploads/2025/09/e65da2688c2f11f0990cae715528e67c_1.jpg','image/jpeg',94182,'кенкеунуке','2025-09-13 20:44:04','2025-09-13 20:44:04','published'),
(72,2,'77571f16930f11f080ce26ac9352cb05_1.jpg','/assets/uploads/2025/09/77571f16930f11f080ce26ac9352cb05_1.jpg','image/jpeg',59159,'кенекн','2025-09-16 18:08:55','2025-09-16 18:08:55','published'),
(73,2,'0adb84728c3011f0b566322dcb89aa32_1.jpg','/assets/uploads/2025/09/0adb84728c3011f0b566322dcb89aa32_1.jpg','image/jpeg',115158,'tert','2025-09-16 18:12:09','2025-09-16 18:12:09','published'),
(74,2,'0adb84728c3011f0b566322dcb89aa32_1_3.jpg','/assets/uploads/2025/09/0adb84728c3011f0b566322dcb89aa32_1_3.jpg','image/jpeg',115158,'','2025-09-23 13:51:37','2025-09-23 13:51:37','published'),
(75,2,'0adb84728c3011f0b566322dcb89aa32_1_4.jpg','/assets/uploads/2025/09/0adb84728c3011f0b566322dcb89aa32_1_4.jpg','image/jpeg',115158,'','2025-09-23 16:09:45','2025-09-23 16:09:45','published'),
(88,2,'0adb84728c3011f0b566322dcb89aa32_1_6.jpg','/assets/uploads/2025/09/0adb84728c3011f0b566322dcb89aa32_1_6.jpg','image/jpeg',115158,'','2025-09-23 17:23:24','2025-09-23 17:23:24','published'),
(89,2,'0adb84728c3011f0b566322dcb89aa32_1_7.jpg','/assets/uploads/2025/09/0adb84728c3011f0b566322dcb89aa32_1_7.jpg','image/jpeg',115158,'','2025-09-23 17:27:06','2025-09-23 17:27:06','published'),
(90,2,'03-povtoryayte-za-mnoy-polnoe-vypolnenie-kompleksa-1-variant-muzyki-1-100_04_40_29nepodvizhnoe-izobrazhenie001.jpg','/assets/uploads/2025/09/03-povtoryayte-za-mnoy-polnoe-vypolnenie-kompleksa-1-variant-muzyki-1-100_04_40_29nepodvizhnoe-izobrazhenie001.jpg','image/jpeg',47178,'','2025-09-23 17:29:00','2025-09-23 17:29:00','published'),
(94,2,'img_20191226_001049.jpg','/assets/uploads/2025/09/img_20191226_001049.jpg','image/jpeg',98099,'','2025-09-25 13:43:08','2025-09-25 13:43:08','published'),
(95,2,'1758650389.png','/assets/uploads/2025/10/1758650389.png','image/png',511168,'','2025-10-04 14:20:04','2025-10-04 14:20:04','published'),
(96,2,'d56cfcaa8c2f11f080b0cabf5906f820_1.jpg','/assets/uploads/2025/10/d56cfcaa8c2f11f080b0cabf5906f820_1.jpg','image/jpeg',101146,'','2025-10-04 14:21:01','2025-10-04 14:21:01','published'),
(97,2,'kosmos-za-dveryu.jpg','/assets/uploads/2025/10/kosmos-za-dveryu.jpg','image/jpeg',46269,'','2025-10-04 14:27:29','2025-10-04 14:27:29','published'),
(98,2,'1758650389_1.png','/assets/uploads/2025/10/1758650389_1.png','image/png',511168,'','2025-10-07 15:53:47','2025-10-07 15:53:47','published'),
(99,2,'1760353173.png','/assets/uploads/2025/10/1760353173.png','image/png',669447,'frwerwe','2025-10-13 11:17:49','2025-10-13 11:17:49','published'),
(100,2,'1760353173_1.png','/assets/uploads/2025/10/1760353173_1.png','image/png',669447,'ыва','2025-10-13 11:22:04','2025-10-13 11:22:04','published'),
(101,2,'77571f16930f11f080ce26ac9352cb05_1.jpg','/assets/uploads/2025/10/77571f16930f11f080ce26ac9352cb05_1.jpg','image/jpeg',59159,'','2025-10-14 12:32:21','2025-10-14 12:32:21','published'),
(102,2,'03-povtoryayte-za-mnoy-polnoe-vypolnenie-kompleksa-1-variant-muzyki-1-100_04_40_29nepodvizhnoe-izobrazhenie001.jpg','/assets/uploads/2025/10/03-povtoryayte-za-mnoy-polnoe-vypolnenie-kompleksa-1-variant-muzyki-1-100_04_40_29nepodvizhnoe-izobrazhenie001.jpg','image/jpeg',47178,'asd','2025-10-14 13:26:11','2025-10-14 13:26:11','published'),
(103,2,'kandinsky-download-1721245873358.png','/assets/uploads/2025/10/kandinsky-download-1721245873358.png','image/png',736424,'sedrewr','2025-10-14 13:46:42','2025-10-14 13:46:42','published'),
(104,2,'03-povtoryayte-za-mnoy-polnoe-vypolnenie-kompleksa-1-variant-muzyki-1-100_04_40_29nepodvizhnoe-izobrazhenie001_3.jpg','/assets/uploads/2025/10/03-povtoryayte-za-mnoy-polnoe-vypolnenie-kompleksa-1-variant-muzyki-1-100_04_40_29nepodvizhnoe-izobrazhenie001_3.jpg','image/jpeg',47178,'sdf','2025-10-14 13:55:39','2025-10-14 13:55:39','published'),
(105,2,'1761331737.png','/assets/uploads/2025/10/1761331737.png','image/png',608405,'','2025-10-25 14:35:16','2025-10-25 14:35:16','published'),
(106,2,'1761331737_1.png','/assets/uploads/2025/10/1761331737_1.png','image/png',608405,'','2025-10-25 14:36:59','2025-10-25 14:36:59','published'),
(107,2,'chatgpt-image-26-okt-2025-g-15_36_48.png','/assets/uploads/2025/10/chatgpt-image-26-okt-2025-g-15_36_48.png','image/png',151844,'','2025-10-27 16:53:47','2025-10-27 16:53:47','published'),
(108,2,'1761999978.png','/assets/uploads/2025/11/1761999978.png','image/png',526743,'78ybiu','2025-11-01 14:53:50','2025-11-01 14:53:50','published'),
(109,2,'1761999978_1.png','/assets/uploads/2025/11/1761999978_1.png','image/png',526743,'4y45y6546','2025-11-01 14:54:01','2025-11-01 14:54:01','published');
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `post_category`
--

DROP TABLE IF EXISTS `post_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_category` (
  `post_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`,`category_id`),
  KEY `fk_post_category_category_id` (`category_id`),
  CONSTRAINT `fk_post_category_category_id` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `post_category_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_category`
--

LOCK TABLES `post_category` WRITE;
/*!40000 ALTER TABLE `post_category` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `post_category` VALUES
(3,3),
(5,3),
(236,3),
(241,3),
(263,3),
(265,3),
(269,3),
(275,3),
(282,3),
(287,3),
(290,3),
(291,3),
(298,3),
(310,3),
(311,3),
(313,3),
(314,3),
(315,3),
(316,3),
(319,3),
(320,3),
(322,3),
(325,3),
(329,3),
(332,3),
(334,3),
(338,3),
(344,3),
(345,3),
(346,3),
(347,3),
(348,3),
(361,3),
(364,3),
(367,3),
(370,3),
(371,3),
(373,3),
(374,3),
(375,3),
(377,3),
(382,3),
(383,3),
(384,3),
(393,3),
(394,3),
(395,3),
(396,3),
(404,3),
(405,3),
(406,3),
(408,3),
(412,3),
(413,3),
(416,3),
(419,3),
(420,3),
(421,3),
(422,3),
(423,3),
(424,3),
(425,3),
(426,3),
(427,3),
(428,3),
(429,3),
(432,3),
(434,3),
(4,4),
(278,4),
(282,4),
(293,4),
(321,4),
(324,4),
(326,4),
(327,4),
(330,4),
(335,4),
(362,4),
(363,4),
(381,4),
(397,4),
(407,4),
(409,4),
(411,4),
(417,4),
(418,4),
(431,4),
(435,4),
(436,4),
(437,4),
(198,5),
(216,5),
(217,5),
(219,5),
(221,5),
(227,5),
(230,5),
(232,5),
(234,5),
(237,5),
(238,5),
(242,5),
(244,5),
(246,5),
(248,5),
(250,5),
(252,5),
(254,5),
(256,5),
(258,5),
(259,5),
(277,5),
(188,6),
(264,6),
(270,6),
(278,6),
(289,6),
(333,6),
(337,6),
(339,6),
(385,6),
(274,7),
(278,7),
(312,7),
(366,7),
(197,8),
(215,8),
(216,8),
(218,8),
(220,8),
(222,8),
(228,8),
(231,8),
(233,8),
(235,8),
(243,8),
(245,8),
(247,8),
(249,8),
(251,8),
(253,8),
(255,8),
(257,8),
(267,8),
(268,8),
(273,8),
(292,8),
(294,8),
(295,8),
(296,8),
(297,8),
(299,8),
(300,8),
(301,8),
(303,8),
(304,8),
(306,8),
(307,8),
(323,8),
(328,8),
(331,8),
(336,8),
(365,8),
(368,8),
(369,8),
(372,8),
(398,8),
(399,8),
(400,8),
(401,8),
(402,8),
(403,8),
(430,8),
(239,10),
(240,10),
(340,10);
/*!40000 ALTER TABLE `post_category` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `post_tag`
--

DROP TABLE IF EXISTS `post_tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_tag` (
  `post_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `fk_post_tag_tag_id` (`tag_id`),
  CONSTRAINT `fk_post_tag_tag_id` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_tag_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tag`
--

LOCK TABLES `post_tag` WRITE;
/*!40000 ALTER TABLE `post_tag` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `post_tag` VALUES
(3,7),
(218,7),
(287,7),
(315,7),
(316,7),
(319,7),
(320,7),
(321,7),
(322,7),
(323,7),
(324,7),
(325,7),
(326,7),
(328,7),
(331,7),
(332,7),
(334,7),
(361,7),
(364,7),
(365,7),
(377,7),
(424,7),
(3,8),
(197,8),
(198,8),
(215,8),
(216,8),
(217,8),
(218,8),
(219,8),
(220,8),
(221,8),
(222,8),
(227,8),
(306,8),
(307,8),
(315,8),
(319,8),
(322,8),
(323,8),
(325,8),
(329,8),
(331,8),
(332,8),
(334,8),
(382,8),
(186,9),
(238,9),
(264,9),
(355,9),
(186,10),
(219,11),
(263,11),
(315,11),
(321,11),
(364,11),
(220,12),
(239,12),
(262,12),
(264,12),
(315,12),
(316,12),
(320,12),
(321,12),
(324,12),
(325,12),
(326,12),
(328,12),
(333,12),
(335,12),
(361,12),
(384,12),
(281,14),
(283,16),
(291,16),
(293,16),
(295,16),
(296,16),
(298,16),
(316,16),
(322,16),
(335,16),
(361,16),
(283,17),
(320,17),
(380,17),
(381,17),
(283,18),
(315,18),
(283,19),
(312,19),
(323,19),
(327,19),
(333,19),
(283,20),
(284,21),
(285,22),
(290,28),
(292,34),
(293,37),
(294,41),
(294,42),
(295,46),
(296,48),
(296,49),
(297,50),
(298,51),
(299,52),
(300,53),
(301,54),
(303,57),
(303,58),
(304,59),
(306,60),
(307,61),
(310,66),
(311,67),
(311,68),
(312,69),
(312,70),
(313,71),
(313,72),
(314,73),
(314,74),
(314,75),
(315,76),
(315,77),
(315,78),
(315,79),
(315,80),
(316,81),
(316,82),
(316,83),
(319,97),
(320,100),
(320,101),
(320,102),
(321,106),
(321,107),
(321,108),
(321,110),
(321,111),
(321,112),
(321,113),
(322,117),
(322,118),
(322,119),
(322,120),
(323,123),
(323,124),
(323,125),
(324,129),
(324,130),
(324,131),
(324,132),
(326,137),
(326,138),
(326,139),
(326,140),
(326,141),
(326,142),
(326,143),
(327,144),
(331,144),
(327,145),
(327,146),
(331,146),
(327,147),
(327,148),
(327,149),
(329,149),
(330,149),
(328,151),
(395,151),
(328,152),
(329,152),
(328,153),
(328,154),
(328,155),
(328,156),
(328,157),
(328,158),
(329,159),
(329,160),
(329,161),
(395,161),
(329,162),
(329,163),
(329,164),
(339,164),
(330,165),
(330,166),
(330,167),
(330,168),
(330,169),
(330,170),
(331,170),
(330,171),
(331,174),
(331,175),
(331,176),
(331,177),
(331,178),
(332,184),
(332,185),
(332,186),
(332,187),
(332,188),
(333,191),
(333,192),
(334,195),
(382,195),
(334,196),
(334,197),
(334,198),
(335,201),
(335,202),
(335,203),
(336,203),
(335,204),
(336,207),
(337,207),
(340,207),
(344,207),
(361,207),
(336,208),
(338,208),
(339,208),
(340,208),
(344,208),
(337,210),
(337,211),
(338,213),
(338,214),
(339,216),
(339,218),
(339,219),
(339,220),
(340,221),
(356,225),
(356,226),
(361,227),
(362,228),
(373,230),
(374,231),
(374,232),
(374,233),
(374,234),
(377,236),
(377,237),
(381,250),
(381,251),
(381,252),
(382,254),
(382,255),
(383,256),
(384,257),
(384,258),
(384,259),
(395,264),
(395,265),
(395,266),
(395,267);
/*!40000 ALTER TABLE `post_tag` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `post_votes`
--

DROP TABLE IF EXISTS `post_votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `visitor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `vote_type` enum('like','dislike') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_post_visitor` (`post_id`,`visitor_id`),
  KEY `idx_post_vote` (`post_id`,`vote_type`),
  KEY `post_votes_ibfk_2` (`visitor_id`),
  CONSTRAINT `post_votes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_votes_ibfk_2` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_votes`
--

LOCK TABLES `post_votes` WRITE;
/*!40000 ALTER TABLE `post_votes` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `post_votes` VALUES
(87,384,11,'2025-11-03 14:27:29','2025-11-03 14:27:29','like');
/*!40000 ALTER TABLE `post_votes` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `after_post_vote_insert` AFTER INSERT ON `post_votes` FOR EACH ROW BEGIN
    IF NEW.vote_type = 'like' THEN
        UPDATE posts SET likes_count = likes_count + 1 WHERE id = NEW.post_id;
    ELSEIF NEW.vote_type = 'dislike' THEN
        UPDATE posts SET dislikes_count = dislikes_count + 1 WHERE id = NEW.post_id;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_post_vote_update` BEFORE UPDATE ON `post_votes` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Изменение голоса запрещено';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `before_post_vote_delete` BEFORE DELETE ON `post_votes` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Удаление голоса запрещено';
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(128) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(128) NOT NULL,
  `content` text NOT NULL,
  `excerpt` varchar(200) DEFAULT NULL,
  `thumbnail_media_id` int(11) DEFAULT NULL,
  `video_link_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  `meta_title` varchar(128) DEFAULT NULL,
  `meta_keywords` varchar(255) DEFAULT NULL,
  `meta_description` varchar(160) DEFAULT NULL,
  `robots` enum('index','follow','noindex','nofollow','noindex, follow','index, follow','noindex, nofollow','index, nofollow') NOT NULL DEFAULT 'index' COMMENT 'Не используется',
  `status` enum('draft','pending','published','deleted') NOT NULL DEFAULT 'draft',
  `article_type` enum('post','page') NOT NULL DEFAULT 'post',
  `likes_count` int(10) unsigned NOT NULL DEFAULT 0,
  `dislikes_count` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_url` (`url`),
  KEY `posts_ibfk_1` (`user_id`),
  KEY `idx_status_article_updated` (`status`,`article_type`,`updated_at`),
  KEY `fk_posts_thumbnail_media` (`thumbnail_media_id`),
  KEY `idx_posts_created` (`created_at`),
  KEY `idx_posts_type_created` (`article_type`,`created_at`),
  KEY `video_link_index` (`video_link_id`),
  CONSTRAINT `fk_posts_thumbnail` FOREIGN KEY (`thumbnail_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_posts_thumbnail_media` FOREIGN KEY (`thumbnail_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_video_link` FOREIGN KEY (`video_link_id`) REFERENCES `video_links` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=439 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `posts` VALUES
(3,'taksuyu',2,'Про таксиста','Taксую. Ранее утро. Пассажир — женщина. Спокойно катим в пробке. Мимо нас в междурядье с грохотом проносится мотоциклист.\n— Ух, отчаянный, — провожаю его добрым словом.\n— Вы не гоняете, anekdotov.net, случаем, на байке? — спрашиваю женщину.\n— Я — нет, — улыбнулась она.\n— А вот племянник недавно признался, что мечтает о мотоцикле. И никакие доводы на него не действуют. Ни мои, ни матери. Хочу и всё! И понимаем, что запретить никак не получится. Купит втайне и гонять будет. Ну, тогда я ему и предложила месяц у меня поработать, и тогда пусть покупает. Если денег не хватит — добавлю. Работа чисто физическая. Принеси/унеси. Как раз для молодого парня. После второй смены он сказал, что передумал покупать мотоцикл.\n— А где вы работаете?\n— В реанимации.','',NULL,NULL,'2025-04-22 16:54:59','2025-06-19 18:12:34','','Про таксиста','описание Про таксиста','index','published','post',19,25),
(4,'sotrudnitsa-otdela-prodazh',2,'Про сотрудницу','Сотрудница отдела продаж, специалист по сервису и их начальник идут обедать и находят старую масляную лампу. Они трут лампу, и Джин появляется в облаке дыма. Джин говорит:\n— Обычно я выполняю три желания, поэтому каждый из Вас может загадать по одному.\n— Чур, я первая! , — говорит сотрудница отдела продаж. Я хочу быть сейчас на Багамах, мчаться без забот на скутере по волнам.\nПуфф! И она растворяется в воздухе. anekdotov.net,\n— Я следующий! , — говорит спец по сервису. Я хочу на Гавайи, расслабляться на пляже с личной массажисткой и бесконечным запасом Пина-Колады.\nПуфф! Исчезает.\n— OK, твоя очередь! , — говорит Джин менеджеру.\nТогда менеджер говорит:\n— Я хочу, чтобы эти двое были в офисе после обеда.','',NULL,NULL,'2025-04-01 16:57:23','2025-06-19 18:13:53','',NULL,NULL,'index','published','post',3,14),
(5,'pro-fax',2,'Про факс','\"В России одновременно сосуществуют поколения людей, anekdotov.net, которые в 20 лет ещё не знали, что такое \"отправить FAX\" и которые в 20 лет уже не знают, что такое \"отправить FAX\"\"\r\nИ вспомнилось...\r\nВ конце 90-х на \"ЛМЗ\" (Ленинградский Металлический завод, я там работал) и \"Электросилу\" наехали новые \"собственники\" с новыми порядками ведения бизнеса: коммерческая тайна и т. п. А я тогда как раз какой-то проект делал, как обычно: ЛМЗ-шная турбина и Электросиловский генератор. И мне от Электросилы технические данные нужны.\r\nЗвоню в их КБ, задаю вопросы. Мне в ответ: все данные готовы, но передать не можем — новый собственник запретил напрямую письма писать, телексы и факсы отправлять (е-мэйла у нас тогда ещё не было). Только через Службу Безопасности!\r\nА это ещё неделя на согласование!\r\nЯ устно информацию принять, конечно, могу, но мне именно документ нужен.\r\n— Что, совсем всё запретили?\r\n— Всё!\r\n— И телеграммы?\r\n— И телеграммы!\r\n— И факсы?\r\n— И факсы!\r\n— А телефонограммы?\r\n— О!\r\nЧерез 15 минут из факса вылезает лист с крупным заголовком: ТЕЛЕФОНОГРАММА!','',NULL,NULL,'2025-04-24 13:42:02','2025-06-21 15:30:35','',NULL,NULL,'index','published','post',46,6),
(184,'o-proekte',2,'О проекте','<p>Сайт СмехБук создан группой единомышленников, которых объединяет желание разместить в одном месте все те шутки и юмор, который накопился за определенное время в разных местах (серверах, личных записках, блогах). На нашем сайте мы будем собирать все те шутки и юмор, который есть в наших запасах и на сторонних проектах. Нашей задачей мы видим создание юмористического портала сатиры, юмора и хорошего настроения. <b>СмехБук строится на следующих принципах:</b> 1) Мы тщательно отбираем все присланные материалы на сайт. Наш приоритет сатира и юмор высокого качества, а не &laquo;ниже пояса&raquo;. Да и еще раз Да, мы правим анекдоты,шутки, цитаты и все остальные материалы, или удаляем без восстановления и повторного рассмотрения. 2) Мы любим красивые и тщательно сделанные вещи. Поэтому один из основных моментов при формировании материалов на сайт &mdash; это соблюдение правил оформления, все должно быть красиво, ясно и понятно посетителям сайта. 3) Мы не обсуждаем ваше чувство юмора и не собираемся обсуждать наше. Если по каким-то причинам вам не нравятся материалы на сайте, то вы можете проголосовать за них против или просто не посещать наш сайт.</p>','',NULL,NULL,'2025-06-08 12:22:28','2025-11-11 14:44:25','заголовок о проекте','юмор, приколы, смех','Описание страницы о проекте','index','published','page',0,0),
(185,'kontakty',2,'Контакты','Вы хотели связаться с нами? Это замечательно! Мы тоже давно мечтали о связи — не только электронной, но и духовной. Однако пока наша команда контактов занята важным делом: проверяет, работает ли кнопка \"отправить\" на самом деле, а также спорит, сколько должно быть адресов, чтобы считаться «множеством».\r\n\r\nПока они решают эти глобальные вопросы, мы предлагаем вам немного подождать. Скоро здесь появятся все возможные способы добраться до нас — от электронной почты до телепатии (последнее пока в стадии тестирования).\r\n\r\nЕсли у вас срочное дело, можете попробовать поймать одного из редакторов на просторах сайта — обычно они шатаются где-то между рубриками «Анекдоты» и «Истории». Или просто напишите нам через форму обратной связи (когда она заработает), или как-нибудь иначе — мы всегда рады общению, особенно если оно с юмором!\r\n\r\nТем временем предлагаем вам не скучать, а читать что-нибудь смешное. А потом рассказать об этом друзьям. Или врагам. Всё равно полезно.\r\n\r\nСпасибо, что вы с нами. Почти буквально.','',NULL,NULL,'2025-06-08 12:22:28','2025-07-29 12:22:28','','контакты','Описание страницы контакты','index','published','page',0,0),
(186,'policy',2,'Пользовательское соглашение','Сайт www.smehbook.ru размещается на серверах и управляется на территории\r\nРоссийской Федерации. Вся деятельность сайта находится под действием\r\nдействующего законодательства Российской Федерации.\r\n\r\nВсе права на присланные материалы (контент) принадлежат их владельцам —\r\nнепосредственным участникам и тем, кто прислал материалы (контент) через\r\nформу на нашем сайте. Администрация сайта не несет ответственности\r\nза их использование третьими сторонами.\r\n\r\nАдминистрация сайта не ставит перед собой цели оскорблять честь\r\nи достоинство физических лиц, либо посягать на чью-либо деловую\r\nрепутацию. Совпадения реальных имён и названий считаются случайными.\r\n\r\nАдминистрация сайта не занимается предварительной оценкой присланных\r\nматериалов (контента) на предмет соответствия их действующему\r\nзаконодательству, чьим-то личным представлениям о прекрасном,\r\nи не оценивает содержащиеся в материалах оценочные суждения. При этом\r\nадминистрация оставляет за собой право удалять явно нарушающие\r\nзаконодательство, разглашающие личные сведения или являющиеся личными\r\nвыпадами против конкретных лиц и групп лиц материалы (контент), в том\r\nчисле и автоматически, но не гарантирует полного отсутствия подобных\r\nматериалов (контента). В случае обнаружения таких материалов (контента)\r\nпросьба написать нам, мы постараемся принять меры.\r\n\r\nАдминистрация сайта имеет право публиковать, удалять\r\nи редактировать любые материалы (контент), присланные пользователями,\r\nпо своему усмотрению.\r\n\r\nАдминистрация сайта оставляет за собой право на использование\r\nинформации, содержащейся на сайте, по своему усмотрению.','',NULL,NULL,'2025-06-08 12:22:28','2025-06-08 12:22:28','','пользовательское соглашение','Описание страницы пользовательское соглашение','index','published','page',0,0),
(187,'sitemap',2,'Карта сайта','Вы попали сюда, значит, либо вы — опытный искатель приключений (и кнопок), либо просто потерялись среди нашего безграничного океана юмора. Не волнуйтесь, мы тоже иногда не можем найти, куда запрятали главную страницу.\r\n\r\nПока что эта карта больше похожа на меню в кафе, где половина блюд уже закончилась, а официант уверяет, что «всё есть, просто не всё сразу». Но обещаем — скоро здесь будет настоящий путеводитель по нашему порталу: удобный, понятный и такой же доброжелательный, как наш админ, когда он не в плохом настроении после чтения комментариев.\r\n\r\nЧто вы сможете найти:\r\n\r\nРазделы с анекдотами (свежими, как утренний хлеб, и не менее питательными).\r\nИстории из жизни (потому что реальность порой смешнее вымысла).\r\nВесёлые стихи (рифма есть — совесть не просим).\r\nАвторские колонки и сатира (остро, но не до крови).\r\nВозможно даже кое-что полезное — но это строго на ваш страх и риск.\r\nА пока предлагаем ориентироваться по принципу: «Кликнул — не пожалел», или как говорится у нас в редакции — «Вперёд, туда, где ещё никто не успел заблудиться!»\r\n\r\nЕсли вдруг найдёте что-то интересное по пути — не держите в себе, делитесь с друзьями. А если потеряетесь — не переживайте, выход всегда там, где вход.','',NULL,NULL,'2025-06-08 12:22:28','2025-06-08 12:40:49','','карта сайта','Описание страницы карта сайта','noindex, follow','published','page',0,0),
(188,'istoriya-1',2,'История 1','Представьте, что вы едете на машине. На спидометре стрелка показывает 60 км/ч — это ваша мгновенная скорость в конкретный момент времени. Именно так работает производная!\r\nОна отвечает на вопрос: «Как быстро меняется одна величина (например, путь) относительно другой (например, времени)?»\r\n\r\nПример из жизни:\r\n\r\nВы замечаете, что за 1 час температура на улице поднялась с +5°C до +10°C.\r\nСредняя скорость изменения: 10−51=5110−5​=5°C в час.\r\nНо если температура росла неравномерно (сначала быстро, потом медленно), производная покажет её мгновенное изменение в конкретную минуту.\r\nКак понять производную через графики\r\nДопустим, вы рисуете график, где по оси Х — время, а по оси Y — расстояние, которое вы прошли.\r\n\r\nСредняя скорость — это наклон прямой между двумя точками (например, за весь день).\r\nПроизводная (мгновенная скорость) — это наклон касательной к кривой в конкретной точке.\r\nПроще говоря, чем круче график в определённый момент, тем больше производная (и тем быстрее что-то меняется).\r\n\r\nПримеры из жизни, где встречается производная\r\n1. Экономика: прибыль компании\r\nДопустим, компания продаёт кофе.\r\n\r\nПроизводная покажет, как быстро растёт прибыль при увеличении продаж на 1 чашку.\r\nЕсли график прибыли резко идёт вверх — производная большая (бизнес процветает).\r\nЕсли график падает — производная отрицательная (убытки).\r\n2. Медицина: действие лекарства\r\nДоктор смотрит, как быстро снижается температура у пациента после приёма таблетки.\r\nПроизводная здесь — скорость выздоровления (например, на сколько градусов в час падает жар).\r\n3. Спорт: подготовка марафонца\r\nТренер анализирует, как увеличивается скорость бега спортсмена от недели к неделе.\r\nПроизводная покажет, в какой момент прогресс замедлился и нужно сменить тренировки.\r\n4. Строительство: наполнение бассейна\r\nЕсли вы открываете кран, производная — это скорость наполнения (литры в минуту).\r\nЕсли кран засорился и вода течёт медленнее, производная уменьшается.\r\nЗачем это нужно?\r\nПроизводная помогает:\r\n\r\nРассчитать оптимальную скорость поезда, чтобы он не опоздал.\r\nПредсказать, когда закончится бензин в баке.\r\nПонять, как быстро тают льды в Арктике.\r\nСоздавать реалистичную анимацию в играх (например, падение мяча под уклон).\r\nКак посчитать производную? (Минимум формул)\r\nДля тех, кто хочет чуть больше математики:\r\n\r\nВозьмите функцию, например, y=x² (путь зависит от времени).\r\nПроизводная этой функции — y′=2x.\r\nЭто значит, что скорость изменения y в любой момент x равна 2x.\r\nНапример:\r\n\r\nВ момент времени x=3 скорость будет 2×3=6.\r\nЧем больше x, тем быстрее растёт y.\r\nКрасным проведена касательная к параболе в точке А.\r\nНо не пугайтесь: в жизни производные часто считают компьютеры. Ваша задача — понять, что они означают.\r\n\r\nПроизводная — это как «математическая интуиция», она помогает чувствовать, как мир меняется вокруг нас:\r\n\r\nКогда вы ждёте автобус и решаете, бежать или идти шагом,\r\nКогда видите, как быстро темнеет зимой,\r\nИли когда пытаетесь успеть на скидку в магазине.\r\nПроизводная превращает абстрактные числа в истории о движении, росте и времени. Попробуйте замечать эти изменения — и математика станет чуть ближе к реальности!\r\n\r\nА вы задумывались, как быстро меняется что-то в вашей жизни? Делитесь примерами в комментариях.','',NULL,NULL,'2025-04-22 16:54:59','2025-04-24 13:44:14','','История 1','описание истории 1','index, follow','published','post',0,1),
(197,'predlozhennyy-material-2025-06-13-202910',2,'Пост от 13.06.2025','sdfgsdfgdfsdfgsdfgdfgdfg','',NULL,NULL,'2025-06-13 17:29:10','2025-06-13 17:29:10','',NULL,NULL,'index','published','post',1,0),
(198,'predlozhennyy-material-2025-06-13-203658',2,'Пост от 13.06.2025','asdfasdfadsf','',NULL,NULL,'2025-06-13 17:36:58','2025-06-13 17:36:58','',NULL,NULL,'index','published','post',1,0),
(215,'predlozhennyy-material-2025-06-13-204608',2,'Пост от 13.06.2025','вапывапрывапвпвапвапв','',NULL,NULL,'2025-06-13 17:46:08','2025-06-13 17:46:08','',NULL,NULL,'index','published','post',0,0),
(216,'predlozhennyy-material-2025-06-15-190627',2,'Пост от 15.06.2025','df ghd ghdfgf','',NULL,NULL,'2025-06-15 16:06:27','2025-06-15 16:06:27','',NULL,NULL,'index','published','post',0,0),
(217,'predlozhennyy-material-2025-06-15-191225',2,'Пост от 15.06.2025','gsdfgsdgsdfggfs','',NULL,NULL,'2025-06-15 16:12:25','2025-06-15 16:12:25','',NULL,NULL,'index','published','post',0,0),
(218,'predlozhennyy-material-2025-06-15-193232',2,'Пост от 15.06.2025','ячсмчясмвыавыафыва','',NULL,NULL,'2025-06-15 16:32:32','2025-06-15 16:32:32','',NULL,NULL,'index','published','post',0,0),
(219,'predlozhennyy-material-2025-06-15-193249',2,'Пост от 15.06.2025','ыва ыва вапыва п','',NULL,NULL,'2025-06-15 16:32:49','2025-06-15 16:32:49','',NULL,NULL,'index','published','post',0,0),
(220,'predlozhennyy-material-2025-06-15-193614',2,'Пост от 15.06.2025','we g ывап sdfgsdfg','',NULL,NULL,'2025-06-15 16:36:14','2025-06-15 16:36:14','',NULL,NULL,'index','published','post',0,0),
(221,'predlozhennyy-material-2025-06-15-193836',2,'Пост от 15.06.2025','sвапывапваып','',NULL,NULL,'2025-06-15 16:38:36','2025-06-15 16:38:36','',NULL,NULL,'index','published','post',0,0),
(222,'predlozhennyy-material-2025-06-15-193851',2,'Пост от 15.06.2025','вы ыва sdfs','',NULL,NULL,'2025-06-15 16:38:51','2025-06-15 16:38:51','',NULL,NULL,'index','published','post',0,0),
(227,'predlozhennyy-material-2025-06-15-200020',2,'jhkjhkj','sdfsdfsdfsdfs','',NULL,NULL,'2025-06-15 17:00:20','2025-06-15 17:00:20','',NULL,NULL,'index','published','post',0,0),
(228,'predlozhennyy-material-2025-06-15-200129',2,'Пост от 15.06.2025','sdfsgsdfgasdfsad','',NULL,NULL,'2025-06-15 17:01:29','2025-06-15 17:01:29','',NULL,NULL,'index','published','post',0,0),
(230,'predlozhennyy-material-2025-06-15-200311',2,'Пост от 15.06.2025','вапвап вап вап','',NULL,NULL,'2025-06-15 17:03:11','2025-06-15 17:03:11','',NULL,NULL,'index','published','post',0,0),
(231,'predlozhennyy-material-2025-06-15-201000',2,'Пост от 15.06.2025','фывафываыфваыфва','',NULL,NULL,'2025-06-15 17:10:00','2025-06-15 17:10:00','',NULL,NULL,'index','published','post',0,0),
(232,'predlozhennyy-material-2025-06-15-203040',2,'Пост от 15.06.2025','asdfasdfasdfaf','',NULL,NULL,'2025-06-15 17:30:40','2025-06-15 17:30:40','',NULL,NULL,'index','published','post',0,1),
(233,'predlozhennyy-material-2025-06-15-203143',2,'Пост от 15.06.2025','sdfasdfsdf','',NULL,NULL,'2025-06-15 17:31:43','2025-06-15 17:31:43','',NULL,NULL,'index','published','post',0,0),
(234,'predlozhennyy-material-2025-06-22-190339',2,'Пост от 22.06.2025','фывафыафываффывафв','',NULL,NULL,'2025-06-22 16:03:39','2025-06-22 16:03:39','',NULL,NULL,'index','published','post',0,0),
(235,'predlozhennyy-material-2025-06-22-191810',2,'Пост от 22.06.2025','лоприлотлролыыы','',NULL,NULL,'2025-06-22 16:18:10','2025-06-22 16:18:10','',NULL,NULL,'index','published','post',0,0),
(236,'pyatiy-klass',2,'пятый класс','В пятом классе я записался в кружок математики в Ленинградском Дворце Пионеров, который находился на Невском проспекте. Мы жили на окраине и родители побаивались отпускать 11- летнего ребёнка одного.\r\nНо папа здраво рассудил: \"Пускай лучше ездит по городу, чем болтается во дворе. \"\r\nМама меня напутствовала: \"Будь осторожен: в центре полно хулиганов, воров и... проституток! \"\r\nКонечно я примерно представлял, кто такие проститутки, но не очень понимал зачем я им нужен, и потому не придал маминым словам большого значения. А зря!\r\nОколо входа во Дворец Пионеров меня окружила толпа каких-то девиц, примерно моего возраста. Они стали хватать меня за руки и требовать: \"Пойдём с нами! \"\r\nЯ понял: \"Вот они, anekdotov.net, проститутки! Мама была права! \", вырвался от них и убежал.','',NULL,NULL,'2025-06-22 16:42:04','2025-06-22 16:42:04','','5 класс','5 класс','index, follow','published','post',0,0),
(237,'gomeostizis',2,'гомеостазис','Был у нас один преподаватель, как говорится ума палата. Любил он всякими научными терминами бросаться. И как-то рассказывает нам по этому поводу историю.\r\nВот однажды пошел он с сынишкой купаться. anekdotov.net, Зашел по колено в воду. Благодать! Солнышко светит, птички поют. Ну и препод так восхищенно говорит:\r\n— Вот это гомеостазис!!!\r\nСынишка спрашивает:\r\n— Пап, а что такое гомеостазис?\r\n— Ну, это когда тебе хорошо, единство в природе, так сказать, состояние равновесия. В общем, это когда вокруг все тихо, размеренно, когда нет внутренних противоречий.\r\nСынок у него смышленый попался, словечко мудрое запомнил. И вот как-то на уроке природоведения учительница спрашивает про круговорот веществ в природе. Мальчик тут же вскакивает и кричит:\r\n— Мария Ивановна, это полный гомеост','',NULL,NULL,'2025-06-22 16:44:27','2025-06-22 16:44:27','','гомеостазис','гомеостазис','index, follow','published','post',0,0),
(238,'park',2,'парк','Семилетний мальчик веpнyлся домой из паpка без своих новых салазок.\r\n— Вы знаете, — сказал он pодителям, — y меня их попpосил покататься стаpик с симпатичным малышом. К 4 часам они пообещали салазки веpнyть.\r\nРодителям все это не очень понpавилось, но в глyбине дyши они были довольны пpоявлением столь добpых чyвств со стоpоны своего pебенка. Четыpе часа — нет салазок. Hо в 4:30 pаздался звонок, и появился стаpик с малышом, салазками и большой коpобкой конфет. Сын тyт же скpылся в спальне и, выбежав оттyда, anekdotov.net, внимательно осмотpел салазки, и заявил:\r\n— Все в поpядке, полyчите свои часы.','',NULL,NULL,'2025-06-22 16:44:27','2025-06-22 16:44:27','','парк','парк','index, follow','published','post',0,0),
(239,'vovchka',2,'вовочка','Вовочка получил два балла за контрольную. Отец пришел в школу разбираться. Учительница ему говорит, ваш сын все списал у своей соседки:\r\n- Вот смотрите, вопрос - в каком году родился Пушкин.\r\nМаша правильно пишет - в 1799. anekdotov.net, И ваш сын тоже.\r\n- Ну так и что? Почему мой сын не может правильно ответить?\r\n- Смотрите дальше. Вопрос - кто написал \"Войну и мир\"?\r\nМаша пишет - Лермонтов, и Вовочка то же самое.\r\n- А почему они не могли оба ошибиться?\r\n- Ну, допустим. Но вот следующий вопрос - какие пьесы написал Чехов?\r\nМаша написала - \"Я не знаю\". А Вовочка - \"И я тоже\".','',NULL,NULL,'2025-06-22 16:47:32','2025-06-22 16:47:32','','вовочка','вовочка','index, follow','published','post',1,0),
(240,'ironiya',2,'ирония','— Дети, anekdotov.net, кто знает, что такое ирония?\r\nВовочка:\r\n— Это когда я говорю, что мне нравится делать домашнее задание.','',NULL,NULL,'2025-06-22 16:47:32','2025-06-22 16:47:32','','ирония','ирония','index, follow','published','post',0,0),
(241,'cod',2,'код','  $total_posts = $this->model->countAllPosts();\r\n\r\n    // Генерируем ссылки пагинации\r\n    $pagination_links = generatePaginationLinks(\r\n        $page,\r\n        $total_posts,\r\n        $posts_per_page,\r\n        \'/\' // базовый URL\r\n    );\r\n\r\n    $URL = sprintf(\"%s://%s\", $_SERVER[\'REQUEST_SCHEME\'], $_SERVER[\'HTTP_HOST\']);\r\n\r\n    $content = View::render(\'../app/views/posts/index.php\', [\r\n        \'posts\' => $posts,\r\n        \'show_caption\' => false,\r\n        \'url\' => $URL,\r\n        \'pagination\' => [\r\n            \'current_page\' => $page,\r\n            \'posts_per_page\' => $posts_per_page,','',NULL,NULL,'2025-06-22 17:22:30','2025-06-22 17:22:30','','код','код','index, follow','published','post',0,0),
(242,'predlozhennyy-material-2025-06-24-193559',2,'Пост от 24.06.2025','dgfdsfgdsfgsdfgsdfgds','',NULL,NULL,'2025-06-24 16:35:59','2025-08-15 16:22:52','',NULL,NULL,'index','deleted','post',0,0),
(243,'predlozhennyy-material-2025-06-24-193638',2,'Пост от 24.06.2025','fghdfghdfghdfgh','',NULL,NULL,'2025-06-24 16:36:38','2025-06-24 16:36:38','',NULL,NULL,'index','published','post',0,0),
(244,'predlozhennyy-material-2025-06-24-193642',2,'Пост от 24.06.2025','gdhjgjghjfghjg','',NULL,NULL,'2025-06-24 16:36:42','2025-08-15 16:23:10','',NULL,NULL,'index','deleted','post',0,0),
(245,'predlozhennyy-material-2025-06-24-193646',2,'Пост от 24.06.2025','fghjghjgfhjhfgj','',NULL,NULL,'2025-06-24 16:36:46','2025-06-24 16:36:46','',NULL,NULL,'index','published','post',1,0),
(246,'predlozhennyy-material-2025-06-24-193649',2,'Пост от 24.06.2025','fghjghghjghj','',NULL,NULL,'2025-06-24 16:36:49','2025-06-24 16:36:49','',NULL,NULL,'index','published','post',1,0),
(247,'predlozhennyy-material-2025-06-24-193653',2,'Пост от 24.06.2025','fghjghjgfhjg','',NULL,NULL,'2025-06-24 16:36:53','2025-06-24 16:36:53','',NULL,NULL,'index','published','post',0,1),
(248,'predlozhennyy-material-2025-06-24-193657',2,'Пост от 24.06.2025','fghjghjfghjg','',NULL,NULL,'2025-06-24 16:36:57','2025-06-24 16:36:57','',NULL,NULL,'index','published','post',1,0),
(249,'predlozhennyy-material-2025-06-24-193701',2,'Пост от 24.06.2025','fghjghjgfhgf','',NULL,NULL,'2025-06-24 16:37:01','2025-06-24 16:37:01','',NULL,NULL,'index','published','post',1,0),
(250,'predlozhennyy-material-2025-06-24-193704',2,'Пост от 24.06.2025','gfjghjghjg','',NULL,NULL,'2025-06-24 16:37:04','2025-06-24 16:37:04','',NULL,NULL,'index','published','post',1,0),
(251,'predlozhennyy-material-2025-06-24-193708',2,'Пост от 24.06.2025','fghjghjgjghj','',NULL,NULL,'2025-06-24 16:37:08','2025-06-24 16:37:08','',NULL,NULL,'index','published','post',1,0),
(252,'predlozhennyy-material-2025-06-24-193711',2,'Пост от 24.06.2025','fghjghjfghjgf','',NULL,NULL,'2025-06-24 16:37:11','2025-06-24 16:37:11','',NULL,NULL,'index','published','post',0,1),
(253,'predlozhennyy-material-2025-06-24-193715',2,'Пост от 24.06.2025','паропропаро','',NULL,NULL,'2025-06-24 16:37:15','2025-06-24 16:37:15','',NULL,NULL,'index','published','post',1,0),
(254,'predlozhennyy-material-2025-06-24-193718',2,'Пост от 24.06.2025','паропаропро','',NULL,NULL,'2025-06-24 16:37:18','2025-06-24 16:37:18','',NULL,NULL,'index','published','post',0,1),
(255,'predlozhennyy-material-2025-06-24-193721',2,'Пост от 24.06.2025','пароапропаро','',NULL,NULL,'2025-06-24 16:37:21','2025-06-24 16:37:21','',NULL,NULL,'index','published','post',1,0),
(256,'predlozhennyy-material-2025-06-24-193725',2,'Пост от 24.06.2025','3245234муцкемкмек','',NULL,NULL,'2025-06-24 16:37:25','2025-06-24 16:37:25','',NULL,NULL,'index','published','post',0,1),
(257,'predlozhennyy-material-2025-06-24-193729',2,'Пост от 24.06.2025','гьлопртопато','',NULL,NULL,'2025-06-24 16:37:29','2025-06-24 16:37:29','',NULL,NULL,'index','published','post',0,0),
(258,'predlozhennyy-material-2025-06-24-193732',2,'Пост от 24.06.2025','sdfgvdsgvf','',NULL,NULL,'2025-06-24 16:37:32','2025-06-24 16:37:32','',NULL,NULL,'index','published','post',0,1),
(259,'predlozhennyy-material-2025-06-24-193735',2,'Пост от 24.06.2025','кетноектекн','',NULL,NULL,'2025-06-24 16:37:35','2025-06-24 16:37:35','',NULL,NULL,'index','published','post',1,1),
(260,'predlozhennyy-material-2025-06-24-193739',2,'Пост от 24.06.2025','ипаравипрвирав','',NULL,NULL,'2025-06-24 16:37:39','2025-06-24 16:37:39','',NULL,NULL,'index','pending','post',0,0),
(261,'predlozhennyy-material-2025-06-24-193742',2,'Пост от 24.06.2025','тнгнгтентгн','',NULL,NULL,'2025-06-24 16:37:42','2025-06-24 16:37:42','',NULL,NULL,'index','pending','post',0,0),
(262,'predlozhennyy-material-2025-06-24-193748',2,'Пост от 24.06.2025','иукинкеикерирпа','',NULL,NULL,'2025-06-24 16:37:48','2025-06-24 16:37:48','',NULL,NULL,'index','pending','post',0,0),
(263,'predlozhennyy-material-2025-07-06-164047',2,'Пост от 06.07.2025','5645b645y куреп уеапреа','',NULL,NULL,'2025-07-06 13:40:47','2025-07-06 13:40:47','',NULL,NULL,'index','published','post',1,1),
(264,'predlozhennyy-material-2025-07-06-164109',2,'Пост от 06.07.2025','смичсмисчмисчмисчми','',NULL,NULL,'2025-07-06 13:41:09','2025-07-06 13:41:09','',NULL,NULL,'index','draft','post',1,0),
(265,'predlozhennyy-material-2025-07-06-164625',2,'Пост от 06.07.2025','фысч xc vxcv cvx','',NULL,NULL,'2025-07-06 13:46:25','2025-07-06 13:46:25','',NULL,NULL,'index','deleted','post',1,0),
(266,'predlozhennyy-material-2025-07-13-190430',2,'Пост от 13.07.2025','sadfsadafsadf','',NULL,NULL,'2025-07-13 16:04:30','2025-07-13 16:04:30','',NULL,NULL,'index','pending','post',0,0),
(267,'predlozhennyy-material-2025-07-28-201000',2,'Пост от 28.07.2025','sdfdfgdsfgdfgdsgdfdfsg','',NULL,NULL,'2025-07-28 17:10:00','2025-07-28 17:10:00','',NULL,NULL,'index','published','post',0,1),
(268,'predlozhennyy-material-2025-07-28-201159',2,'Пост от 28.07.2025','смитсмитсмитсмитсми','',NULL,NULL,'2025-07-28 17:11:59','2025-07-28 17:11:59','',NULL,NULL,'index','published','post',16,0),
(269,'predlozhennyy-material-2025-08-05-165354',2,'Пост от 05.08.2025','asfdasdfsadfsadfsadf','',NULL,NULL,'2025-08-05 13:53:54','2025-08-05 13:53:54','',NULL,NULL,'index','published','post',2,0),
(270,'predlozhennyy-material-2025-08-06-173232',2,'Пост от 06.08.2025','в ва dfsg вап вап вапвыап','',NULL,NULL,'2025-08-06 14:32:32','2025-08-06 14:32:32','',NULL,NULL,'index','published','post',2,2),
(271,'predlozhennyy-material-2025-08-08-202043',2,'Пост от 08.08.2025','asdasasdas','',NULL,NULL,'2025-08-08 17:20:43','2025-08-08 17:20:43','',NULL,NULL,'index','pending','post',0,0),
(272,'4645645',2,'4645645','','',NULL,NULL,'2025-08-10 15:28:23','2025-08-10 15:28:23','','','','index','draft','post',0,0),
(273,'prv-apr-ap',2,'прв апр ап','','',NULL,NULL,'2025-08-10 15:30:21','2025-08-10 15:30:21','','','','index','draft','post',0,0),
(274,'novaya-kartinka',2,'новая картинка','','',NULL,NULL,'2025-08-10 15:42:25','2025-08-10 15:42:25','','','','index','published','post',1,0),
(275,'5inueikeniekn',2,'5инуеикениекн','<p>впвпвапвапвап</p>','',NULL,NULL,'2025-08-10 15:45:52','2025-08-10 15:45:52','','','','index','published','post',1,1),
(276,'terteter',2,'terteter','','',NULL,NULL,'2025-08-10 15:51:07','2025-08-10 15:51:07','','','','index','draft','post',0,0),
(277,'erterter',2,'erterter','','',NULL,NULL,'2025-08-10 15:51:16','2025-08-10 15:51:16','','','','index','draft','post',0,0),
(278,'rterterter',2,'rterterter','','',NULL,NULL,'2025-08-10 15:51:27','2025-08-10 15:51:27','','','','index','draft','post',0,0),
(279,'3tertewrw',2,'3tertewrw','','',NULL,NULL,'2025-08-10 15:56:27','2025-08-10 15:56:27','','','','index','draft','post',0,0),
(280,'vapkekue',2,'вапкекуе','','',NULL,NULL,'2025-08-10 16:13:34','2025-08-10 16:13:34','','','','index','draft','post',0,0),
(281,'464564565464',2,'464564565464','','',27,NULL,'2025-08-10 16:19:38','2025-08-10 16:19:38','','','','index','draft','post',0,0),
(282,'proraprparap',2,'прорапрпарап','Сидят два друга в баре. Один, Вася, грустный-грустный. Другой, Петя, спрашивает:\r\n— Вась, ты чего такой кислый?\r\n— Да понимаешь, вчера с женой поссорился. Долго ругались, потом она говорит: «Всё, я от тебя ухожу! У меня есть три мужчины, которые ждут моего звонка. Один из них — начальник, он предложил мне повышение. Второй — коллега, он предложил мне работу в другом городе. А третий… Ну, третий просто ждёт».\r\n— Ну и что? — спрашивает Петя.\r\n— А я, дурак, ей говорю: «Ну и звони своим трём мужикам! А я найду себе трёх женщин, которые ждут моего звонка!»\r\n— И что?\r\n— Звоню одной. Спрашиваю: «Мам, как дела?» Вторая — сестра, третья — тётя. А теперь сижу и думаю… А жена-то, наверное, уже ушла.','dgdfgdfgdf',25,NULL,'2025-08-10 16:21:56','2025-08-10 16:21:56','','вапвапавп','вапвап','index','published','post',2,0),
(283,'proba',2,'проба','','',NULL,NULL,'2025-08-10 16:26:37','2025-08-10 16:26:37','','','','index','draft','post',0,0),
(284,'ukeuke',2,'укеуке','','',NULL,NULL,'2025-08-10 16:36:13','2025-08-10 16:36:13','','','','index','draft','post',0,0),
(285,'fsdfdsf',2,'fsdfdsf','','',NULL,NULL,'2025-08-10 16:36:54','2025-08-10 16:36:54','','','','index','draft','post',0,0),
(287,'88888',2,'88888','<p>Почему бык, который всю жизнь бегал за коровами, наконец женился на свинье?</p>\r\n<p>Потому что он устал от отношений, где всегда он был виноват, и захотел чего-то нового.</p>','',51,NULL,'2025-08-12 13:48:52','2025-08-15 15:49:23','','','','index','deleted','post',1,0),
(288,'werewrwerforma',2,'werewrwer','<p>ываыавыаываы</p>\r\n<p>ыва</p>\r\n<p>sd</p>\r\n<p>а</p>\r\n<p>выа</p>\r\n<p>sd</p>\r\n<p>f</p>','',NULL,NULL,'2025-08-12 15:18:53','2025-08-12 15:18:53','','','','index','draft','post',0,0),
(289,'23423432',2,'Искусство Замедления','\r\nВ бешеном ритме современности легко потерять ощущение настоящего. Мы постоянно скользим между тревожными мыслями о будущем и анализом ошибок прошлого, забывая, что единственное время, которым мы действительно владеем, — это текущая секунда.\r\n\r\nПопробуйте остановиться. Не нужно бросать дела, достаточно просто на мгновение осознать своё окружение. Почувствуйте вес тела на стуле, услышьте далёкие звуки за окном, заметьте текстуру предмета, который держите в руке. Эти маленькие, простые акты внимательности — как якорь, который возвращает вас в реальность.\r\n\r\nЭто не просто упражнение, это практика благодарности и способ снизить уровень стресса. Когда вы полностью присутствуете в моменте — будь то чашка утреннего кофе, разговор с другом или выполнение рабочей задачи, — вы извлекаете из этого события максимум, делая жизнь более насыщенной и осмысленной.','',27,NULL,'2025-08-14 12:51:31','2025-08-14 12:51:31','','','','index','published','post',1,0),
(290,'novyj-super-zagolovok',2,'новый супер заголовок','<div _ngcontent-ng-c3041318976=\"\" class=\"markdown markdown-main-panel enable-updated-hr-color\" id=\"model-response-message-contentr_bd0cbf24013a6e78\" dir=\"ltr\">\r\n<p>Идут по джунглям обезьяна и бегемот. Обезьяна, как всегда, не может усидеть на месте &mdash; прыгает с лианы на лиану, корчит рожицы. Вдруг она видит огромный, красивый кокос, который висит на самой верхушке пальмы.</p>\r\n<p>&mdash; Эй, бегемот, &mdash; кричит обезьяна, &mdash; смотри, какой кокос! Хочешь?</p>\r\n<p>Бегемот хмуро качает головой:</p>\r\n<p>&mdash; Да что мне твой кокос? Я и так сыт.</p>\r\n<p>Обезьяна, конечно, не унимается. Она мгновенно забирается на пальму, срывает кокос и спускается вниз.</p>\r\n<p>&mdash; Ладно, &mdash; говорит она, &mdash; тогда я его расколю и съем сама.</p>\r\n<p>Она начинает бить кокосом о камень, но ничего не выходит. Кокос твёрдый, как камень, а обезьяна маленькая и слабая.</p>\r\n<p>&mdash; Помоги, бегемот! &mdash; просит она. &mdash; У тебя такая большая челюсть, ты разгрызёшь его в два счёта.</p>\r\n<p>Бегемот, который как раз решил вздремнуть, открывает один глаз, лениво зевает:</p>\r\n<p>&mdash; Отстань, обезьяна. Я отдыхаю.</p>\r\n<p>Тогда обезьяна решает пойти на хитрость. Она берёт кокос, подходит к бегемоту и говорит:</p>\r\n<p>&mdash; А знаешь, бегемот, ты ведь очень сильный. Наверное, самый сильный в этих джунглях! Даже этот кокос перед тобой не устоит.</p>\r\n<p>Бегемот недоверчиво смотрит на неё, но лесть ему приятна.</p>\r\n<p>&mdash; Ну&hellip; я не знаю.</p>\r\n<p>&mdash; Как не знаешь?! &mdash; восклицает обезьяна. &mdash; Да ты одним своим ударом можешь свалить целое дерево! А этот кокос &mdash; просто ерунда!</p>\r\n<p>Бегемот улыбается своей широкой улыбкой:</p>\r\n<p>&mdash; Ну&hellip; это да. Сила у меня есть.</p>\r\n<p>&mdash; Вот и покажи! &mdash; настаивает обезьяна. &mdash; Просто раздави его!</p>\r\n<p>Бегемот берёт кокос и сжимает его в своей огромной пасти. Хруст, треск &mdash; и кокос разлетается на мелкие кусочки.</p>\r\n<p>Обезьяна с восторгом собирает их, запихивая в рот, и говорит, радостно потирая лапы:</p>\r\n<p>&mdash; Вот видишь! Ты настоящий чемпион!</p>\r\n<p>Бегемот довольный, но немного сонный, снова закрывает глаза и говорит:</p>\r\n<p>&mdash; Ладно, обезьяна, я пошёл.</p>\r\n<p>Идёт бегемот по тропинке, а навстречу ему &mdash; другой бегемот.</p>\r\n<p>&mdash; Привет, &mdash; говорит второй бегемот. &mdash; Что-то ты грустный.</p>\r\n<p>&mdash; Да нет, &mdash; отвечает первый, &mdash; просто устал.</p>\r\n<p>&mdash; А чего устал?</p>\r\n<p>&mdash; Да обезьяна какая-то привязалась&hellip; Сначала кокос разгрызи, потом ещё чего-нибудь придумает&hellip;</p>\r\n<p>&mdash; Понятно, &mdash; кивает второй бегемот. &mdash; Ну, а зачем ты ей вообще помогал?</p>\r\n<p>&mdash; Так она меня убедила, что я сильный! &mdash; гордо говорит первый бегемот. &mdash; Сказала, что я могу свалить дерево одним ударом.</p>\r\n<p>Второй бегемот удивлённо смотрит на него:</p>\r\n<p>&mdash; Ну, а почему ты ей просто не сказал, что это не так?</p>\r\n<p>Первый бегемот пожимает плечами:</p>\r\n<p>&mdash; Да потому что я и вправду могу свалить дерево одним ударом. А она&hellip; она просто обезьяна.</p>\r\n</div>','',25,NULL,'2025-08-14 15:30:01','2025-08-14 15:30:01','','обезьяний анекдот ключевик','обезьяний анекдот описание','index','draft','post',0,0),
(291,'345345345',2,'345345345','<p>dsfgdsfgdfsgd</p>','',NULL,NULL,'2025-08-15 13:50:36','2025-08-15 13:50:36','','','','index','draft','post',0,0),
(292,'fdg-vapvyapyva',2,'fdg вапвыапыва','<p>выпа ывап dfsg ывап ывап выа пвапыва пва</p>','',27,NULL,'2025-08-15 13:51:30','2025-08-15 13:51:30','','','','index','published','post',0,1),
(293,'pa-pa-vpr-ap-avpra-p',2,'па па впр ап авпра п','<p>&nbsp;sf а пывар ке ра равр ап</p>','',NULL,NULL,'2025-08-15 13:52:19','2025-08-15 13:52:19','','','','index','published','post',1,0),
(294,'avp-sdfgdf',2,'авп sdfgdf','<p>ыва пвап ывап ывап ывап вапв</p>','',51,NULL,'2025-08-15 13:53:17','2025-08-15 13:53:17','','','','index','draft','post',0,0),
(295,'parop-opr',2,'пароп опр','<p>&nbsp;fsdgdf пва пываы&nbsp;</p>','',NULL,NULL,'2025-08-15 13:54:00','2025-08-15 13:54:00','','','','index','draft','post',0,0),
(296,'3453453453',2,'3453453453','<p>terwtewterwt</p>','',NULL,NULL,'2025-08-15 14:01:49','2025-08-15 16:08:11','','','','index','deleted','post',0,0),
(297,'k-ek-apr-vapr-apr-ap',2,'к ек апр вапр апр ап','','',NULL,NULL,'2025-08-15 14:03:38','2025-08-15 15:49:09','','','','index','deleted','post',0,0),
(298,'rapr-vparvap',2,'рапр впарвап','','',NULL,NULL,'2025-08-15 14:43:30','2025-08-15 15:48:57','','','','index','deleted','post',0,0),
(299,'vaprapr',2,'вапрапр','<p>sdfgdfsf</p>','',NULL,NULL,'2025-08-15 16:26:32','2025-08-15 16:26:32','','','','index','draft','post',0,0),
(300,'werterwtert',2,'werterwtert','','',NULL,NULL,'2025-08-15 16:30:25','2025-08-15 16:30:25','','','','index','draft','post',0,0),
(301,'dfs-gsdfg-vap-sdfgd',2,'dfs gsdfg вап sdfgd','','',NULL,NULL,'2025-08-15 16:31:35','2025-08-15 16:31:35','','','','index','draft','post',0,0),
(303,'sd-gdsfg',2,'sd gdsfg','','',NULL,NULL,'2025-08-15 16:39:02','2025-08-15 16:39:02','','','','index','draft','post',0,0),
(304,'ukuckcukc',2,'укуцкцукц','','',NULL,NULL,'2025-08-17 14:52:16','2025-08-17 14:52:16','','','','index','draft','post',0,0),
(306,'va-rap-rap-ravp-arp',2,'ва рап рап равп арп','','',NULL,NULL,'2025-08-17 15:10:06','2025-08-17 15:10:06','','','','index','draft','post',0,0),
(307,'a-gds-avap-sdfgd',2,'а gds авап sdfgd','','',NULL,NULL,'2025-08-17 15:10:34','2025-08-17 15:10:34','','','','index','draft','post',0,0),
(310,'s-vapv-va-pavypa',2,'s вапв ва павыпа','','',NULL,NULL,'2025-08-17 15:29:20','2025-08-17 15:29:20','','','','index','draft','post',0,0),
(311,'f-g-dh-fg-rvap-rvapr',2,'f g dh fg рвап рвапр','','',NULL,NULL,'2025-08-17 15:31:17','2025-08-17 15:31:17','','','','index','draft','post',0,0),
(312,'dgdf-gds-gdsfg',2,'dgdf gds gdsfg','','',NULL,NULL,'2025-08-17 15:35:03','2025-08-17 15:35:03','','','','index','draft','post',0,0),
(313,'v-vyp-vyap-v',2,'в вып выап в','','',NULL,NULL,'2025-08-17 15:51:43','2025-08-17 15:51:43','','','','index','draft','post',0,0),
(314,'vap-vayppv',2,'вап ваыппв','','',NULL,NULL,'2025-08-17 16:06:10','2025-08-17 16:06:10','','','','index','draft','post',0,0),
(315,'vya-papyp',2,'выа папып','','',NULL,NULL,'2025-08-17 16:12:02','2025-08-17 16:12:02','','','','index','draft','post',0,0),
(316,'va-gdg-gf',2,'ва gdg gf','','',NULL,NULL,'2025-08-17 16:17:34','2025-08-17 16:17:34','','','','index','draft','post',0,0),
(319,'sfd-ggs-vapy',2,'sfd ggs вапы','','',NULL,NULL,'2025-08-17 16:27:31','2025-08-17 16:27:31','','','','index','draft','post',0,0),
(320,'vya-pa-yvap-yvap-sdfgsd',2,'выа па ывап ывап sdfgsd','','',NULL,NULL,'2025-08-17 16:28:56','2025-08-17 16:28:56','','','','index','draft','post',0,0),
(321,'yva-pavy',2,'ыва павы','','',NULL,NULL,'2025-08-17 16:30:52','2025-08-17 16:30:52','','','','index','draft','post',0,0),
(322,'vya-pvva',2,'выа пвва','','',NULL,NULL,'2025-08-17 16:35:00','2025-08-17 16:35:00','','','','index','draft','post',0,0),
(323,'var-apavpr',2,'вар апавпр','','',NULL,NULL,'2025-08-18 11:44:15','2025-08-18 11:44:15','','','','index','draft','post',0,0),
(324,'vapv-vy',2,'вапв вы','','',NULL,NULL,'2025-08-18 11:48:54','2025-08-18 11:48:54','','','','index','draft','post',0,0),
(325,'a-rap-rapr',2,'а рап рапр','','',NULL,NULL,'2025-08-18 12:06:18','2025-08-18 12:06:18','','','','index','draft','post',0,0),
(326,'s-pva-pyvap',2,'s пва пывап','','',NULL,NULL,'2025-08-18 12:17:26','2025-08-18 12:17:26','','','','index','draft','post',0,0),
(327,'apvp-vap-v',2,'апвп вап в','','',NULL,NULL,'2025-08-18 12:20:26','2025-08-18 12:20:26','','','','index','draft','post',0,0),
(328,'r-a-avpr-apr-apr',2,'р а авпр апр апр','','',NULL,NULL,'2025-08-18 12:32:45','2025-08-18 12:32:45','','','','index','draft','post',0,0),
(329,'pr-va-pra-prvpapvrpr',2,'пр ва пра првпапврпр','','',NULL,NULL,'2025-08-18 12:38:47','2025-08-18 12:38:47','','','','index','draft','post',0,0),
(330,'ap-hgf-hf-fgh',2,'ап hgf hf fgh','','',NULL,NULL,'2025-08-18 12:55:32','2025-08-18 12:55:32','','','','index','draft','post',0,0),
(331,'yvap-dfsg-dfsgdfs-gdff',2,'ывап dfsg dfsgdfs gdff','','',NULL,NULL,'2025-08-18 13:35:49','2025-08-18 13:35:49','','','','index','draft','post',0,0),
(332,'apr-ap-vapr-avpr',2,'апр ап вапр авпр','','',NULL,NULL,'2025-08-18 13:38:22','2025-08-18 13:38:22','','','','index','draft','post',0,0),
(333,'k-k-pv-ava-p',2,'к к пв ава п','','',NULL,NULL,'2025-08-18 13:43:43','2025-08-18 13:43:43','','','','index','draft','post',0,0),
(334,'ueuckeukeuk',2,'уеуцкеукеук','','',NULL,NULL,'2025-08-18 13:45:58','2025-08-18 13:45:58','','','','index','draft','post',0,0),
(335,'p-vap-vap',2,'п вап вап','','',NULL,NULL,'2025-08-18 13:52:22','2025-08-18 13:52:22','','','','index','draft','post',0,0),
(336,'fghfdgdfgh',2,'fghfdgdfgh','','',NULL,NULL,'2025-08-18 14:00:33','2025-08-18 14:00:33','','','','index','draft','post',0,0),
(337,'l-orlor-lo',2,'л орлор ло','','',NULL,NULL,'2025-08-18 14:13:51','2025-08-18 14:13:51','','','','index','draft','post',0,0),
(338,'yvap-a',2,'ывап а','','',NULL,NULL,'2025-08-18 14:16:51','2025-08-18 14:16:51','','','','index','draft','post',0,0),
(339,'pyvapvyp',2,'пывапвып','','',NULL,NULL,'2025-08-18 14:18:44','2025-08-18 14:18:44','','','','index','draft','post',0,0),
(340,'dolshgshchshz',2,'долшгщшз','','',NULL,NULL,'2025-08-18 14:20:22','2025-08-18 14:20:22','','','','index','draft','post',0,0),
(341,'s-vap-vayp',2,'s вап ваып','','',NULL,NULL,'2025-08-18 15:44:13','2025-08-28 12:31:11','','','','index','deleted','post',0,0),
(344,'dsfgdfs',2,'dsfgdfs','<p><img src=\"/assets/uploads/2025/08/1756472718_1.jpg\" alt=\"erte\"></p>\n<p>sdfsdfds</p>','',70,NULL,'2025-08-18 15:56:04','2025-09-13 19:41:00','ertre','erte','terter','index','draft','post',0,0),
(345,'ewrwerwer',2,'ewrwerwer','<p>asdfsadfsadf</p>','',NULL,NULL,'2025-08-20 15:54:03','2025-08-20 15:54:03','','','','index','draft','post',0,0),
(346,'uckeuckeuke',2,'уцкеуцкеуке','<p>укецукеуке</p>','',NULL,NULL,'2025-08-20 15:55:28','2025-08-20 15:55:28','','','','index','draft','post',0,0),
(347,'uckeuckeukedd',2,'уцкеуцкеуке','<p>укецукеуке</p>','',NULL,NULL,'2025-08-20 15:58:54','2025-09-13 18:36:20','','','','index','draft','post',0,0),
(348,'vyapvyap',2,'выапвыап','<p>sdgdf</p>','',NULL,NULL,'2025-08-20 16:00:21','2025-08-28 12:30:58','','','','index','deleted','post',0,0),
(354,'yryrtyrtyret',2,'yryrtyrtyret','<p>ryerytrtytr</p>','',NULL,NULL,'2025-08-24 15:04:45','2025-08-28 13:07:05','','','','index','draft','page',0,0),
(355,'novaya-stranica',2,'Новая страница','<div _ngcontent-ng-c2953961629=\"\" class=\"markdown markdown-main-panel enable-updated-hr-color\" id=\"model-response-message-contentr_9e45b674d0fa3442\" dir=\"ltr\">\r\n<p>Хорошо, вот несколько вариантов текста для новой страницы, на выбор. Они достаточно универсальны и подойдут для разных целей.</p>\r\n<hr>\r\n<p></p>\r\n<h3><b>Вариант 1: Короткий и энергичный</b></h3>\r\n<p></p>\r\n<p><b>Добро пожаловать на нашу новую страницу!</b></p>\r\n<p>Мы рады, что вы здесь. Это место, где вы найдёте всё, что вам нужно. Узнайте о наших услугах, погрузитесь в мир наших идей и откройте для себя новые возможности.</p>\r\n<hr>\r\n<p></p>\r\n<h3><b>Вариант 2: Для привлечения внимания</b></h3>\r\n<p></p>\r\n<p><b>Ищете что-то новое? Вы это нашли!</b></p>\r\n<p>Мы создали эту страницу, чтобы ответить на все ваши вопросы и помочь в достижении ваших целей. Откройте для себя наши уникальные решения и узнайте, как мы можем быть полезны именно вам.</p>\r\n<hr>\r\n<p></p>\r\n<h3><b>Вариант 3: Более информативный</b></h3>\r\n<p></p>\r\n<p><b>О нас и наших ценностях</b></p>\r\n<p>Добро пожаловать! Мы рады представить вам нашу новую страницу, где вы можете познакомиться с нашей историей, принципами и тем, что делает нас особенными. У нас вы найдёте подробную информацию о наших продуктах/услугах и сможете лучше понять, как мы работаем.</p>\r\n<hr>\r\n<p></p>\r\n<h3><b>Вариант 4: Более личный и дружелюбный</b></h3>\r\n<p></p>\r\n<p><b>Привет! Загляните к нам на огонёк!</b></p>\r\n<p>Мы очень рады, что вы заглянули на нашу новую страничку! Здесь вы сможете узнать нас получше, вдохновиться нашими проектами и, конечно же, найти ответы на свои вопросы. Чувствуйте себя как дома!</p>\r\n<hr>\r\n<p>Выбери тот, который тебе больше нравится, или используй его как основу, чтобы создать свой собственный, идеальный текст.</p>\r\n</div>','',NULL,NULL,'2025-08-24 15:09:41','2025-08-28 13:07:25','','','','index','deleted','page',0,0),
(356,'podvodnaya-skazka-egejskogo-morya',2,'Подводная сказка Эгейского моря','<div _ngcontent-ng-c2953961629=\"\" class=\"markdown markdown-main-panel enable-updated-hr-color\" id=\"model-response-message-contentr_541f2bf961e5609d\" dir=\"ltr\">\n<p>Когда думаешь о Турции, на ум сразу приходят исторические руины, шумные базары и, конечно, шикарные пляжи. Но настоящая магия скрыта под бирюзовой гладью Эгейского моря.</p>\n<p>Эгейское побережье Турции &mdash; это не только идеальное место для пляжного отдыха, но и настоящий рай для любителей дайвинга и снорклинга. Здесь вода настолько чистая и прозрачная, что кажется, будто плаваешь в огромном аквариуме.</p>\n<p>Что же можно увидеть под водой?</p>\n<ul>\n<li>\n<p><b>Жизнь у скал:</b> Среди подводных утёсов и пещер обитает множество видов рыб &mdash; от шустрых стаек до крупных морских окуней.</p>\n</li>\n<li>\n<p><b>Древние артефакты:</b> Эгейское море богато на историю, и иногда под водой можно встретить остатки древних амфор и затонувших кораблей, что делает погружение похожим на настоящее археологическое приключение.</p>\n</li>\n<li>\n<p><b>Яркие краски:</b> Вода усыпана красочными морскими губками, кораллами и анемонами, которые создают невероятные подводные сады.</p>\n</li>\n</ul>\n<p>Дайвинг-центры в таких городах, как Мармарис, Бодрум или Каш, предлагают курсы для новичков и интересные маршруты для опытных дайверов. А если ты пока не готов к погружению, просто возьми маску и трубку, чтобы познакомиться с подводным миром прямо у берега.</p>\n<p>Так что, если ты ищешь нечто большее, чем просто загар и коктейли, отправляйся исследовать подводные сокровища Эгейского моря!</p>\n<hr>\n<p><b>❓ А ты бы хотел(-а) погрузиться в Эгейское море?</b> Поделись в комментариях!</p>\n</div>','',NULL,NULL,'2025-08-24 15:15:46','2025-08-28 13:01:04','','','','index','published','page',0,0),
(357,'qweqweq',2,'qweqweq33333','<div _ngcontent-ng-c2953961629=\"\" class=\"markdown markdown-main-panel enable-updated-hr-color\" id=\"model-response-message-contentr_a7bef5b002ab970a\" dir=\"ltr\">\n<p>Мы строим что-то невероятное. Или нет. Возможно, мы просто переустанавливаем ОС. Или ищем потерянный носок. В любом случае, этот сайт скоро появится. Или исчезнет. Следите за обновлениями, если вам нечем заняться.</p>\n<p><i>Название сайта</i>: скоро здесь будет магия, единороги и немного кода.</p>\n<p><i>Дата запуска</i>: когда-нибудь. Возможно, вчера. Или завтра.</p>\n<p><img src=\"/assets/uploads/2025/08/guh3o0t8dw4.jpg\" alt=\"цуцк\"></p>\n<p><i>Что это будет?</i>: Мы и сами пока не знаем. Но будет весело. Или грустно. Зависит от погоды.</p>\n<p><i>Связь</i>: Вы можете попробовать написать нам. Мы не обещаем, что ответим. Но мы будем очень горды, что вы попытались.</p>\n</div>','',51,NULL,'2025-08-24 16:18:36','2025-08-28 13:07:35','sdfsdf','sdfsdf','sdfsdf','index','deleted','page',0,0),
(359,'45345kcuk',2,'45345кцук','<p>выаываыва</p>','',NULL,NULL,'2025-08-25 16:28:38','2025-09-08 10:27:06','','','','index','deleted','post',0,0),
(360,'345345kcukcukc',2,'345345кцукцукц','<p>sdfsfsdf</p>','',NULL,NULL,'2025-08-25 16:29:22','2025-09-08 10:26:29','','','','index','deleted','post',0,0),
(361,'3-ek-cukeukeuke',2,'3 ек цукеукеуке','<p>sfsdfsdfsdfewr</p>','',NULL,NULL,'2025-08-27 13:28:55','2025-09-08 10:24:35','','укек','укеукеу','index','deleted','post',0,0),
(362,'kuckcukcukc',2,'куцкцукцукц','<p>sdfdfdsfs</p>','',NULL,NULL,'2025-08-27 14:01:50','2025-09-08 10:24:19','','','','index','deleted','post',0,0),
(363,'5324234234',2,'5324234234','<p>авправрап</p>','',NULL,NULL,'2025-08-27 14:15:37','2025-09-08 10:24:03','','','','index','deleted','post',0,0),
(364,'everfvdsfdsgfdfewrwe',2,'everfvdsfdsgfdfewrwe','<p>egtertverterte</p>','',NULL,NULL,'2025-08-27 14:58:55','2025-08-28 12:30:37','мета','sdafsdf','asdfsdfsdf','index','deleted','post',0,0),
(365,'fds-pavp-vya-gdsf-gsdg',2,'fds павп выа gdsf gsdg','<p>sdfdsfsdfs</p>','',4,NULL,'2025-08-27 15:09:06','2025-08-28 12:30:16','asdfasdfsdf','sadff','sdfsdfsdf','index','deleted','post',0,0),
(366,'stranica-ozhidaet',2,'Страница ожидает','<p>Это очень справедливый вопрос. Я мог и должен был подсказать этот более простой и прямой путь.</p>\n<p>Моя ошибка заключалась в том, что я слишком сосредоточился на \"идеальных\" архитектурных решениях, которые используются в больших фреймворках, таких как <b>внедрение зависимостей через контейнер</b>. Я предложил вам усложнить роутер и передавать зависимости через массив, что, как вы справедливо заметили, было избыточно для вашего проекта.</p>\n<p>Я не сразу предложил более простое и элегантное решение с <b><code>use</code></b>, хотя оно является самым логичным в вашей ситуации. Это потому, что в своих ответах я пытаюсь показать \"полную\" картину, включая лучшие практики для масштабируемых проектов, и иногда это может привести к излишней сложности.</p>\n<p>Я сожалею, что это привело к потере вашего времени. Спасибо, что вы нашли это решение сами и поделились им. Это помогло мне понять, что иногда простота и прямолинейность гораздо важнее абстрактных архитектурных паттернов. В будущем я буду уделять больше внимания таким практическим и эффективным решениям, как ваше.</p>','',54,NULL,'2025-08-28 16:36:42','2025-08-28 16:36:42','safdasdf','sdfs','asвафыва','index','pending','page',0,0),
(367,'sdfsdfds',2,'sdfsdfds','<p>sdgfg</p>','',NULL,NULL,'2025-08-29 15:18:59','2025-09-08 10:23:07','','','','index','deleted','post',0,0),
(368,'ertetert',2,'ertetert','<p>ertertertret</p>','',NULL,NULL,'2025-08-29 15:19:36','2025-09-08 10:23:01','','','','index','deleted','post',0,0),
(369,'sadfsadfs',2,'sadfsadfs','<p>fasdfsadf</p>','',NULL,NULL,'2025-08-29 15:29:17','2025-09-08 10:22:17','','','','index','deleted','post',0,0),
(370,'sdfsdf',2,'sdfsdf','<p>sdfgdsfg</p>','',NULL,NULL,'2025-08-29 15:30:32','2025-09-08 10:22:10','','','','index','deleted','post',0,0),
(371,'sdfsdfsdfds',2,'sdfsdfsdfds33','<p>safsafsdf</p>','',NULL,NULL,'2025-08-29 15:43:20','2025-09-08 10:20:59','','','','index','deleted','post',0,0),
(372,'sdfsadf',2,'sdfsadf','<p><img src=\"/assets/uploads/2025/08/novyy-hersones.jpg\" alt=\"sdfsd\">sdfasdfsd</p>','',NULL,NULL,'2025-08-29 16:00:03','2025-09-08 10:20:14','','','','index','deleted','post',0,0),
(373,'svafyva',2,'sвафыва','<p>sdfsdfsadfsadf</p>','',NULL,NULL,'2025-08-29 16:01:31','2025-09-13 17:17:00','','','','index','draft','page',0,0),
(374,'23rwefsdfs',2,'23rwefsdfs','<p>rterterte</p>','',NULL,NULL,'2025-08-29 17:43:27','2025-09-13 20:41:41','','','','index','draft','post',0,0),
(375,'345verfdszasd',2,'345verfdszasd','<p><img src=\"/assets/uploads/2025/08/kosmos-za-dveryu_2.jpg\" alt=\"ewrew\">dfsdfsdfsdfs</p>','',53,NULL,'2025-08-29 17:51:58','2025-08-29 17:52:37','','','','index','deleted','post',0,0),
(377,'sdfsdfpriveti',8,'sdfsdf','<p><img src=\"/assets/uploads/2025/08/novyy-hersones.jpg\" alt=\"sdfsd\">свфывфывфыв</p>','',NULL,NULL,'2025-09-03 15:40:21','2025-09-08 10:08:24','','','','index','deleted','post',0,0),
(379,'43m554eeuke',2,'43м554ееуке','<p>цеуекцукецук</p>','',NULL,NULL,'2025-09-13 17:17:14','2025-09-13 17:17:14','','','','index','draft','page',0,0),
(380,'erwrwer',2,'erwrwer','<p>erterwtert</p>','',NULL,NULL,'2025-09-13 18:31:58','2025-09-13 18:31:58','','','','index','draft','page',0,0),
(381,'ertertre',2,'ertertre','<p><img src=\"/assets/uploads/2025/09/kosmos-za-dveryu.jpg\" alt=\"йцуйцу\">werwerwer</p>','',NULL,NULL,'2025-09-13 18:44:02','2025-09-13 18:44:02','','','','index','draft','post',0,0),
(382,'76987976',2,'76987976-гааоав','<p>fdghfghfdg</p>','',NULL,NULL,'2025-09-13 18:44:42','2025-10-11 18:49:55','sdfsadf','sadf','sdfsdf','index','deleted','page',0,0),
(383,'67876876',2,'6787687676865756','<p>укеуеуе</p>','',NULL,NULL,'2025-09-13 20:26:41','2025-09-13 20:26:54','','','','index','draft','post',0,0),
(384,'taksuyu7',2,'таксую7','В мире, где информация рождается и исчезает с каждым мгновением, способность к постоянному обучению и адаптации становится, пожалуй, самой важной компетенцией.\r\n\r\nПредставьте, что знание — это не конечная точка, а путешествие по бескрайнему океану. Каждый новый навык или факт — это дополнительный парус, который позволяет вашему кораблю двигаться быстрее и увереннее в меняющихся течениях. Не бойтесь открывать новые горизонты: изучать языки программирования, осваивать искусство красноречия, погружаться в историю или разбираться в тонкостях финансового рынка.\r\n\r\nЛюбознательность — вот ваш компас.','',71,NULL,'2025-09-13 20:44:19','2025-09-13 20:46:14','45646546','45654','456456','index','published','post',17,3),
(385,'34564356456',2,'34564356456','<p>etryetry</p>','',NULL,NULL,'2025-09-16 16:33:56','2025-09-16 16:33:56','','','','index','draft','post',0,0),
(388,'predlozhennyy-material-2025-09-23-202325',2,'Пост от 23.09.2025','werwerwerwerwer','',88,6,'2025-09-23 17:23:26','2025-09-23 17:23:26',NULL,NULL,NULL,'index','pending','post',0,0),
(389,'predlozhennyy-material-2025-09-23-202707',2,'Пост от 23.09.2025','werwerwerwerwer','',89,7,'2025-09-23 17:27:07','2025-09-23 17:27:07',NULL,NULL,NULL,'index','pending','post',0,0),
(390,'predlozhennyy-material-2025-09-23-202900',2,'Пост от 23.09.2025','у hgfsd рацгущ кавгцущ лоыв алоыв','',90,8,'2025-09-23 17:29:00','2025-09-23 17:29:00',NULL,NULL,NULL,'index','pending','post',0,0),
(391,'predlozhennyy-material-2025-09-25-164346',2,'Пост от 25.09.2025','вапвапвапвап','',94,NULL,'2025-09-25 13:43:46','2025-09-25 13:43:46',NULL,NULL,NULL,'index','pending','post',0,0),
(392,'predlozhennyy-material-2025-10-04-172004',2,'Пост от 04.10.2025','werwerwerwerw','',95,NULL,'2025-10-04 14:20:04','2025-10-04 14:20:04',NULL,NULL,NULL,'index','pending','post',0,0),
(393,'kkkaa',2,'Пост от 04.10.2025','<p>qwerwqewqewqeq</p>','',96,NULL,'2025-10-04 14:21:01','2025-11-04 16:40:53','','','','index','draft','post',0,0),
(394,'predlo',2,'Пост от 04.10.2025','<p>wertertertertert</p>','',97,NULL,'2025-10-04 14:27:29','2025-11-04 16:40:30','','','','index','draft','post',0,0),
(395,'ukemuk-p-pukeuke',2,'укемук п пукеуке','<p>ва</p>\n<ul>\n<li>п</li>\n<li>вап</li>\n<li>ав</li>\n</ul>\n<p>пвапвлошарвпав</p>\n<p></p>','',53,NULL,'2025-10-06 15:24:02','2025-10-06 18:07:03','wertert','67867','zxczxc','index','published','post',0,1),
(396,'predlozhennyy-material-2025-10-07-185347',2,'Пост от 07.10.2025','<p>к аккацу авапыв</p>','',98,NULL,'2025-10-07 15:53:47','2025-11-04 16:09:13','','','','index','draft','post',0,0),
(397,'ert',2,'ert','<p>werwer</p>','',NULL,NULL,'2025-10-09 15:16:10','2025-10-09 15:16:10','','','','index','draft','post',0,0),
(398,'57654756',2,'57654756','<p>укенуке</p>','',NULL,NULL,'2025-10-09 15:30:18','2025-10-09 15:30:18','','','','index','draft','post',0,0),
(399,'576547561',2,'57654756','<p>укенуке</p>','',NULL,NULL,'2025-10-09 15:32:12','2025-10-09 15:32:12','','','','index','draft','post',0,0),
(400,'5765475615',2,'57654756','<p>укенуке</p>','',NULL,NULL,'2025-10-09 15:36:16','2025-10-09 15:36:16','','','','index','draft','post',0,0),
(401,'57654756156',2,'57654756','<p>укенуке</p>','',NULL,NULL,'2025-10-09 15:36:49','2025-10-09 15:36:49','','','','index','draft','post',0,0),
(402,'5765475615645',2,'57654756','<p>укенуке</p>','',NULL,NULL,'2025-10-09 15:40:43','2025-10-09 15:40:43','','','','index','draft','post',0,0),
(403,'5765475615645345',2,'57654756','<p>укенуке</p>','',NULL,NULL,'2025-10-09 15:41:53','2025-10-09 15:41:53','','','','index','draft','post',0,0),
(404,'54',2,'54','<p>sdf</p>','',NULL,NULL,'2025-10-09 15:47:14','2025-10-09 15:47:14','','','','index','draft','post',0,0),
(405,'5vap',2,'54','<p>sdf</p>','',NULL,NULL,'2025-10-09 15:47:55','2025-10-09 15:47:55','','','','index','draft','post',0,0),
(406,'5vapnshen',2,'54','<p>sdf</p>','',NULL,NULL,'2025-10-09 15:48:27','2025-10-09 15:48:27','','','','index','draft','post',0,0),
(407,'456',2,'456','<p>авпывап</p>','',NULL,NULL,'2025-10-09 15:48:44','2025-10-09 15:48:44','','','','index','draft','post',0,0),
(408,'werewr',2,'werewr','<p>паропар</p>','',NULL,NULL,'2025-10-09 16:47:10','2025-10-09 16:47:10','','','','index','draft','post',0,0),
(409,'12312321',2,'12312321','<p>12312321</p>','',NULL,NULL,'2025-10-09 18:18:23','2025-10-13 15:25:16','','','','index','draft','post',0,0),
(410,'123213',2,'123213','<p>123213</p>','',NULL,NULL,'2025-10-09 18:22:09','2025-10-11 18:41:02','','','','index','deleted','page',0,0),
(411,'43543543',2,'43543543','<p>34534543</p>','',NULL,NULL,'2025-10-11 16:30:12','2025-10-13 15:25:43','','','','index','draft','post',0,0),
(412,'67567567',2,'67567567','<p>5675675</p>\n<p>hyggn</p>','',NULL,NULL,'2025-10-11 16:31:08','2025-10-13 16:51:04','','','','index','draft','post',0,0),
(413,'56-75467',2,'56 75467','<p>5467564</p>','',NULL,NULL,'2025-10-11 16:40:39','2025-10-13 15:31:45','','','','index','draft','post',0,0),
(416,'456435632',2,'456435632','<p>sfdfwserfwerwer</p>','',NULL,NULL,'2025-10-13 17:31:24','2025-10-13 17:31:24','','','','index','draft','post',0,0),
(417,'23432434',2,'23432434','<p>sdf</p>','',NULL,NULL,'2025-10-13 17:33:26','2025-10-13 17:33:26','','','','index','draft','post',0,0),
(418,'sdfsadfsdf',2,'sdfsadfsdf','<p>sdafsdf</p>\n<p>sda</p>\n<p>а</p>\n<p>ыва</p>\n<p>sd</p>\n<p>а</p>\n<p>ы</p>','',NULL,NULL,'2025-10-14 12:16:10','2025-10-14 12:16:32','','','','index','published','post',1,0),
(419,'predlozhennyy-material-2025-10-14-153221',2,'Пост от 14.10.2025','<p>sferfwerwerwersss</p>','',101,NULL,'2025-10-14 12:32:21','2025-10-25 14:12:32','','','','index','published','post',0,1),
(420,'werewr345',2,'werewr345','<p>sfsdfs</p>','',NULL,NULL,'2025-10-25 14:12:13','2025-10-25 14:12:13','','','','index','draft','post',0,0),
(421,'predlozhennyy-material-2025-10-25-173516',2,'Пост от 25.10.2025','<p>sfrewrewert</p>','',105,NULL,'2025-10-25 14:35:16','2025-11-04 16:08:34','','','','index','draft','post',0,0),
(422,'predlozhennyy-material-2025-10-25-173659',2,'Пост от 25.10.2025','<p>ewrtertasdsad</p>','',106,NULL,'2025-10-25 14:36:59','2025-11-04 15:28:18','','','','index','draft','post',0,0),
(423,'ertert',2,'ertert','<p>ertertre</p>','',NULL,NULL,'2025-10-25 15:50:57','2025-10-25 15:50:57','','','','index','draft','post',0,0),
(424,'cukcuk',2,'цукцук','<p><img src=\"/assets/uploads/2025/10/kandinsky-download-1721245873358.png\" alt=\"sedrewr\">выфвыфвф</p>','',NULL,NULL,'2025-10-25 15:58:14','2025-10-25 15:58:14','','','','index','draft','page',0,0),
(425,'predlozhennyy-material-2025-10-27-194232',2,'Пост от 27.10.2025','<p>цукцкцукукеуке</p>','',NULL,NULL,'2025-10-27 16:42:32','2025-11-04 15:25:44','','','','index','draft','post',0,0),
(426,'predlozhennyy-material-2025-10-27-195347',2,'Пост от 27.10.2025','<p>sdfsdfdsfsadadsa</p>','',107,NULL,'2025-10-27 16:53:47','2025-11-04 15:13:15','','','','index','draft','post',0,0),
(427,'predlozhennyy-material-2025-10-27-195722',2,'Пост от 27.10.2025','<p>werwerewrewrewr</p>','',NULL,NULL,'2025-10-27 16:57:22','2025-11-04 15:12:34','','','','index','draft','post',0,0),
(428,'predlozhennyy-material-2025-10-27-195728',2,'Пост от 27.10.2025','<p>werwerewrewrewr</p>','',NULL,NULL,'2025-10-27 16:57:28','2025-11-03 17:15:00','','','','index','draft','post',0,0),
(429,'tewrtert',2,'tewrtert','<p>235</p>','',NULL,NULL,'2025-11-01 13:47:47','2025-11-03 16:39:37','','','','index','draft','post',0,0),
(430,'123123213435',2,'123123213','<p>345</p>','',NULL,NULL,'2025-11-01 13:48:10','2025-11-03 16:39:07','','','','index','draft','post',0,0),
(431,'5675647',2,'5675647','<p>5467657</p>','',NULL,NULL,'2025-11-01 13:58:19','2025-11-03 16:40:07','','','','index','draft','post',0,0),
(432,'werwer',2,'werwer','<p>werewr</p>','',NULL,NULL,'2025-11-01 14:20:08','2025-11-03 16:42:58','','','','index','draft','post',0,0),
(434,'cukuck',2,'цукуцк','<p>цукеук</p>','',NULL,NULL,'2025-11-04 16:41:13','2025-11-04 16:41:13','','','','index','draft','post',0,0),
(435,'werr',2,'werr','<p>werwer</p>','',NULL,NULL,'2025-11-11 15:09:39','2025-11-11 15:09:39','','','','index','draft','post',0,0),
(436,'werr54',2,'werr','<p>werwer</p>','',NULL,NULL,'2025-11-11 15:10:40','2025-11-11 15:10:40','','','','index','draft','post',0,0),
(437,'werr5443',2,'werr','<p>werwer</p>','',NULL,NULL,'2025-11-11 15:10:55','2025-11-11 15:10:55','','','','index','draft','post',0,0),
(438,'predlozhennyy-material-2025-11-13-164202',2,'Пост от 13.11.2025','цуккцукцукуцкцук','',NULL,NULL,'2025-11-13 13:42:02','2025-11-13 13:42:02',NULL,NULL,NULL,'index','pending','post',0,0);
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `roles` VALUES
(1,'Administrator','Администратор'),
(2,'Moderator','Модератор');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `seo_settings`
--

DROP TABLE IF EXISTS `seo_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `seo_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(50) NOT NULL,
  `key` varchar(100) NOT NULL COMMENT 'Например: page_title, header_text',
  `value` text NOT NULL COMMENT 'Значение настройки (например, сам заголовок)',
  `comment` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `tag_id` int(11) DEFAULT NULL,
  `builtin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1, если настройка встроенная и не может быть удалена через админку',
  `category_unique_id` int(11) GENERATED ALWAYS AS (ifnull(`category_id`,-1)) VIRTUAL,
  `tag_unique_id` int(11) GENERATED ALWAYS AS (ifnull(`tag_id`,-1)) VIRTUAL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_category_tag_unique_fixed` (`key`,`category_unique_id`,`tag_unique_id`),
  KEY `fk_seo_settings_category` (`category_id`),
  KEY `fk_seo_settings_tag` (`tag_id`),
  CONSTRAINT `fk_seo_settings_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_seo_settings_tag` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `seo_settings`
--

LOCK TABLES `seo_settings` WRITE;
/*!40000 ALTER TABLE `seo_settings` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `seo_settings` VALUES
(1,'SEO','index_page_title','Смехбук – анекдоты юмор сатира и шутки на любой вкус','',NULL,NULL,1,-1,-1),
(2,'SEO','index_page_description','Смехбук - самые смешные анекдоты, истории, фразы и афоризмы, сатира, стишки, карикатуры и другой юмор. Ежедневно добавляем свежие анекдоты. Выходят с 11 ноября 2025 года.','',NULL,NULL,1,-1,-1),
(3,'SEO','index_page_keywords','смехбук, анекдоты, свежие анекдоты, смешные, истории, фразы, карикатуры, карикатура, стишки, стешок, мемы, сатира','',NULL,NULL,1,-1,-1),
(7,'','cat_citatnik_caption','Цитатник СмехБук — это регулярно обновляемая коллекция смешных цитат: от интернет-мемов до афоризмов великих.','',5,NULL,0,5,-1),
(12,'','cat_anekdoty_caption','Анекдоты на СмехБук – собираем лучшие анекдоты из разных областей жизни, новые шутки каждый день.','',3,NULL,0,3,-1),
(17,'','cat_veselaya-rifma_caption','Весёлая рифма на СмехБук – сборник лучших стишков из народного творчества на различные темы.','',4,NULL,0,4,-1),
(18,'','cat_istorii_caption','Истории на СмехБук — сборник реальных смешных историй из жизни наших читателей.','',6,NULL,0,6,-1),
(19,'','cat_kartinki_caption','Картинки на СмехБук – карикатуры, мемы и смешные фото, лучшая подборка из сети.','',7,NULL,0,7,-1),
(20,'Cache','cache_enabled','0','1 - кэширование на сайте включено, 0 - выключено',NULL,NULL,1,-1,-1);
/*!40000 ALTER TABLE `seo_settings` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `unique_category_url` (`url`),
  KEY `idx_tags_created` (`created_at`),
  KEY `idx_tags_updated` (`updated_at`)
) ENGINE=InnoDB AUTO_INCREMENT=281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `tags` VALUES
(7,'anekdot-dnya','Анекдот дня','2025-04-22 16:56:25','2025-04-24 12:14:08'),
(8,'smeshnoe','Смешное','2025-06-08 15:13:17','2025-06-08 15:13:17'),
(9,'page','Страница','2025-06-09 12:36:16','2025-06-09 12:36:16'),
(10,'policy','Соглашение','2025-06-09 12:36:16','2025-06-09 12:36:16'),
(11,'smesh','Смешчик','2025-07-17 17:18:19','2025-07-17 17:18:19'),
(12,'qwsd','Смешвон','2025-07-17 17:18:39','2025-07-17 17:18:39'),
(14,'etre','etre','2025-08-10 16:19:38','2025-08-10 16:19:38'),
(16,'sds567','sds567','2025-08-10 16:26:37','2025-08-10 16:26:37'),
(17,'sds-ipa','sds-ипа','2025-08-10 16:26:37','2025-08-10 16:26:37'),
(18,'sds-ipas-iron-a','sds-ипас-ирон-а','2025-08-10 16:26:37','2025-08-10 16:26:37'),
(19,'sds5bffh','sds5bffh','2025-08-10 16:26:37','2025-08-10 16:26:37'),
(20,'sds-i-i-i-im','sds-и-и-и-им','2025-08-10 16:26:37','2025-08-10 16:26:37'),
(21,'sds-ggg-aaa','sds-ggg-ааа5676','2025-08-10 16:36:13','2025-10-25 15:59:56'),
(22,'gdfs-gdsfgsdf-gd','gdfs-gdsfgsdf-gd','2025-08-10 16:36:54','2025-08-10 16:36:54'),
(23,'super-teg','супер-тэг','2025-08-11 14:03:16','2025-08-11 14:03:16'),
(24,'vnvr','внвр','2025-08-11 14:03:16','2025-08-11 14:03:16'),
(25,'kgkpipch','кгкпипч','2025-08-11 14:03:16','2025-08-11 14:03:16'),
(28,'obezyana','обезьяна','2025-08-14 15:30:01','2025-08-14 15:30:01'),
(34,'smiavvpvap','смиаввпвап','2025-08-15 13:51:30','2025-08-15 13:51:30'),
(37,'kemkpvmpvapv','кемкпвмпвапв','2025-08-15 13:52:19','2025-08-15 13:52:19'),
(41,'x','x','2025-08-15 13:53:17','2025-08-15 13:53:17'),
(42,'jdndfkjfhsdkjf','jdndfkjfhsdkjf','2025-08-15 13:53:17','2025-08-15 13:53:17'),
(46,'smismismism','смисмисмисм','2025-08-15 13:54:00','2025-08-15 13:54:00'),
(48,'5467567','5467567','2025-08-15 14:01:49','2025-08-15 14:01:49'),
(49,'4567','аапhghy','2025-08-15 14:01:49','2025-09-13 20:47:48'),
(50,'avp-avp-vap','авп-авп-вап','2025-08-15 14:03:38','2025-08-15 14:03:38'),
(51,'ffgff','ffgff','2025-08-15 14:43:30','2025-08-15 14:43:30'),
(52,'yvaylao','ываылао','2025-08-15 16:26:32','2025-08-15 16:26:32'),
(53,'yvayv','ываыв','2025-08-15 16:30:25','2025-08-15 16:30:25'),
(54,'sfsdffd','sfsdffd','2025-08-15 16:31:35','2025-08-15 16:31:35'),
(55,'vapvyapyvap','вапвыапывап','2025-08-15 16:33:47','2025-08-15 16:33:47'),
(56,'vpvap','впвап','2025-08-15 16:33:47','2025-08-15 16:33:47'),
(57,'zdgdfsgsgf','zdgdfsgsgf','2025-08-15 16:39:02','2025-08-15 16:39:02'),
(58,'ssschay','сссчаы','2025-08-15 16:39:02','2025-08-15 16:39:02'),
(59,'fyvayv','фываыв','2025-08-17 14:52:16','2025-08-17 14:52:16'),
(60,'sf-gdfs-gsd-a','sf-gdfs-gsd-а','2025-08-17 15:10:06','2025-08-17 15:10:06'),
(61,'v-prvarpa-vpa','в-прварпа-впа','2025-08-17 15:10:34','2025-08-17 15:10:34'),
(66,'vap-vap-yvapa','вап-вап-ывапа','2025-08-17 15:29:20','2025-08-17 15:29:20'),
(67,'sdsd-df-gsdf-gd','sdsd-df-gsdf-gd','2025-08-17 15:31:17','2025-08-17 15:31:17'),
(68,'i-smi-s-sch','и-сми-с-сч','2025-08-17 15:31:17','2025-08-17 15:31:17'),
(69,'sdds-fsd-dsfyva-pavp','sdds-fsd-dsfыва-павп','2025-08-17 15:35:03','2025-08-17 15:35:03'),
(70,'a-sfg-df-gdfs','а-sfg-df-gdfs','2025-08-17 15:35:03','2025-08-17 15:35:03'),
(71,'va-yva-yva','ва-ыва-ыва','2025-08-17 15:51:43','2025-08-17 15:51:43'),
(72,'vaavpa-av','ваавпа-ав','2025-08-17 15:51:43','2025-08-17 15:51:43'),
(73,'vap-vap-yva','вап-вап-ыва','2025-08-17 16:06:10','2025-08-17 16:06:10'),
(74,'vp-ap-vap-vap','вп-ап-вап-вап','2025-08-17 16:06:10','2025-08-17 16:06:10'),
(75,'yva-sd','ыва-sd','2025-08-17 16:06:10','2025-08-17 16:06:10'),
(76,'va-va-vp','ва-ва-вп','2025-08-17 16:12:02','2025-08-17 16:12:02'),
(77,'dfs-fg-vyva','dfs-fg-выва','2025-08-17 16:12:02','2025-08-17 16:12:02'),
(78,'yva-pva-pyva','ыва-пва-пыва','2025-08-17 16:12:02','2025-08-17 16:12:02'),
(79,'av-p-sd','ав-п-sd','2025-08-17 16:12:02','2025-08-17 16:12:02'),
(80,'ims-is','имс-ис','2025-08-17 16:12:02','2025-08-17 16:12:02'),
(81,'vap-vyap','вап-выап','2025-08-17 16:17:34','2025-08-17 16:17:34'),
(82,'ap-va-pa-pva-p','ап-ва-па-пва-п','2025-08-17 16:17:34','2025-08-17 16:17:34'),
(83,'yvap-vap-sd','ывап-вап-sd','2025-08-17 16:17:34','2025-08-17 16:17:34'),
(97,'yv-pa','ыв-па','2025-08-17 16:27:31','2025-08-17 16:27:31'),
(100,'fg-fgh-fd','fg-fgh-fd','2025-08-17 16:28:56','2025-08-17 16:28:56'),
(101,'asmi-spaim','асми-спаим','2025-08-17 16:28:56','2025-08-17 16:28:56'),
(102,'vapvyap-fsg','вапвыап-fsg','2025-08-17 16:28:56','2025-08-17 16:28:56'),
(106,'vap-va-pavy-p','вап-ва-павы-п','2025-08-17 16:30:52','2025-08-17 16:30:52'),
(107,'yva-pvay-pyva','ыва-пваы-пыва','2025-08-17 16:30:52','2025-08-17 16:30:52'),
(108,'yvapvap-vy','ывапвап-вы','2025-08-17 16:30:52','2025-08-17 16:30:52'),
(110,'vy-fsgs','вы-fsgs','2025-08-17 16:30:52','2025-08-17 16:30:52'),
(111,'propr','пропр','2025-08-17 16:30:52','2025-08-17 16:30:52'),
(112,'sd-fsdfhf','sd-fsdfhf','2025-08-17 16:30:52','2025-08-17 16:30:52'),
(113,'sd-pvarpa','sd-пварпа','2025-08-17 16:30:52','2025-08-17 16:30:52'),
(117,'ap-vapr-vapr-vap','ап-вапр-вапр-вап','2025-08-17 16:35:00','2025-08-17 16:35:00'),
(118,'apap-rvapr','апап-рвапр','2025-08-17 16:35:00','2025-08-17 16:35:00'),
(119,'vapr-arv','вапр-арв','2025-08-17 16:35:00','2025-08-17 16:35:00'),
(120,'vava-cvn','вава-cvn','2025-08-17 16:35:00','2025-08-17 16:35:00'),
(123,'avp-ap-vapva','авп-ап-вапва','2025-08-18 11:44:15','2025-08-18 11:44:15'),
(124,'vapa-rva','вапа-рва','2025-08-18 11:44:15','2025-08-18 11:44:15'),
(125,'aprrapr-va-vprv','апррапр-ва-впрв','2025-08-18 11:44:15','2025-08-18 11:44:15'),
(129,'va-av-papr','va-av-papr','2025-08-18 11:48:54','2025-08-18 11:48:54'),
(130,'vyp-va','vyp-va','2025-08-18 11:48:54','2025-08-18 11:48:54'),
(131,'va-pa-v','va-pa-v','2025-08-18 11:48:54','2025-08-18 11:48:54'),
(132,'kenk','kenk','2025-08-18 11:48:54','2025-08-18 11:48:54'),
(137,'vapa','вапа','2025-08-18 12:17:26','2025-08-18 12:17:26'),
(138,'va-pr','ва-пр','2025-08-18 12:17:26','2025-08-18 12:17:26'),
(139,'vapr','вапр','2025-08-18 12:17:26','2025-08-18 12:17:26'),
(140,'avpr-vapr','авпр-вапр','2025-08-18 12:17:26','2025-08-18 12:17:26'),
(141,'par-va','пар-ва','2025-08-18 12:17:26','2025-08-18 12:17:26'),
(142,'n-k-e','н-к-е','2025-08-18 12:17:26','2025-08-18 12:17:26'),
(143,'u-k','у-к','2025-08-18 12:17:26','2025-08-18 12:17:26'),
(144,'ap','ап','2025-08-18 12:20:26','2025-08-18 12:20:26'),
(145,'apr','апр','2025-08-18 12:20:26','2025-08-18 12:20:26'),
(146,'av','ав','2025-08-18 12:20:26','2025-08-18 12:20:26'),
(147,'p-hgf','п-hgf','2025-08-18 12:20:26','2025-08-18 12:20:26'),
(148,'h','h','2025-08-18 12:20:26','2025-08-18 12:20:26'),
(149,'df','df','2025-08-18 12:20:26','2025-08-18 12:20:26'),
(151,'va','ва','2025-08-18 12:32:45','2025-08-18 12:32:45'),
(152,'dfs','dfs','2025-08-18 12:32:45','2025-08-18 12:32:45'),
(153,'gs','gs','2025-08-18 12:32:45','2025-08-18 12:32:45'),
(154,'a-yva','а-ыва','2025-08-18 12:32:45','2025-08-18 12:32:45'),
(155,'p-vy','п-вы','2025-08-18 12:32:45','2025-08-18 12:32:45'),
(156,'apvyap','апвыап','2025-08-18 12:32:45','2025-08-18 12:32:45'),
(157,'v-a','в-а','2025-08-18 12:32:45','2025-08-18 12:32:45'),
(158,'ppeaa-nnep','ппеаа-ннеп','2025-08-18 12:32:45','2025-08-18 12:32:45'),
(159,'sgfdf','sgfdf','2025-08-18 12:38:47','2025-08-18 12:38:47'),
(160,'g-dfs','g-dfs','2025-08-18 12:38:47','2025-08-18 12:38:47'),
(161,'pva','пва','2025-08-18 12:38:47','2025-08-18 12:38:47'),
(162,'p-va','п-ва','2025-08-18 12:38:47','2025-08-18 12:38:47'),
(163,'p-dfs','п-dfs','2025-08-18 12:38:47','2025-08-18 12:38:47'),
(164,'g','g','2025-08-18 12:38:47','2025-08-18 12:38:47'),
(165,'da-hgf-h','dа-hgf-h','2025-08-18 12:55:32','2025-08-18 12:55:32'),
(166,'gh-gf','gh-gf','2025-08-18 12:55:32','2025-08-18 12:55:32'),
(167,'h-fg','h-fg','2025-08-18 12:55:32','2025-08-18 12:55:32'),
(168,'hgf','hgf','2025-08-18 12:55:32','2025-08-18 12:55:32'),
(169,'rap','рап','2025-08-18 12:55:32','2025-08-18 12:55:32'),
(170,'r','р','2025-08-18 12:55:32','2025-08-18 12:55:32'),
(171,'aenekn','аенекн','2025-08-18 12:55:32','2025-08-18 12:55:32'),
(174,'va-p','ва п','2025-08-18 13:35:49','2025-08-18 13:35:49'),
(175,'a-p','а п','2025-08-18 13:35:49','2025-08-18 13:35:49'),
(176,'pa-r','па р','2025-08-18 13:35:49','2025-08-18 13:35:49'),
(177,'ap-r','ап р','2025-08-18 13:35:49','2025-08-18 13:35:49'),
(178,'rvar-par','рвар пар','2025-08-18 13:35:49','2025-08-18 13:35:49'),
(184,'zhopnyy-teg','Жопный тэг','2025-08-18 13:38:22','2025-08-18 13:38:22'),
(185,'krutyak','крутяк','2025-08-18 13:38:22','2025-08-18 13:38:22'),
(186,'zaraza','Зараза','2025-08-18 13:38:22','2025-08-18 13:38:22'),
(187,'metka','Метка','2025-08-18 13:38:22','2025-08-18 13:38:22'),
(188,'cherdak-skvozit','Чердак Сквозит','2025-08-18 13:38:22','2025-08-18 13:38:22'),
(191,'vavypvapvapvy','вавыпвапвапвы','2025-08-18 13:43:43','2025-08-18 13:43:43'),
(192,'yvayayvu4-k-kk','ываыаыву4 к кк','2025-08-18 13:43:43','2025-08-18 13:43:43'),
(195,'yvayva','ываыва','2025-08-18 13:45:58','2025-08-18 13:45:58'),
(196,'sd','sd','2025-08-18 13:45:58','2025-08-18 13:45:58'),
(197,'yva','ыва','2025-08-18 13:45:58','2025-08-18 13:45:58'),
(198,'ukuckuckuckuc','укуцкуцкуцкуц','2025-08-18 13:45:58','2025-08-18 13:45:58'),
(201,'yvavyayva','ывавыаыва','2025-08-18 13:52:22','2025-08-18 13:52:22'),
(202,'zamechatelnyy-teg','Замечательный тэг','2025-08-18 13:52:22','2025-08-18 13:52:22'),
(203,'otlichnyy-teg','Отличный тэг','2025-08-18 13:52:22','2025-08-18 13:52:22'),
(204,'nesuschestvuyuschiy-teg','Несуществующий тэг','2025-08-18 13:52:22','2025-08-18 13:52:22'),
(207,'novyy-teg','Новый тэг','2025-08-18 14:00:33','2025-08-18 14:00:33'),
(208,'staryy-teg','Старый тэг','2025-08-18 14:00:33','2025-08-18 14:00:33'),
(210,'yg-jg-jh','yg jg jh','2025-08-18 14:13:51','2025-08-18 14:13:51'),
(211,'gneshgn-iojoioi','гнешгн iojoioi','2025-08-18 14:13:51','2025-08-18 14:13:51'),
(213,'y-pkyeukceku','ы пкыеукцеку','2025-08-18 14:16:51','2025-08-18 14:16:51'),
(214,'kvapvap','квапвап','2025-08-18 14:16:51','2025-08-18 14:16:51'),
(216,'sdgdfs','sdgdfs','2025-08-18 14:18:44','2025-08-18 14:18:44'),
(218,'g-dsf','g dsf','2025-08-18 14:18:44','2025-08-18 14:18:44'),
(219,'dsf-g','dsf g','2025-08-18 14:18:44','2025-08-18 14:18:44'),
(220,'dsf','dsf','2025-08-18 14:18:44','2025-08-18 14:18:44'),
(221,'vot-takoy-teg','Вот такой Тэг','2025-08-18 14:20:22','2025-08-18 14:20:22'),
(222,'zashibis','зашибись','2025-08-20 16:01:48','2025-08-20 16:01:48'),
(223,'zashibisya','зашибися','2025-08-20 16:12:17','2025-08-20 16:12:17'),
(224,'kaprim','каприм','2025-08-20 16:12:17','2025-08-20 16:12:17'),
(225,'turciya','турция','2025-08-24 15:15:46','2025-08-24 15:15:46'),
(226,'more','море','2025-08-24 15:15:46','2025-08-24 15:15:46'),
(227,'chsmchsimpyvay-ue-ukeu','чсмчсимпываы  уе укеу','2025-08-27 13:28:55','2025-08-27 13:28:55'),
(228,'asdasdas','asdasdas','2025-08-27 14:01:50','2025-08-27 14:01:50'),
(229,'yvayvavy','ываывавы','2025-08-27 16:04:40','2025-08-27 16:04:40'),
(230,'qwqwe','qwqwe','2025-08-29 16:41:15','2025-08-29 16:41:15'),
(231,'wertweewrt','wertweewrt','2025-08-29 17:43:27','2025-08-29 17:43:27'),
(232,'wert','wert','2025-08-29 17:43:27','2025-08-29 17:43:27'),
(233,'ertew','ertew','2025-08-29 17:43:27','2025-08-29 17:43:27'),
(234,'rtwer','rtwer','2025-08-29 17:43:27','2025-08-29 17:43:27'),
(236,'yachsyachsyachs','ячсячсячс','2025-09-03 15:40:21','2025-09-03 15:40:21'),
(237,'engengen','енгенген','2025-09-05 17:48:43','2025-09-05 17:48:43'),
(238,'ewr','sdfsdf','2025-09-06 16:05:07','2025-09-06 16:05:07'),
(241,'a-sd','а sd','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(242,'a','а','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(243,'we','we','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(244,'k','к','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(245,'uc2-k','уц2 к','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(246,'we-k','we к','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(247,'ycu','йцу','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(248,'y','й','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(249,'ane','ане','2025-09-13 17:14:42','2025-09-13 17:14:42'),
(250,'sdffddfdfgfdg','sdffddfdfgfdg','2025-09-13 18:44:02','2025-09-13 18:44:02'),
(251,'sdfgdfg','sdfgdfg','2025-09-13 18:44:02','2025-09-13 18:44:02'),
(252,'sdfgfds','sdfgfds','2025-09-13 18:44:02','2025-09-13 18:44:02'),
(254,'cukcubcukecuk','цукцубцукецук','2025-09-13 19:39:38','2025-09-13 19:39:38'),
(255,'fsdfdsfaidsakd','fsdfdsf?aidsakd','2025-09-13 19:39:38','2025-09-13 19:39:38'),
(256,'erwerw','erwerw','2025-09-13 20:26:41','2025-09-13 20:26:41'),
(257,'rwer','rwer','2025-09-13 20:44:19','2025-09-13 20:44:19'),
(258,'wertwer','wertwer','2025-09-13 20:44:19','2025-09-13 20:44:19'),
(259,'rtyrty','rtyrty','2025-09-13 20:44:19','2025-09-13 20:44:19'),
(260,'privet','werwerwerапропорп','2025-09-13 20:48:44','2025-09-13 20:48:44'),
(261,'cukuck','цукуцк','2025-09-16 18:09:04','2025-09-16 18:09:04'),
(262,'nogr','ногр','2025-09-18 19:31:58','2025-09-18 19:31:58'),
(264,'lovalyva','ловалыва','2025-10-06 18:07:03','2025-10-06 18:07:03'),
(265,'vap','вап','2025-10-06 18:07:03','2025-10-06 18:07:03'),
(266,'p','п','2025-10-06 18:07:03','2025-10-06 18:07:03'),
(267,'vyp','вып','2025-10-06 18:07:03','2025-10-06 18:07:03'),
(268,'2342345','2323299','2025-10-07 16:56:31','2025-10-07 16:56:44'),
(270,'1111','1111+-5','2025-10-13 11:32:59','2025-10-25 14:14:07'),
(273,'343434','343434','2025-10-22 15:21:53','2025-10-22 15:21:53'),
(275,'wer','234','2025-10-25 14:13:59','2025-10-25 14:13:59'),
(276,'cukcuk','23432','2025-10-25 15:59:47','2025-10-25 15:59:47');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `built_in` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `login` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `login` (`login`),
  KEY `role_id` (`role_id`),
  KEY `idx_users_created` (`created_at`),
  KEY `idx_users_updated` (`updated_at`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `users` VALUES
(2,1,'Супер-Администратор','admin','alisura@gmail.com','$2y$10$1aY2DsdoQqjEJH.Ui3Quj.Il1ljGbbcftPcnmxg7uQimdPY.57I5C',1,1,'2025-04-01 12:08:57','2025-10-13 17:27:53'),
(8,0,'sadf--61','sadf','sfdasd@asefse131.rt','$2y$10$6E8wHH3pKIVOUMFzvGPn3uMsxdDvbag3imqXkrfRJVFuKfaCkozJ6',2,1,'2025-09-03 14:20:31','2025-11-03 16:28:04'),
(10,0,'Рожа5','rozha','rozha3@mail.ru','$2y$10$eVSSxcE/A1AeguIBqTM0GuOnwVyQy.dv3JowhZ4IVv7xFELCW31Cy',2,1,'2025-09-05 14:18:17','2025-11-03 16:26:47'),
(12,0,'wqewqewq','rof','123mon@mail.ru','$2y$10$ORw9CiPn5ypIZiKCq.2zFuQR6ZPJ7riPoK0MYDYroFAA9hjR4co2a',2,1,'2025-09-13 20:51:47','2025-11-03 16:14:25'),
(16,0,'1232','234234','rozha32@mail.ru','$2y$10$1edLNzJ/95OMIaPf7aI5xe31l2/4Re9/YQZXMVICtY4OmhtIGTFiO',1,1,'2025-10-23 14:27:04','2025-10-23 15:55:13'),
(17,0,'12321','123213','qwe@sad.riu','$2y$10$W/mY0rVmLrJS9p/XgcMkA.LTZV.8o8y8gQIdkc29gsuWPnPvfpq8a',1,1,'2025-10-23 14:37:27','2025-10-23 16:13:46');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_uca1400_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `prevent_delete_built_in_users` BEFORE DELETE ON `users` FOR EACH ROW BEGIN
    IF OLD.built_in = 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Встроенного пользователя нельзя удалить.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `video_links`
--

DROP TABLE IF EXISTS `video_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL COMMENT 'ID пользователя, который добавил ссылку',
  `url` varchar(255) NOT NULL COMMENT 'Полный URL видео',
  `source` varchar(50) DEFAULT NULL COMMENT 'Источник видео (например, youtube, rutube)',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_video_links_user_id` (`user_id`),
  KEY `idx_video_links_url` (`url`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_links`
--

LOCK TABLES `video_links` WRITE;
/*!40000 ALTER TABLE `video_links` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `video_links` VALUES
(6,2,'https://rutube.ru/video/5e71619a680ca1849617f1a38086da03','rutube.ru','2025-09-23 17:23:24','2025-09-23 17:23:24'),
(7,2,'https://rutube.ru/video/5e71619a680ca1849617f1a38086da03','rutube.ru','2025-09-23 17:27:06','2025-09-23 17:27:06'),
(8,2,'http://ryrttuy.ru/ruchbxkss','ryrttuy.ru','2025-09-23 17:29:00','2025-09-23 17:29:00');
/*!40000 ALTER TABLE `video_links` ENABLE KEYS */;
UNLOCK TABLES;
commit;

--
-- Table structure for table `visitors`
--

DROP TABLE IF EXISTS `visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(40) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid_unique` (`uuid`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitors`
--

LOCK TABLES `visitors` WRITE;
/*!40000 ALTER TABLE `visitors` DISABLE KEYS */;
set autocommit=0;
INSERT INTO `visitors` VALUES
(11,'v_06e09651-ef16-457f-b39d-61fd19251bdf');
/*!40000 ALTER TABLE `visitors` ENABLE KEYS */;
UNLOCK TABLES;
commit;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2025-11-19 17:26:41
