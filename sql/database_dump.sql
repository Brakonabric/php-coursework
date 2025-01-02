-- MySQL dump 10.13  Distrib 8.0.40, for Linux (x86_64)
--
-- Host: localhost    Database: php_coursework
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `event_types`
--

DROP TABLE IF EXISTS `event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_types` (
  `id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `default_description` text COLLATE utf8mb4_unicode_ci,
  `visible_roles` json NOT NULL,
  `color` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_types`
--

LOCK TABLES `event_types` WRITE;
/*!40000 ALTER TABLE `event_types` DISABLE KEYS */;
INSERT INTO `event_types` VALUES ('coachMeeting','Treneru sapulce','Treneru sapulce','Sapulce treneriem','[\"coach\", \"admin\"]','#c8b6ff'),('individualTraining','Individuālais treniņš','Individuālais treniņš','Individuālais treniņš ar treneri','[\"teamMember\", \"coach\", \"admin\"]','#ffaaa5'),('match','Spēle','Spēle','Komandas spēle','[\"guest\", \"fan\", \"teamMember\", \"coach\", \"admin\"]','#ff8b94'),('medicalCheckup','Medicīniskā pārbaude','Medicīniskā pārbaude','Regulārā medicīniskā pārbaude','[\"teamMember\", \"coach\", \"admin\"]','#bbd0ff'),('publicEvent','Publisks pasākums','Jauns pasākums','Pasākuma apraksts','[\"guest\", \"fan\", \"teamMember\", \"coach\", \"admin\"]','#a8e6cf'),('teamBuilding','Komandas saliedēšanas pasākums','Komandas pasākums','Komandas saliedēšanas aktivitātes','[\"teamMember\", \"coach\", \"admin\"]','#b8c0ff'),('teamMeeting','Komandas sapulce','Komandas sapulce','Sapulce visiem komandas dalībniekiem','[\"teamMember\", \"coach\", \"admin\"]','#dcd3ff'),('teamTraining','Komandas treniņš','Komandas treniņš','Kopējais treniņš visiem komandas dalībniekiem','[\"teamMember\", \"coach\", \"admin\"]','#ffd3b6'),('tournament','Turnīrs','Turnīrs','Sporta turnīrs','[\"guest\", \"fan\", \"teamMember\", \"coach\", \"admin\"]','#ffc6ff');
/*!40000 ALTER TABLE `event_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `event_visibility` json NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`event_type`) REFERENCES `event_types` (`id`),
  CONSTRAINT `events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'Turnīrs','Sporta turnīrs','tournament','2025-02-03 15:30:00','2025-02-10 09:00:00','Sporta komplekss',1,'[\"guest\", \"fan\", \"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:41:19','2024-12-27 14:41:19'),(2,'Spēle','Komandas spēle','match','2024-12-06 09:00:00','2024-12-06 09:00:00','Sporta halle',1,'[\"guest\", \"fan\", \"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:41:46','2024-12-27 14:41:46'),(3,'Komandas treniņš','Kopējais treniņš visiem komandas dalībniekiem','teamTraining','2024-12-04 09:00:00','2024-12-04 09:00:00','Sporta zāle',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:42:05','2024-12-27 14:42:05'),(4,'Komandas treniņš','Kopējais treniņš visiem komandas dalībniekiem','teamTraining','2024-12-27 09:00:00','2024-12-11 09:00:00','Sporta zāle',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:42:12','2024-12-27 14:42:12'),(5,'Komandas treniņš','Kopējais treniņš visiem komandas dalībniekiem','teamTraining','2024-12-11 09:00:00','2024-12-11 09:00:00','Sporta zāle',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:42:23','2024-12-27 14:42:23'),(6,'Komandas treniņš','Kopējais treniņš visiem komandas dalībniekiem','teamTraining','2024-12-18 09:00:00','2024-12-18 09:00:00','Sporta zāle',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:42:32','2024-12-27 14:42:32'),(7,'Komandas treniņš','Kopējais treniņš visiem komandas dalībniekiem','teamTraining','2024-12-25 09:00:00','2024-12-25 09:00:00','Sporta zāle',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:42:39','2024-12-27 14:42:39'),(8,'Komandas treniņš','Kopējais treniņš visiem komandas dalībniekiem','teamTraining','2025-01-01 09:00:00','2025-01-01 09:00:00','Sporta zāle',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:42:52','2024-12-27 14:42:52'),(9,'Komandas treniņš','Kopējais treniņš visiem komandas dalībniekiem','teamTraining','2024-11-27 09:00:00','2024-11-27 09:00:00','Sporta zāle',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:42:59','2024-12-27 14:42:59'),(10,'Medicīniskā pārbaude','Regulārā medicīniskā pārbaude','medicalCheckup','2024-11-04 09:00:00','2024-11-08 09:00:00','Medicīnas kabinets',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:43:12','2024-12-27 14:43:12'),(11,'Treneru sanāksme','Sanāksme treneriem','coachMeeting','2025-01-03 09:00:00','2025-01-03 09:00:00','Treneru istaba',1,'[\"coach\", \"admin\"]','2024-12-27 14:43:40','2024-12-27 14:43:40'),(12,'Treneru sanāksme','Sanāksme treneriem','teamMeeting','2025-01-04 09:00:00','2025-01-04 09:00:00','Treneru istaba',1,'[\"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:43:52','2024-12-27 14:44:01'),(13,'Pavasara nometne','Pavasara nometne','publicEvent','2025-03-24 09:00:00','2025-03-28 09:00:00','Nometnes Kompleks',1,'[\"guest\", \"fan\", \"teamMember\", \"coach\", \"admin\"]','2024-12-27 14:45:31','2024-12-27 14:45:31');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery`
--

DROP TABLE IF EXISTS `gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `source_idx` (`source_type`,`source_id`),
  CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery`
--

LOCK TABLES `gallery` WRITE;
/*!40000 ALTER TABLE `gallery` DISABLE KEYS */;
INSERT INTO `gallery` VALUES (1,NULL,'/assets/images/uploads/news/preview_1735304566.jpg',1,'news',1,'2024-12-27 13:11:24'),(2,NULL,'/assets/images/uploads/news/1_extra_0_1735304566.jpg',1,'news',1,'2024-12-27 13:11:24'),(3,NULL,'/assets/images/uploads/news/1_extra_1_1735304566.jpg',1,'news',1,'2024-12-27 13:11:24'),(4,NULL,'/assets/images/uploads/news/1_extra_2_1735304566.jpg',1,'news',1,'2024-12-27 13:11:24'),(5,NULL,'/assets/images/uploads/news/preview_1735304599.jpg',1,'news',2,'2024-12-27 13:11:24'),(6,NULL,'/assets/images/uploads/news/2_extra_0_1735304599.jpg',1,'news',2,'2024-12-27 13:11:24'),(7,NULL,'/assets/images/uploads/news/2_extra_1_1735304599.jpg',1,'news',2,'2024-12-27 13:11:24'),(8,NULL,'/assets/images/uploads/news/2_extra_2_1735304599.jpg',1,'news',2,'2024-12-27 13:11:24'),(9,NULL,'/assets/images/uploads/news/preview_1735304921.jpg',1,'news',3,'2024-12-27 13:11:24'),(10,NULL,'/assets/images/uploads/news/3_extra_0_1735304921.jpg',1,'news',3,'2024-12-27 13:11:24'),(11,NULL,'/assets/images/uploads/news/preview_1735305029.jpg',1,'news',4,'2024-12-27 13:11:24'),(12,NULL,'/assets/images/uploads/news/4_extra_0_1735305029.jpg',1,'news',4,'2024-12-27 13:11:24'),(13,NULL,'/assets/images/uploads/news/4_extra_1_1735305029.jpg',1,'news',4,'2024-12-27 13:11:24'),(14,NULL,'/assets/images/uploads/news/preview_1735310260.jpg',1,'news',5,'2024-12-27 14:47:25'),(15,NULL,'/assets/images/uploads/news/5_extra_0_1735310260.jpg',1,'news',5,'2024-12-27 14:47:25'),(16,NULL,'/assets/images/uploads/news/5_extra_1_1735310260.jpg',1,'news',5,'2024-12-27 14:47:25'),(17,NULL,'/assets/images/uploads/gallery/676ebf858de30.jpg',1,NULL,NULL,'2024-12-27 14:53:57'),(18,NULL,'/assets/images/uploads/gallery/676ebf88c803d.jpg',1,NULL,NULL,'2024-12-27 14:54:00'),(19,NULL,'/assets/images/uploads/gallery/676ebf8ac97ff.jpg',1,NULL,NULL,'2024-12-27 14:54:02'),(20,NULL,'/assets/images/uploads/gallery/676ebf8f99dbd.jpg',1,NULL,NULL,'2024-12-27 14:54:07'),(21,NULL,'/assets/images/uploads/gallery/676ebf93a14a5.jpg',1,NULL,NULL,'2024-12-27 14:54:11'),(22,NULL,'/assets/images/uploads/gallery/676ebf97be504.jpg',1,NULL,NULL,'2024-12-27 14:54:15'),(23,NULL,'/assets/images/uploads/gallery/676ebf9c23465.jpg',1,NULL,NULL,'2024-12-27 14:54:20'),(24,NULL,'/assets/images/uploads/gallery/676ebfa097ece.jpg',1,NULL,NULL,'2024-12-27 14:54:24'),(25,NULL,'/assets/images/uploads/gallery/676ebfa51cd08.jpg',1,NULL,NULL,'2024-12-27 14:54:29'),(26,NULL,'/assets/images/uploads/news/preview_1735311354.jpg',1,'news',6,'2024-12-27 14:57:02'),(27,NULL,'/assets/images/uploads/news/6_extra_0_1735311354.jpg',1,'news',6,'2024-12-27 14:57:02'),(28,NULL,'/assets/images/uploads/news/6_extra_1_1735311354.jpg',1,'news',6,'2024-12-27 14:57:02');
/*!40000 ALTER TABLE `gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_comments`
--

DROP TABLE IF EXISTS `gallery_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `photo_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `photo_id` (`photo_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `gallery_comments_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `gallery` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gallery_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_comments`
--

LOCK TABLES `gallery_comments` WRITE;
/*!40000 ALTER TABLE `gallery_comments` DISABLE KEYS */;
INSERT INTO `gallery_comments` VALUES (1,11,3,'Love this!','2024-12-27 14:38:36'),(2,11,3,'Incredible Incredible content!content!','2024-12-27 14:38:43'),(3,7,3,'Incredible content!','2024-12-27 14:38:47'),(4,6,3,'Incredible content!','2024-12-27 14:38:49'),(5,11,1,'So cool!','2024-12-27 14:39:48');
/*!40000 ALTER TABLE `gallery_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gallery_likes`
--

DROP TABLE IF EXISTS `gallery_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gallery_likes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `photo_id` int NOT NULL,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`photo_id`,`user_id`),
  KEY `photo_id` (`photo_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `gallery_likes_ibfk_1` FOREIGN KEY (`photo_id`) REFERENCES `gallery` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gallery_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gallery_likes`
--

LOCK TABLES `gallery_likes` WRITE;
/*!40000 ALTER TABLE `gallery_likes` DISABLE KEYS */;
INSERT INTO `gallery_likes` VALUES (1,6,3,'2024-12-27 14:38:51'),(2,8,3,'2024-12-27 14:38:53'),(3,10,3,'2024-12-27 14:38:55'),(4,11,3,'2024-12-27 14:38:57'),(5,1,3,'2024-12-27 14:39:01'),(6,12,1,'2024-12-27 14:39:22'),(8,6,1,'2024-12-27 14:39:33'),(9,8,1,'2024-12-27 14:39:36'),(10,1,1,'2024-12-27 14:47:38'),(11,6,4,'2024-12-27 14:48:06'),(12,23,1,'2024-12-27 14:56:28'),(13,21,1,'2024-12-27 14:56:29'),(14,25,1,'2024-12-27 14:56:32');
/*!40000 ALTER TABLE `gallery_likes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path_preview` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_path_extra` text COLLATE utf8mb4_unicode_ci,
  `user_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `news_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
INSERT INTO `news` VALUES (1,'Where can I get some?','There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don\\\'t look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn\\\'t anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.','/assets/images/uploads/news/preview_1735304566.jpg','[\"\\/assets\\/images\\/uploads\\/news\\/1_extra_0_1735304566.jpg\",\"\\/assets\\/images\\/uploads\\/news\\/1_extra_1_1735304566.jpg\",\"\\/assets\\/images\\/uploads\\/news\\/1_extra_2_1735304566.jpg\"]',1,'2024-12-27 13:02:46'),(2,'Where does it come from?','Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \\\"de Finibus Bonorum et Malorum\\\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \\\"Lorem ipsum dolor sit amet..\\\", comes from a line in section 1.10.32.\\r\\n\\r\\nThe standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from \\\"de Finibus Bonorum et Malorum\\\" by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham.','/assets/images/uploads/news/preview_1735304599.jpg','[\"\\/assets\\/images\\/uploads\\/news\\/2_extra_0_1735304599.jpg\",\"\\/assets\\/images\\/uploads\\/news\\/2_extra_1_1735304599.jpg\",\"\\/assets\\/images\\/uploads\\/news\\/2_extra_2_1735304599.jpg\"]',1,'2024-12-27 13:03:19'),(3,'What is Lorem Ipsum?','Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\\\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.','/assets/images/uploads/news/preview_1735304921.jpg','[\"\\/assets\\/images\\/uploads\\/news\\/3_extra_0_1735304921.jpg\"]',1,'2024-12-27 13:08:41'),(4,'Neque porro quisquam est qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit','Lorem ipsum dolor sit amet, consectetur adipiscing elit. In vel elit nec risus ullamcorper imperdiet ut ut urna. Ut cursus nisi nisl, vel euismod nunc interdum vitae. Cras ornare arcu et maximus consectetur. Nam volutpat leo quis efficitur pulvinar. Praesent convallis nisl tellus, sed efficitur ipsum venenatis et. Fusce sed tincidunt mauris, et finibus sem. In varius ac odio eget egestas. Pellentesque fringilla enim eu massa consequat, fermentum hendrerit massa convallis. Quisque ante neque, dignissim quis volutpat vitae, placerat sed nunc. Pellentesque eu orci in magna rutrum consectetur sed at nisl. Pellentesque consectetur dictum feugiat. Sed et aliquet augue. Mauris vel quam ut nulla laoreet tincidunt. Nunc facilisis arcu nec nunc sodales, sit amet pellentesque leo scelerisque. ','/assets/images/uploads/news/preview_1735305029.jpg','[\"\\/assets\\/images\\/uploads\\/news\\/4_extra_0_1735305029.jpg\",\"\\/assets\\/images\\/uploads\\/news\\/4_extra_1_1735305029.jpg\"]',1,'2024-12-27 13:10:29'),(5,'There is no one who loves pain itself, who seeks after it and wants to have it, simply because it is pain...','Nunc id varius lorem. Sed facilisis leo laoreet, auctor neque luctus, ullamcorper ipsum. Nullam luctus erat nibh, quis dictum ex convallis semper. Suspendisse tempus ullamcorper diam, sed interdum ante molestie a. Sed congue mi a rhoncus scelerisque. Donec porttitor libero ut turpis laoreet, a sodales leo commodo. Suspendisse consequat sodales magna ac congue. Praesent eget elementum elit, a sagittis ipsum. Etiam accumsan augue laoreet mollis euismod. Proin vel consectetur nunc. Integer porta vulputate magna. Mauris dapibus est nibh, et laoreet neque scelerisque vitae. Sed et consequat erat. Cras aliquam sapien ut tellus gravida, id placerat lorem ultrices. Nullam sagittis, dolor accumsan fermentum blandit, est felis vehicula dui, vel suscipit eros velit sed erat. ','/assets/images/uploads/news/preview_1735310260.jpg','[\"\\/assets\\/images\\/uploads\\/news\\/5_extra_0_1735310260.jpg\",\"\\/assets\\/images\\/uploads\\/news\\/5_extra_1_1735310260.jpg\"]',1,'2024-12-27 14:37:40'),(6,'Lorem Ipsum','Phasellus ante sapien, pharetra in odio eu, cursus lacinia risus. Phasellus quis turpis facilisis, hendrerit diam in, fringilla enim. Nulla id feugiat nunc. Mauris placerat porta nisi quis facilisis. In suscipit ipsum ut mauris eleifend vehicula. Suspendisse pretium augue sed turpis imperdiet consequat a id diam. Vivamus ornare tincidunt tristique. Duis at nunc consectetur ante euismod vulputate. Nulla luctus dolor eget ante luctus faucibus. ','/assets/images/uploads/news/preview_1735311354.jpg','[\"\\/assets\\/images\\/uploads\\/news\\/6_extra_0_1735311354.jpg\",\"\\/assets\\/images\\/uploads\\/news\\/6_extra_1_1735311354.jpg\"]',1,'2024-12-27 14:55:54');
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_comments`
--

DROP TABLE IF EXISTS `news_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `news_id` int NOT NULL,
  `user_id` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `news_id` (`news_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `news_comments_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  CONSTRAINT `news_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_comments`
--

LOCK TABLES `news_comments` WRITE;
/*!40000 ALTER TABLE `news_comments` DISABLE KEYS */;
INSERT INTO `news_comments` VALUES (1,2,1,'Wow! Thanks amazing!','2024-12-27 13:03:38'),(2,2,2,'This made my day!','2024-12-27 13:06:02'),(3,1,2,'Keep it up!','2024-12-27 13:06:18'),(4,3,1,'Super inspiring!','2024-12-27 13:08:53'),(5,5,3,'Love this!','2024-12-27 14:38:23'),(6,5,4,'NICE!','2024-12-27 14:46:40');
/*!40000 ALTER TABLE `news_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('guest','fan','teamMember','coach','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'fan',
  `can_comment` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','Adminson','admin@example.com','','$2y$10$tR6v478NZgLCD0hc1qN7KuWsHC3XsfJ8mROArxHsQyPX3qOyM6fpm','admin',1,'2024-12-27 00:55:23'),(2,'Linass','Coach','coach@example.com','','$2y$10$vQa1z/ALfO1pnVvMn/3KNuhd1fvUcoy5RrcxiyiL037rckdAxeaCy','coach',1,'2024-12-27 13:05:10'),(3,'Arturs','Fan','fan@example.com','','$2y$10$rKstDDdHg6skNFy.KmsfyOyx/5te864srAEX748oytMIr.Z79lpzO','fan',1,'2024-12-27 14:38:08'),(4,'Davids','Players','players@example.com','','$2y$10$nu4YSmUhAB6ZiCuaBLPmy.qKeT57fpwH0Rt7OwSxaDDPfO5F6JqCa','teamMember',1,'2024-12-27 14:46:30');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-12-27 15:02:39
