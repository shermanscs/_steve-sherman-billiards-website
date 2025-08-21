-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Aug 21, 2025 at 12:54 PM
-- Server version: 8.0.42-33
-- PHP Version: 8.3.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `uihgfite_WPUNF`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_unit_content_with_details`
-- (See below for the actual view)
--
CREATE TABLE `vw_unit_content_with_details` (
`id` int
,`unit_id` int
,`content_type` enum('drill','instruction','video','document','assignment','training_content','other')
,`drill_id` int
,`content_title` varchar(255)
,`content_description` text
,`content_data` longtext
,`content_order` int
,`is_required` tinyint
,`estimated_duration_minutes` int
,`points_possible` int
,`created_by` int
,`is_active` tinyint
,`created_at` timestamp
,`updated_at` timestamp
,`unit_name` varchar(255)
,`unit_description` text
,`program_name` varchar(255)
,`drill_name` varchar(255)
,`drill_description` text
,`drill_max_score` int
,`drill_difficulty` decimal(3,1)
,`drill_category` varchar(100)
,`drill_skill` varchar(100)
,`created_by_name` varchar(255)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_program_progress`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_program_progress` (
`user_id` int
,`program_id` int
,`program_name` varchar(255)
,`user_name` varchar(255)
,`total_content_items` bigint
,`completed_items` bigint
,`in_progress_items` bigint
,`not_started_items` bigint
,`completion_percentage` decimal(26,2)
,`average_score_percentage` decimal(19,10)
,`total_time_spent_minutes` decimal(32,0)
,`last_activity_date` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `wp_admin_passwords`
--

CREATE TABLE `wp_admin_passwords` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_admin_passwords`
--

INSERT INTO `wp_admin_passwords` (`id`, `user_id`, `password`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin', '2025-07-17 21:39:22', '2025-07-17 21:39:22'),
(2, 2, 'admin', '2025-07-17 21:39:22', '2025-07-17 21:39:22');

-- --------------------------------------------------------

--
-- Table structure for table `wp_challenge_events`
--

CREATE TABLE `wp_challenge_events` (
  `id` int NOT NULL,
  `series_name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `drill_id` int NOT NULL,
  `scoring_method_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('scheduled','active','complete','cancelled') DEFAULT 'scheduled',
  `description` text,
  `max_attempts` int DEFAULT '3',
  `created_by` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_challenge_events`
--

INSERT INTO `wp_challenge_events` (`id`, `series_name`, `title`, `drill_id`, `scoring_method_id`, `start_date`, `end_date`, `status`, `description`, `max_attempts`, `created_by`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Straight Pool', 'level 1 14.1', 8, 1, '2025-07-20', '2025-07-27', 'scheduled', '0', 1, 1, 1, '2025-07-20 17:50:56', '2025-07-20 18:53:49'),
(2, 'Straight Pool', 'level 2 14.1', 8, 2, '2025-07-20', '2025-07-27', 'scheduled', '0', 3, 1, 1, '2025-07-20 18:35:30', '2025-07-20 18:54:06'),
(3, 'Straight Pool', 'level 3 event title', 8, 2, '2025-07-20', '2025-07-27', 'scheduled', '0', 3, 1, 1, '2025-07-20 19:26:53', '2025-07-20 19:28:49'),
(4, 'Straight Pool', '0', 8, 3, '2025-07-21', '2025-07-28', 'scheduled', '0', 1, 1, 1, '2025-07-21 14:19:13', '2025-07-21 14:19:13'),
(5, 'Straight Pool', '0', 8, 2, '2025-07-21', '2025-07-28', 'scheduled', '0', 3, 1, 1, '2025-07-21 16:48:49', '2025-07-21 16:48:49'),
(6, 'Straight Pool', 'test 6', 15, 2, '2025-07-21', '2025-07-28', 'scheduled', 'test 6 desc', 3, 1, 1, '2025-07-21 17:51:30', '2025-07-21 17:51:30');

-- --------------------------------------------------------

--
-- Table structure for table `wp_challenge_participants`
--

CREATE TABLE `wp_challenge_participants` (
  `id` int NOT NULL,
  `challenge_event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `enrolled_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `enrolled_by` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_challenge_participants`
--

INSERT INTO `wp_challenge_participants` (`id`, `challenge_event_id`, `user_id`, `enrolled_date`, `enrolled_by`, `is_active`) VALUES
(1, 3, 3, '2025-07-21 03:46:36', 1, 1),
(3, 3, 4, '2025-07-21 03:50:15', 1, 0),
(4, 2, 3, '2025-07-21 03:50:35', 1, 1),
(5, 1, 3, '2025-07-21 03:55:19', 1, 1),
(6, 6, 4, '2025-07-21 22:57:38', 1, 1),
(7, 5, 3, '2025-07-30 22:01:26', 1, 1),
(8, 5, 4, '2025-07-30 22:01:28', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `wp_challenge_scores`
--

CREATE TABLE `wp_challenge_scores` (
  `id` int NOT NULL,
  `challenge_event_id` int NOT NULL,
  `user_id` int NOT NULL,
  `drill_id` int NOT NULL,
  `score` decimal(8,2) NOT NULL,
  `max_possible_score` int NOT NULL,
  `percentage` decimal(5,2) GENERATED ALWAYS AS (((`score` / `max_possible_score`) * 100)) STORED,
  `attempt_number` int DEFAULT '1',
  `session_notes` text,
  `practice_date` date DEFAULT (curdate()),
  `submitted_by` int NOT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wp_challenge_scoring_methods`
--

CREATE TABLE `wp_challenge_scoring_methods` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text,
  `calculation_method` enum('highest_score','average_score','total_score','best_percentage') DEFAULT 'highest_score',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_challenge_scoring_methods`
--

INSERT INTO `wp_challenge_scoring_methods` (`id`, `name`, `display_name`, `description`, `calculation_method`, `is_active`, `created_at`) VALUES
(1, 'highest_score', 'Highest Score', 'Winner determined by the highest single score achieved', 'highest_score', 1, '2025-07-20 13:23:24'),
(2, 'average_score', 'Average Score', 'Winner determined by the average of all attempts', 'average_score', 1, '2025-07-20 13:23:24'),
(3, 'total_score', 'Total Score', 'Winner determined by the sum of all attempts', 'total_score', 1, '2025-07-20 13:23:24'),
(4, 'best_percentage', 'Best Percentage', 'Winner determined by the highest percentage achieved', 'best_percentage', 1, '2025-07-20 13:23:24');

-- --------------------------------------------------------

--
-- Table structure for table `wp_coach_passwords`
--

CREATE TABLE `wp_coach_passwords` (
  `id` int NOT NULL,
  `coach_id` int NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_coach_passwords`
--

INSERT INTO `wp_coach_passwords` (`id`, `coach_id`, `password_hash`, `created_at`, `updated_at`) VALUES
(2, 5, 'booger200!', '2025-07-29 03:16:41', '2025-07-29 20:23:47'),
(3, 6, 'booger200!', '2025-07-29 03:16:41', '2025-07-29 20:23:47');

-- --------------------------------------------------------

--
-- Table structure for table `wp_credit_to`
--

CREATE TABLE `wp_credit_to` (
  `id` int NOT NULL,
  `organization_name` varchar(255) NOT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `description` text,
  `is_active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `icon_url` varchar(500) DEFAULT NULL,
  `icon_filename` varchar(255) DEFAULT NULL,
  `icon_file_size` bigint DEFAULT NULL,
  `icon_mime_type` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_credit_to`
--

INSERT INTO `wp_credit_to` (`id`, `organization_name`, `website_url`, `description`, `is_active`, `created_at`, `updated_at`, `created_by`, `icon_url`, `icon_filename`, `icon_file_size`, `icon_mime_type`) VALUES
(1, 'Roy Pastor, Break and Run Program', 'https://playbetterbilliards.com/jr-instruction/', 'this is a test description', 1, '2025-08-17 18:57:03', '2025-08-17 21:00:09', 1, 'https://steveshermanbilliards.com/wp-content/uploads/credit-icons/credit_icon_1.png', 'credit_icon_1.png', 410844, 'image/png'),
(2, 'Dr. Dave Billiards', 'https://drdavebilliards.com/', NULL, 1, '2025-08-17 20:41:11', '2025-08-17 22:22:19', 1, 'https://steveshermanbilliards.com/wp-content/uploads/credit-icons/credit_icon_2.png', 'credit_icon_2.png', 23781, 'image/png');

-- --------------------------------------------------------

--
-- Table structure for table `wp_diagrams`
--

CREATE TABLE `wp_diagrams` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `diagram_type` varchar(50) DEFAULT 'drill',
  `visibility` enum('public','private') DEFAULT 'private',
  `original_filename` varchar(255) NOT NULL,
  `created_by` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `image_url` varchar(500) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `vector_data` longtext COMMENT 'JSON string containing SVG vector information',
  `is_vector` tinyint(1) DEFAULT '0' COMMENT '1 if diagram contains vector data, 0 for image files',
  `credit_id` int DEFAULT NULL COMMENT 'Optional credit reference'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_diagrams`
--

INSERT INTO `wp_diagrams` (`id`, `name`, `description`, `diagram_type`, `visibility`, `original_filename`, `created_by`, `is_active`, `created_at`, `updated_at`, `image_url`, `thumbnail_url`, `vector_data`, `is_vector`, `credit_id`) VALUES
(6, 'long line drill to file', 'description of long line drill to file sdsdsdf', 'drill', 'public', 'Long Line Drill Level 1.jpg', 1, 1, '2025-07-26 02:52:35', '2025-07-26 03:54:23', 'https://steveshermanbilliards.com/wp-content/uploads/diagrams/diagram_6.jpg', 'https://steveshermanbilliards.com/wp-content/uploads/diagrams/thumbnails/thumb_diagram_6.jpg', NULL, 0, NULL),
(7, 'my first draawing', 'this is a description of my first drawing\r\n\r\n[DRAWING_DATA]{\\\"balls\\\":[{\\\"type\\\":\\\"cue\\\",\\\"x\\\":135.71426391601562,\\\"y\\\":127.28570556640625},{\\\"type\\\":\\\"1\\\",\\\"x\\\":237.71426391601562,\\\"y\\\":138.28570556640625}],\\\"lines\\\":[{\\\"x1\\\":157.71426391601562,\\\"y1\\\":130.71426391601562,\\\"x2\\\":221.71426391601562,\\\"y2\\\":132.71426391601562,\\\"color\\\":\\\"white\\\",\\\"strokeWidth\\\":2},{\\\"x1\\\":267.7142639160156,\\\"y1\\\":134.71426391601562,\\\"x2\\\":348.7142639160156,\\\"y2\\\":49.714263916015625,\\\"color\\\":\\\"white\\\",\\\"strokeWidth\\\":2}],\\\"usedBalls\\\":[\\\"cue\\\",\\\"1\\\"]}[/DRAWING_DATA]', 'drill', 'public', 'my_first_draawing.png', 1, 1, '2025-07-26 03:48:56', '2025-07-26 03:48:56', 'https://steveshermanbilliards.com/wp-content/uploads/diagrams/diagram_7.png', 'https://steveshermanbilliards.com/wp-content/uploads/diagrams/thumbnails/thumb_diagram_7.png', NULL, 0, NULL),
(9, 'Test 2 after configuring the new vector column', 'Test 2 after configuring the new vector column description\r\n\r\n[DRAWING_DATA]{\\\"balls\\\":[{\\\"type\\\":\\\"1\\\",\\\"x\\\":86.71426391601562,\\\"y\\\":104.28570556640625},{\\\"type\\\":\\\"2\\\",\\\"x\\\":419.7142639160156,\\\"y\\\":197.28570556640625}],\\\"lines\\\":[{\\\"x1\\\":400.7142639160156,\\\"y1\\\":189.71426391601562,\\\"x2\\\":107.71426391601562,\\\"y2\\\":112.71426391601562,\\\"color\\\":\\\"white\\\",\\\"strokeWidth\\\":2}],\\\"usedBalls\\\":[\\\"1\\\",\\\"2\\\"]}[/DRAWING_DATA]', 'drill', 'public', 'Test_2_after_configuring_the_new_vector_column.png', 1, 1, '2025-07-28 22:26:39', '2025-07-28 22:29:18', 'https://steveshermanbilliards.com/wp-content/uploads/diagrams/diagram_9.png', 'https://steveshermanbilliards.com/wp-content/uploads/diagrams/thumbnails/thumb_diagram_9.png', NULL, 0, NULL),
(10, 'defense spin behind blocker', 'this is a description', 'drill', 'public', 'Spin two rails behind blocker balls.jpg', 2, 1, '2025-08-09 20:17:55', '2025-08-09 20:17:55', 'https://steveshermanbilliards.com/wp-content/uploads/diagrams/diagram_10.jpg', 'https://steveshermanbilliards.com/wp-content/uploads/diagrams/thumbnails/thumb_diagram_10.jpg', NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wp_drills`
--

CREATE TABLE `wp_drills` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int NOT NULL,
  `skill_id` int NOT NULL,
  `description` text,
  `instructions` text,
  `max_score` int DEFAULT '10',
  `video_url` varchar(500) DEFAULT NULL,
  `difficulty_rating` decimal(3,1) DEFAULT '1.0',
  `estimated_time_minutes` int DEFAULT NULL,
  `diagram_id` int DEFAULT NULL,
  `color_code` varchar(7) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `credit_id` int DEFAULT NULL COMMENT 'Optional credit reference'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_drills`
--

INSERT INTO `wp_drills` (`id`, `name`, `category_id`, `skill_id`, `description`, `instructions`, `max_score`, `video_url`, `difficulty_rating`, `estimated_time_minutes`, `diagram_id`, `color_code`, `is_active`, `created_at`, `updated_at`, `credit_id`) VALUES
(1, 'Basic Stance', 1, 1, 'Practice proper stance and bridge formation', NULL, 10, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#4A90E2', 0, '2025-07-17 03:28:38', '2025-07-17 17:43:37', NULL),
(2, 'Straight Shots', 1, 1, 'Practice shooting straight into pockets', NULL, 20, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#50C878', 0, '2025-07-17 03:28:38', '2025-07-17 17:33:49', NULL),
(3, 'Stop Shot Practice', 1, 2, 'Practice stopping the cue ball on contact', NULL, 15, '', 1.0, NULL, NULL, '#FF6347', 0, '2025-07-17 03:28:38', '2025-07-17 19:29:52', NULL),
(4, 'Basic 8-Ball Rack', 1, 3, 'Practice basic position play in 8-ball', NULL, 8, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#9370DB', 0, '2025-07-17 03:28:38', '2025-07-17 17:33:16', NULL),
(5, 'Draw Shot Control', 2, 2, 'Practice controlled draw shots', NULL, 30, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#DC143C', 0, '2025-07-17 03:28:38', '2025-07-17 20:15:11', NULL),
(6, 'Follow Shot Practice', 2, 2, 'Practice controlled follow shots', NULL, 20, '', 1.0, NULL, NULL, '#00CED1', 0, '2025-07-17 03:28:38', '2025-07-18 05:14:14', NULL),
(7, 'Simple Patterns', 2, 4, 'Practice running 3-4 ball patterns', NULL, 25, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#FF8C00', 0, '2025-07-17 03:28:38', '2025-07-18 20:37:02', NULL),
(8, '14.1 Level 1', 1, 3, 'Practice your high run in 14.1', '', 50, '', 1.0, NULL, 9, '#9932cc', 1, '2025-07-17 03:28:38', '2025-07-28 23:01:45', NULL),
(9, 'Bridge Techniques', 2, 1, 'Advanced bridge formations and techniques', NULL, 15, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#228B22', 0, '2025-07-17 03:28:38', '2025-07-17 20:25:11', NULL),
(10, 'Complex Patterns', 3, 4, 'Practice running full racks', NULL, 50, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#800080', 0, '2025-07-17 03:28:38', '2025-07-17 17:34:02', NULL),
(11, 'Advanced Position', 3, 3, 'Advanced position play scenarios', NULL, 30, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#FF1493', 0, '2025-07-17 03:28:38', '2025-07-24 22:35:34', NULL),
(12, 'Five ball position drill level 1', 3, 2, 'The five ball position drill is a very good way to start practicing cue ball control and the tangent line.', '1 point for each ball pocketed', 40, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#8B0000', 1, '2025-07-17 03:28:38', '2025-07-19 02:58:17', NULL),
(13, 'Speed Control', 3, 2, 'Practice precise speed control', NULL, 35, '', 1.0, NULL, NULL, '#006400', 1, '2025-07-17 03:28:38', '2025-07-17 03:28:38', NULL),
(14, 'Advanced Fundamentals', 3, 1, 'Refinement of fundamental techniques', NULL, 25, 'https://youtu.be/wIfK1YE7bCI', 1.0, NULL, NULL, '#4B0082', 0, '2025-07-17 03:28:38', '2025-07-17 20:25:20', NULL),
(15, 'Use this 15 Across Drill it grabs diagram 7', 1, 1, 'The Fifteen Across drill will be one of our most \"go to\" drills. We will use it to focus on specific skill sets including rhythm, cadence, pre shot routine, stroke mechanics, ball approach and much more.', 'Ball in hand each shot, around the first diamond\nIf you are just beginning, you could start without a cueball\nShoot half the object balls in the top left corner\nShoot half the object balls in the top right corner\nWhen you can make all 15 , move one diamond down and continue the process\n\nScore 1 point if you performed the drill without a cueball\nIf you used a cueball, score the points equal to the diamond from where you shot the line of balls.\nScore 2 if the balls were lined up on the 2nd diamond, 3 if the balls were lined up on the 3rd diamond, 4, if the balls were lined up on side pocket (4 is the max)', 15, '', 1.0, NULL, 7, '#667eea', 1, '2025-07-18 23:18:09', '2025-07-27 13:07:02', NULL),
(16, 'test drill with new integration', 1, 2, 'sfsdfsdfsdf', 'sfdsdfsf', 10, '', 1.0, NULL, 9, '#667eea', 1, '2025-07-26 13:48:33', '2025-07-28 23:01:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wp_drill_assignments`
--

CREATE TABLE `wp_drill_assignments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `drill_id` int NOT NULL,
  `assigned_by` int NOT NULL,
  `assigned_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date` date DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT '0',
  `completed_date` timestamp NULL DEFAULT NULL,
  `notes` text,
  `coach_comments` text,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_drill_assignments`
--

INSERT INTO `wp_drill_assignments` (`id`, `user_id`, `drill_id`, `assigned_by`, `assigned_date`, `due_date`, `is_completed`, `completed_date`, `notes`, `coach_comments`, `is_active`) VALUES
(1, 1, 1, 1, '2025-07-17 03:28:38', NULL, 0, NULL, '', 'I am going to delete this ome', 0),
(2, 1, 2, 1, '2025-07-17 03:28:38', NULL, 1, '2025-07-24 03:53:25', '', 'this is a new comment added', 0),
(3, 1, 5, 1, '2025-07-17 03:28:38', NULL, 0, NULL, NULL, NULL, 0),
(4, 1, 12, 1, '2025-07-17 03:28:38', NULL, 1, '2025-07-24 22:19:28', '', 'Five ball drill level 1. I want to see if this record is showing active 1 or active 0', 0),
(5, 3, 3, 1, '2025-07-17 03:28:38', NULL, 0, NULL, NULL, NULL, 0),
(6, 3, 4, 1, '2025-07-17 03:28:38', NULL, 0, NULL, NULL, NULL, 0),
(7, 3, 6, 1, '2025-07-17 03:28:38', NULL, 0, NULL, NULL, NULL, 0),
(8, 4, 7, 1, '2025-07-17 03:28:38', NULL, 0, NULL, NULL, NULL, 0),
(9, 4, 8, 1, '2025-07-17 03:28:38', NULL, 0, NULL, '', 'this is a test that the comments are taking but I have changed in now. Is this working??!!', 1),
(10, 5, 10, 1, '2025-07-17 03:28:38', NULL, 0, NULL, NULL, NULL, 1),
(11, 5, 11, 1, '2025-07-17 03:28:38', NULL, 0, NULL, NULL, NULL, 0),
(12, 3, 13, 1, '2025-07-24 23:02:10', NULL, 0, NULL, '', 'I would like to see if Matt can do this', 0),
(13, 1, 15, 1, '2025-07-25 02:44:00', NULL, 0, NULL, '', '', 1),
(14, 1, 8, 1, '2025-07-25 02:44:04', NULL, 0, NULL, '', '', 1),
(15, 1, 12, 1, '2025-07-25 02:44:08', NULL, 0, NULL, '', '', 1),
(16, 4, 15, 1, '2025-07-25 02:47:21', NULL, 0, NULL, '', '', 1),
(17, 4, 13, 1, '2025-07-25 02:47:24', NULL, 0, NULL, '', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wp_drill_categories`
--

CREATE TABLE `wp_drill_categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_drill_categories`
--

INSERT INTO `wp_drill_categories` (`id`, `name`, `display_name`, `description`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'beginner', 'Beginner', 'Basic skills and fundamentals for new players', 1, 1, '2025-07-17 03:28:38'),
(2, 'intermediate', 'Intermediate', 'Developing skills for progressing players', 2, 1, '2025-07-17 03:28:38'),
(3, 'advanced', 'Advanced', 'Complex skills for experienced players', 3, 1, '2025-07-17 03:28:38');

-- --------------------------------------------------------

--
-- Table structure for table `wp_drill_journal`
--

CREATE TABLE `wp_drill_journal` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_drill_journal`
--

INSERT INTO `wp_drill_journal` (`id`, `user_id`, `title`, `content`, `created_at`, `updated_at`, `is_active`) VALUES
(2, 1, '15 ball across drill', 'I am not consistently performing my preshot routine', '2025-07-19 14:44:34', '2025-07-24 22:00:44', 0),
(4, 1, 'long line drill - draw issue', 'my short draw is good, my long draw is an issue', '2025-07-19 14:44:54', '2025-07-24 22:00:29', 0),
(5, 1, '14.1 level 1 drill problem I am having', 'I am having trouble breaking my clusters', '2025-07-20 02:03:06', '2025-07-24 22:17:04', 0),
(6, 1, '5 ball runout drill problems I am having', 'On the 5 ball drill, I am having issue cutting to the left. Cutting to the right is ok', '2025-07-20 02:03:06', '2025-07-20 02:22:22', 0),
(7, 1, 'corner to corner follow in issue', 'When I shoot slower, I make it, but when I shoot harder, I miss', '2025-07-20 02:18:39', '2025-07-20 02:18:39', 1),
(8, 1, 'corner to corner stop shot', 'I am always drifting to the left', '2025-07-20 02:19:41', '2025-07-24 21:57:52', 0),
(9, 1, 'long line drill - crossing over', 'when I cross over I have speed control issues\n\n[DRAWING_DATA]{\"balls\":[{\"type\":\"1\",\"x\":148.85711669921875,\"y\":105.14285278320312},{\"type\":\"2\",\"x\":266.85711669921875,\"y\":103.14285278320312}],\"lines\":[{\"x1\":256.85711669921875,\"y1\":112.85711669921875,\"x2\":165.85711669921875,\"y2\":105.85711669921875,\"color\":\"white\",\"strokeWidth\":2}],\"usedBalls\":[\"1\",\"2\"]}[/DRAWING_DATA]', '2025-07-20 02:22:05', '2025-07-24 21:29:46', 0);

-- --------------------------------------------------------

--
-- Table structure for table `wp_drill_scores`
--

CREATE TABLE `wp_drill_scores` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `drill_id` int NOT NULL,
  `score` decimal(8,2) NOT NULL,
  `max_possible_score` int NOT NULL,
  `percentage` decimal(5,2) GENERATED ALWAYS AS (((`score` / `max_possible_score`) * 100)) STORED,
  `is_assigned_drill` tinyint(1) DEFAULT '0',
  `assignment_id` int DEFAULT NULL,
  `submitted_by` int NOT NULL,
  `session_notes` text,
  `practice_date` date DEFAULT (curdate()),
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_drill_scores`
--

INSERT INTO `wp_drill_scores` (`id`, `user_id`, `drill_id`, `score`, `max_possible_score`, `is_assigned_drill`, `assignment_id`, `submitted_by`, `session_notes`, `practice_date`, `submitted_at`) VALUES
(1, 1, 1, 6.00, 10, 0, NULL, 1, '', '2025-07-17', '2025-07-17 14:30:33'),
(2, 1, 1, 3.00, 10, 0, NULL, 1, '', '2025-07-17', '2025-07-17 14:30:48'),
(3, 3, 1, 6.00, 10, 0, NULL, 3, '', '2025-07-17', '2025-07-17 15:28:22'),
(4, 3, 1, 1.00, 10, 0, NULL, 3, '', '2025-07-17', '2025-07-17 15:34:36'),
(5, 3, 3, 15.00, 15, 0, NULL, 3, '', '2025-07-17', '2025-07-17 15:34:45'),
(6, 1, 8, 2.00, 9, 0, NULL, 1, '', '2025-07-18', '2025-07-18 01:44:24'),
(7, 1, 6, 2.00, 20, 0, NULL, 1, '', '2025-07-18', '2025-07-18 01:49:02'),
(8, 1, 12, 10.00, 40, 0, NULL, 1, '', '2025-07-18', '2025-07-18 01:52:54'),
(9, 1, 13, 1.00, 35, 0, NULL, 1, '', '2025-07-18', '2025-07-18 04:24:31'),
(10, 1, 11, 15.00, 30, 0, NULL, 1, '', '2025-07-18', '2025-07-18 18:52:58'),
(11, 1, 8, 1.00, 9, 0, NULL, 1, '', '2025-07-18', '2025-07-18 20:16:18'),
(12, 1, 8, 2.00, 9, 0, NULL, 1, '', '2025-07-18', '2025-07-18 20:59:31'),
(13, 1, 12, 7.00, 40, 0, NULL, 1, '', '2025-07-18', '2025-07-18 21:36:17'),
(14, 1, 12, 5.00, 40, 0, NULL, 1, '', '2025-07-19', '2025-07-19 03:11:59'),
(15, 1, 15, 6.00, 15, 0, NULL, 1, '', '2025-07-19', '2025-07-19 14:45:13'),
(16, 1, 15, 5.00, 15, 0, NULL, 1, '', '2025-07-19', '2025-07-19 14:55:26'),
(17, 1, 15, 12.00, 15, 0, NULL, 1, '', '2025-07-21', '2025-07-21 04:00:19'),
(18, 1, 15, 7.00, 15, 0, NULL, 1, '', '2025-07-21', '2025-07-21 13:17:11'),
(19, 4, 15, 10.00, 15, 0, NULL, 4, '', '2025-07-23', '2025-07-23 04:53:23'),
(20, 4, 15, 11.00, 15, 0, NULL, 4, '', '2025-07-23', '2025-07-23 04:53:44'),
(21, 4, 15, 15.00, 15, 0, NULL, 4, '', '2025-07-23', '2025-07-23 11:57:06'),
(22, 4, 8, 10.00, 50, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:01:57'),
(23, 4, 8, 50.00, 50, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:02:33'),
(24, 4, 12, 25.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:09:56'),
(25, 4, 12, 25.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:10:19'),
(26, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:12:35'),
(27, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:12:50'),
(28, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:14:43'),
(29, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:15:23'),
(30, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:19:45'),
(31, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:20:47'),
(32, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:23:32'),
(33, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:24:40'),
(34, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:27:49'),
(35, 4, 12, 26.00, 40, 0, NULL, 4, '', '2025-07-23', '2025-07-23 12:30:30'),
(36, 1, 12, 2.00, 40, 1, 4, 1, '', '2025-07-24', '2025-07-24 03:34:41'),
(37, 1, 12, 10.00, 40, 1, 4, 1, '', '2025-07-24', '2025-07-24 03:35:09'),
(38, 1, 12, 40.00, 40, 1, 4, 1, '', '2025-07-24', '2025-07-24 03:38:24'),
(39, 1, 2, 20.00, 10, 1, 2, 1, '', '2025-07-24', '2025-07-24 03:39:49'),
(40, 1, 2, 20.00, 10, 1, 2, 1, '', '2025-07-24', '2025-07-24 03:40:01'),
(41, 1, 2, 7.00, 10, 1, 2, 1, '', '2025-07-24', '2025-07-24 03:47:21'),
(42, 1, 12, 12.00, 40, 1, 4, 1, '', '2025-07-24', '2025-07-24 03:47:39'),
(43, 1, 2, 16.00, 10, 1, 2, 1, '', '2025-07-24', '2025-07-24 03:50:33'),
(44, 1, 2, 15.00, 20, 1, 2, 1, '', '2025-07-24', '2025-07-24 03:53:25'),
(45, 1, 13, 19.00, 35, 0, NULL, 1, '', '2025-07-24', '2025-07-24 22:18:52'),
(46, 1, 12, 9.00, 40, 1, 4, 1, '', '2025-07-24', '2025-07-24 22:19:28'),
(47, 1, 15, 15.00, 15, 0, NULL, 1, '', '2025-07-27', '2025-07-27 13:23:54'),
(48, 1, 16, 2.00, 10, 0, NULL, 1, '', '2025-08-02', '2025-08-02 17:04:28'),
(49, 1, 8, 49.00, 50, 0, NULL, 1, '', '2025-08-09', '2025-08-09 20:23:12');

-- --------------------------------------------------------

--
-- Table structure for table `wp_drill_skills`
--

CREATE TABLE `wp_drill_skills` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_drill_skills`
--

INSERT INTO `wp_drill_skills` (`id`, `name`, `display_name`, `description`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'fundamentals', 'Fundamentals', 'Basic stance, grip, and shooting form', 1, 1, '2025-07-17 03:28:38'),
(2, 'cueball control', 'Cueball Control', 'Draw, follow, and english shots', 2, 1, '2025-07-17 03:28:38'),
(3, 'position play', 'Position Play', 'Planning and executing position for next shot', 3, 1, '2025-07-17 03:28:38'),
(4, 'pattern play', 'Pattern Play', 'Running multiple balls in sequence', 4, 1, '2025-07-17 03:28:38');

-- --------------------------------------------------------

--
-- Table structure for table `wp_drill_users`
--

CREATE TABLE `wp_drill_users` (
  `id` int NOT NULL,
  `wp_user_id` bigint UNSIGNED DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `user_type` enum('admin','coach','player') DEFAULT 'player',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `coach_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_drill_users`
--

INSERT INTO `wp_drill_users` (`id`, `wp_user_id`, `email`, `display_name`, `user_type`, `is_active`, `created_at`, `updated_at`, `coach_id`) VALUES
(1, NULL, 'shermanscs@gmail.com', 'Steve Sherman', 'admin', 1, '2025-07-17 03:28:38', '2025-07-19 14:34:45', NULL),
(2, NULL, 'admin', 'Administrator', 'admin', 1, '2025-07-17 03:28:38', '2025-08-21 02:27:23', NULL),
(3, NULL, 'shermanm352@gmail.com', 'Matt Thibedeau', 'player', 1, '2025-07-17 03:28:38', '2025-08-05 04:51:50', 5),
(4, NULL, 'steve@example.com', 'Steve Student', 'player', 1, '2025-07-17 03:28:38', '2025-08-05 04:51:50', 5),
(5, NULL, 'coach@example.com', 'Coach Smith', 'coach', 1, '2025-07-17 03:28:38', '2025-08-21 02:29:24', NULL),
(6, NULL, 'steve@coach.com', 'Steve Sherman', 'coach', 1, '2025-07-29 03:11:29', '2025-08-05 23:20:14', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wp_practice_sessions`
--

CREATE TABLE `wp_practice_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_name` varchar(255) DEFAULT NULL,
  `session_date` date DEFAULT (curdate()),
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_content`
--

CREATE TABLE `wp_training_content` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `content_type` enum('image','pdf','document','video','link','other') DEFAULT 'document',
  `category_id` int NOT NULL,
  `skill_id` int NOT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_url` varchar(500) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL COMMENT 'External URL for link content',
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `visibility` enum('public','private') DEFAULT 'private',
  `download_count` int DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `is_active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `credit_id` int DEFAULT NULL COMMENT 'Optional credit reference'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Training content materials including PDFs, images, and documents';

--
-- Dumping data for table `wp_training_content`
--

INSERT INTO `wp_training_content` (`id`, `name`, `description`, `content_type`, `category_id`, `skill_id`, `difficulty_level`, `original_filename`, `file_size`, `mime_type`, `file_url`, `external_url`, `thumbnail_url`, `visibility`, `download_count`, `created_by`, `is_active`, `created_at`, `updated_at`, `credit_id`) VALUES
(1, 'MS Core', 'MSCore describes the core fundamental bridges - Mechanical, Specialty, Closed, Open, Rail and Extended Rail bridges. test', 'image', 1, 1, 'beginner', 'Ms Core cartoon.jpg', 85555, '0', 'https://steveshermanbilliards.com/wp-content/uploads/training-content/content_1.jpg', NULL, 'https://steveshermanbilliards.com/wp-content/uploads/training-content/thumbnails/thumb_content_1.jpg', 'public', 0, 2, 1, '2025-08-01 21:36:48', '2025-08-21 01:39:22', 1),
(2, 'Break Physics', 'This document goes over the technical aspects of break physics', 'pdf', 2, 1, 'intermediate', 'Break Physics 2.pdf', 296794, '0', 'https://steveshermanbilliards.com/wp-content/uploads/training-content/content_2.pdf', NULL, NULL, 'public', 0, 2, 1, '2025-08-01 23:50:01', '2025-08-01 23:50:01', NULL),
(3, 'testing with url linked data v5', 'testing with url linked data v5 desc', 'link', 1, 1, 'beginner', NULL, NULL, NULL, NULL, 'https://youtu.be/GGm08omfgwA?si=YzgBlvFN0pwtuUqf', NULL, 'public', 0, 2, 1, '2025-08-02 16:52:01', '2025-08-02 16:52:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_programs`
--

CREATE TABLE `wp_training_programs` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category_id` int NOT NULL,
  `skill_id` int NOT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `estimated_duration_weeks` int DEFAULT NULL,
  `is_active` tinyint DEFAULT '1',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `credit_id` int DEFAULT NULL COMMENT 'Optional credit reference'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_training_programs`
--

INSERT INTO `wp_training_programs` (`id`, `name`, `description`, `category_id`, `skill_id`, `difficulty_level`, `estimated_duration_weeks`, `is_active`, `created_by`, `created_at`, `updated_at`, `credit_id`) VALUES
(1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, 1, '2025-07-30 19:10:37', '2025-08-19 18:27:26', 1),
(2, 'Intermediate Position Play Mastery', 'Focus on advanced cue ball control and position play techniques', 2, 3, 'intermediate', 12, 1, 1, '2025-07-30 19:10:37', '2025-08-19 21:36:29', 2),
(3, 'Advanced Competition Preparation', 'Intensive training program for competitive players', 3, 4, 'advanced', 16, 1, 1, '2025-07-30 19:10:37', '2025-07-30 19:10:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_program_assigned`
--

CREATE TABLE `wp_training_program_assigned` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL COMMENT 'References wp_training_program_assignments.id',
  `user_id` int NOT NULL COMMENT 'Student assigned to this program',
  `original_program_id` int NOT NULL COMMENT 'Original template program ID for reference',
  `name` varchar(255) NOT NULL COMMENT 'Program name at time of assignment',
  `description` text COMMENT 'Program description at time of assignment',
  `category_id` int NOT NULL COMMENT 'Category at time of assignment',
  `skill_id` int NOT NULL COMMENT 'Skill at time of assignment',
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `estimated_duration_weeks` int DEFAULT NULL,
  `created_by` int NOT NULL COMMENT 'Who assigned this program',
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the program was assigned',
  `is_active` tinyint DEFAULT '1' COMMENT 'Whether this assignment is active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Student-specific program snapshots at assignment time';

--
-- Dumping data for table `wp_training_program_assigned`
--

INSERT INTO `wp_training_program_assigned` (`id`, `assignment_id`, `user_id`, `original_program_id`, `name`, `description`, `category_id`, `skill_id`, `difficulty_level`, `estimated_duration_weeks`, `created_by`, `assigned_at`, `is_active`, `created_at`, `updated_at`) VALUES
(8, 18, 5, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-14 22:36:23', 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(9, 19, 6, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-14 22:36:59', 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(10, 20, 3, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-14 22:42:10', 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(11, 21, 1, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-15 21:11:31', 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(12, 22, 4, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-16 00:16:13', 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(13, 23, 4, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-16 00:55:10', 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(14, 24, 4, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-16 13:20:06', 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(15, 25, 4, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-16 16:27:57', 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(16, 26, 4, 1, 'Beginner Foundation Program', 'A comprehensive 8-week program covering all fundamental skills for new players', 1, 1, 'beginner', 8, 1, '2025-08-16 17:21:00', 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_program_assignments`
--

CREATE TABLE `wp_training_program_assignments` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `user_id` int NOT NULL,
  `assigned_by` int NOT NULL,
  `assigned_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `due_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `completion_date` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) DEFAULT '0.00',
  `current_unit_id` int DEFAULT NULL,
  `status` enum('not_started','in_progress','completed','paused') DEFAULT 'not_started',
  `notes` text,
  `is_active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `assignment_sequence` int NOT NULL DEFAULT '1' COMMENT 'Sequence number for multiple assignments of same program to same user'
) ;

--
-- Dumping data for table `wp_training_program_assignments`
--

INSERT INTO `wp_training_program_assignments` (`id`, `program_id`, `user_id`, `assigned_by`, `assigned_date`, `due_date`, `start_date`, `completion_date`, `progress_percentage`, `current_unit_id`, `status`, `notes`, `is_active`, `created_at`, `updated_at`, `assignment_sequence`) VALUES
(1, 1, 4, 5, '2025-08-06 23:00:56', NULL, '2025-08-06', NULL, 0.00, NULL, 'not_started', '', 0, '2025-08-06 23:00:56', '2025-08-14 20:59:48', 1),
(2, 1, 1, 1, '2025-08-14 21:36:48', NULL, '2025-01-15', NULL, 0.00, NULL, 'not_started', 'Legacy test assignment - no snapshot', 1, '2025-08-14 21:36:48', '2025-08-14 21:36:48', 1),
(4, 1, 4, 1, '2025-08-14 22:12:58', NULL, '2025-01-15', NULL, 0.00, NULL, 'not_started', 'Legacy test assignment - no snapshot', 0, '2025-08-14 22:12:58', '2025-08-15 23:54:22', 2),
(18, 1, 5, 1, '2025-08-14 22:36:23', NULL, '2025-01-15', NULL, 0.00, NULL, 'not_started', 'Snapshot test assignment - unlocked mode', 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47', 1),
(19, 1, 6, 1, '2025-08-14 22:36:59', NULL, '2025-01-15', NULL, 0.00, NULL, 'not_started', 'Snapshot test assignment - progressive mode', 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59', 1),
(20, 1, 3, 1, '2025-08-14 22:42:10', NULL, '2025-01-15', NULL, 0.00, NULL, 'not_started', 'Snapshot test assignment - locked mode', 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10', 1),
(21, 1, 1, 1, '2025-08-15 21:11:31', NULL, '2025-08-15', NULL, 0.00, NULL, 'not_started', 'Test sequence assignment #2', 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31', 2),
(22, 1, 4, 5, '2025-08-16 00:16:13', NULL, '2025-08-16', NULL, 0.00, NULL, 'not_started', 'Snapshot-based assignment with unlocked unit access mode', 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37', 3),
(23, 1, 4, 5, '2025-08-16 00:55:10', NULL, '2025-08-16', NULL, 0.00, NULL, 'not_started', 'Snapshot-based assignment with unlocked unit access mode', 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20', 4),
(24, 1, 4, 5, '2025-08-16 13:20:06', NULL, '2025-08-16', NULL, 0.00, NULL, 'not_started', 'Snapshot-based assignment with unlocked unit access mode', 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46', 5),
(25, 1, 4, 5, '2025-08-16 16:27:57', NULL, '2025-08-16', NULL, 0.00, NULL, 'not_started', 'Snapshot-based assignment with unlocked unit access mode', 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50', 6),
(26, 1, 4, 5, '2025-08-16 17:21:00', NULL, '2025-08-16', NULL, 0.00, NULL, 'not_started', 'Snapshot-based assignment with progressive unit access mode', 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00', 7);

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_program_content`
--

CREATE TABLE `wp_training_program_content` (
  `id` int NOT NULL,
  `unit_id` int NOT NULL COMMENT 'References wp_training_program_units.id',
  `content_type` enum('drill','instruction','video','document','assignment','training_content','other') DEFAULT 'drill',
  `drill_id` int DEFAULT NULL COMMENT 'References wp_drills.id when content_type is drill',
  `content_id` int DEFAULT NULL,
  `content_title` varchar(255) DEFAULT NULL COMMENT 'Title for non-drill content',
  `content_description` text COMMENT 'Description or instructions for the content',
  `content_data` longtext COMMENT 'JSON data for instructional content, video URLs, etc.',
  `content_order` int NOT NULL DEFAULT '1' COMMENT 'Order within the unit',
  `is_required` tinyint DEFAULT '1' COMMENT '1 if required, 0 if optional',
  `estimated_duration_minutes` int DEFAULT NULL COMMENT 'Estimated time to complete',
  `points_possible` int DEFAULT NULL COMMENT 'Points possible for this content item',
  `created_by` int DEFAULT NULL COMMENT 'User who created this assignment',
  `is_active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Content assignments for training program units';

--
-- Dumping data for table `wp_training_program_content`
--

INSERT INTO `wp_training_program_content` (`id`, `unit_id`, `content_type`, `drill_id`, `content_id`, `content_title`, `content_description`, `content_data`, `content_order`, `is_required`, `estimated_duration_minutes`, `points_possible`, `created_by`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 1, 1, '2025-07-31 13:57:42', '2025-07-31 13:57:42'),
(2, 1, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 1, 1, '2025-07-31 13:57:42', '2025-07-31 13:57:42'),
(3, 1, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 1, 1, '2025-07-31 13:57:42', '2025-07-31 13:57:42'),
(4, 1, 'training_content', NULL, NULL, 'Stance and Bridge Setup', 'Learn the fundamentals of proper stance and bridge formation for consistent shooting.', NULL, 1, 1, 15, NULL, 1, 0, '2025-07-31 13:58:04', '2025-08-05 00:11:31'),
(5, 1, 'training_content', NULL, NULL, 'Cue Ball Control Basics', 'Watch this video to understand the fundamentals of cue ball control.', NULL, 2, 1, 25, NULL, 1, 0, '2025-07-31 13:58:04', '2025-08-05 00:11:39'),
(6, 2, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 1, 1, '2025-07-31 22:08:23', '2025-07-31 22:08:23'),
(7, 2, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 1, 1, '2025-08-04 17:00:17', '2025-08-04 17:00:17'),
(8, 2, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 1, 1, '2025-08-04 23:55:01', '2025-08-04 23:55:01'),
(9, 2, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 1, 1, '2025-08-05 00:10:41', '2025-08-05 00:10:41');

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_program_content_assigned`
--

CREATE TABLE `wp_training_program_content_assigned` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL COMMENT 'References wp_training_program_assignments.id',
  `assigned_unit_id` int NOT NULL COMMENT 'References wp_training_program_units_assigned.id',
  `original_content_id` int DEFAULT NULL COMMENT 'Original template content ID (can be NULL for custom content)',
  `content_type` enum('drill','instruction','video','document','assignment','training_content','other') DEFAULT 'drill',
  `drill_id` int DEFAULT NULL COMMENT 'References wp_drills.id when content_type is drill',
  `content_id` int DEFAULT NULL COMMENT 'References wp_training_content.id',
  `content_title` varchar(255) DEFAULT NULL,
  `content_description` text,
  `content_data` longtext COMMENT 'JSON data for instructional content, video URLs, etc.',
  `content_order` int NOT NULL DEFAULT '1',
  `is_required` tinyint DEFAULT '1',
  `estimated_duration_minutes` int DEFAULT NULL,
  `points_possible` int DEFAULT NULL,
  `is_active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Student-specific content assignments (snapshots of template content)';

--
-- Dumping data for table `wp_training_program_content_assigned`
--

INSERT INTO `wp_training_program_content_assigned` (`id`, `assignment_id`, `assigned_unit_id`, `original_content_id`, `content_type`, `drill_id`, `content_id`, `content_title`, `content_description`, `content_data`, `content_order`, `is_required`, `estimated_duration_minutes`, `points_possible`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 18, 43, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(2, 18, 43, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(3, 18, 43, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(4, 18, 44, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(5, 18, 44, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(6, 18, 44, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(7, 18, 44, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(8, 19, 49, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(9, 19, 49, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(10, 19, 49, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(11, 19, 50, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(12, 19, 50, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(13, 19, 50, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(14, 19, 50, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(15, 20, 55, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(16, 20, 55, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(17, 20, 55, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(18, 20, 56, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(19, 20, 56, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(20, 20, 56, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(21, 20, 56, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(22, 21, 61, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(23, 21, 61, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(24, 21, 61, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(25, 21, 62, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(26, 21, 62, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(27, 21, 62, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(28, 21, 62, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(29, 22, 67, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(30, 22, 67, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(31, 22, 67, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(32, 22, 68, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(33, 22, 68, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(34, 22, 68, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(35, 22, 68, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(36, 23, 73, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(37, 23, 73, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(38, 23, 73, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(39, 23, 74, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(40, 23, 74, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(41, 23, 74, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(42, 23, 74, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(43, 24, 79, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(44, 24, 79, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(45, 24, 79, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(46, 24, 80, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(47, 24, 80, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(48, 24, 80, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(49, 24, 80, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(50, 25, 85, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(51, 25, 85, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(52, 25, 85, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(53, 25, 86, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(54, 25, 86, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(55, 25, 86, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(56, 25, 86, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(57, 26, 91, 1, 'drill', 8, NULL, NULL, NULL, NULL, 1, 1, 30, 50, 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(58, 26, 91, 2, 'drill', 12, NULL, NULL, NULL, NULL, 2, 1, 45, 40, 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(59, 26, 91, 3, 'drill', 15, NULL, NULL, NULL, NULL, 3, 1, 20, 15, 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(60, 26, 92, 6, 'drill', 15, NULL, '', '', NULL, 1, 1, NULL, 15, 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(61, 26, 92, 7, 'drill', 8, NULL, NULL, NULL, NULL, 2, 1, NULL, 50, 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(62, 26, 92, 8, 'training_content', NULL, 1, NULL, NULL, NULL, 3, 1, NULL, NULL, 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(63, 26, 92, 9, 'training_content', NULL, 2, NULL, NULL, NULL, 4, 1, NULL, NULL, 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00');

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_program_progress`
--

CREATE TABLE `wp_training_program_progress` (
  `id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'References wp_drill_users.id',
  `program_id` int NOT NULL COMMENT 'References wp_training_programs.id',
  `unit_id` int NOT NULL COMMENT 'References wp_training_program_units.id',
  `content_id` int NOT NULL COMMENT 'References wp_training_program_content.id',
  `status` enum('not_started','in_progress','completed','skipped') DEFAULT 'not_started',
  `score_achieved` decimal(8,2) DEFAULT NULL COMMENT 'Score achieved for drill content',
  `max_possible_score` int DEFAULT NULL COMMENT 'Maximum possible score',
  `attempts_count` int DEFAULT '0' COMMENT 'Number of attempts made',
  `time_spent_minutes` int DEFAULT NULL COMMENT 'Time spent on this content',
  `completion_date` timestamp NULL DEFAULT NULL,
  `notes` text COMMENT 'Student or instructor notes',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `unit_assigned_id` int DEFAULT NULL COMMENT 'References wp_training_program_units_assigned.id (for new snapshot model)',
  `content_assigned_id` int DEFAULT NULL COMMENT 'References wp_training_program_content_assigned.id (for new snapshot model)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='User progress tracking for training program content';

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_program_units`
--

CREATE TABLE `wp_training_program_units` (
  `id` int NOT NULL,
  `program_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `unit_order` int DEFAULT '1',
  `estimated_duration_days` int DEFAULT NULL,
  `is_active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `wp_training_program_units`
--

INSERT INTO `wp_training_program_units` (`id`, `program_id`, `name`, `description`, `unit_order`, `estimated_duration_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 1, '2025-07-30 19:10:58', '2025-07-30 19:10:58'),
(2, 1, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 1, '2025-07-30 19:10:58', '2025-07-30 19:10:58'),
(3, 1, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 1, '2025-07-30 19:10:58', '2025-07-30 19:10:58'),
(4, 1, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 1, '2025-07-30 19:10:58', '2025-07-30 19:10:58'),
(5, 1, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 1, '2025-07-30 19:10:58', '2025-07-30 19:10:58'),
(6, 1, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 1, '2025-07-30 19:10:58', '2025-07-30 19:10:58'),
(7, 2, 'Advanced Draw Techniques', 'Master various draw shot applications', 1, 10, 1, '2025-07-30 19:11:18', '2025-07-30 19:11:18'),
(8, 2, 'Follow Shot Precision', 'Perfect follow shot control and speed', 2, 10, 1, '2025-07-30 19:11:18', '2025-07-30 19:11:18'),
(9, 2, 'English and Spin Control', 'Advanced use of side spin and english', 3, 14, 1, '2025-07-30 19:11:18', '2025-07-30 19:11:18'),
(10, 2, 'Multi-Ball Position Planning', 'Think 3-5 balls ahead consistently', 4, 21, 1, '2025-07-30 19:11:18', '2025-07-30 19:11:18'),
(11, 2, 'Pattern Play Excellence', 'Execute complex patterns with confidence', 5, 21, 1, '2025-07-30 19:11:18', '2025-07-30 19:11:18'),
(12, 2, 'Pressure Situation Management', 'Maintain form under competitive pressure', 6, 14, 1, '2025-07-30 19:11:18', '2025-07-30 19:11:18');

-- --------------------------------------------------------

--
-- Table structure for table `wp_training_program_units_assigned`
--

CREATE TABLE `wp_training_program_units_assigned` (
  `id` int NOT NULL,
  `assignment_id` int NOT NULL COMMENT 'References wp_training_program_assignments.id',
  `original_unit_id` int DEFAULT NULL COMMENT 'Original template unit ID (can be NULL for custom units)',
  `name` varchar(255) NOT NULL,
  `description` text,
  `unit_order` int DEFAULT '1',
  `estimated_duration_days` int DEFAULT NULL,
  `is_locked` tinyint DEFAULT '0' COMMENT 'Whether this specific unit is locked for the student',
  `unlocked_by` int DEFAULT NULL COMMENT 'Coach who unlocked this unit',
  `unlocked_date` timestamp NULL DEFAULT NULL COMMENT 'When this unit was unlocked',
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When unit was assigned',
  `is_active` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Student-specific unit assignments with individual lock status';

--
-- Dumping data for table `wp_training_program_units_assigned`
--

INSERT INTO `wp_training_program_units_assigned` (`id`, `assignment_id`, `original_unit_id`, `name`, `description`, `unit_order`, `estimated_duration_days`, `is_locked`, `unlocked_by`, `unlocked_date`, `assigned_at`, `is_active`, `created_at`, `updated_at`) VALUES
(43, 18, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 0, NULL, NULL, '2025-08-14 22:36:23', 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(44, 18, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 0, NULL, NULL, '2025-08-14 22:36:23', 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(45, 18, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 0, NULL, NULL, '2025-08-14 22:36:23', 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(46, 18, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 0, NULL, NULL, '2025-08-14 22:36:23', 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(47, 18, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 0, NULL, NULL, '2025-08-14 22:36:23', 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(48, 18, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 0, NULL, NULL, '2025-08-14 22:36:23', 0, '2025-08-14 22:36:23', '2025-08-14 22:46:47'),
(49, 19, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 0, NULL, NULL, '2025-08-14 22:36:59', 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(50, 19, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 1, NULL, NULL, '2025-08-14 22:36:59', 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(51, 19, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 1, NULL, NULL, '2025-08-14 22:36:59', 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(52, 19, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 1, NULL, NULL, '2025-08-14 22:36:59', 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(53, 19, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 1, NULL, NULL, '2025-08-14 22:36:59', 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(54, 19, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 1, NULL, NULL, '2025-08-14 22:36:59', 1, '2025-08-14 22:36:59', '2025-08-14 22:36:59'),
(55, 20, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 1, NULL, NULL, '2025-08-14 22:42:10', 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(56, 20, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 1, NULL, NULL, '2025-08-14 22:42:10', 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(57, 20, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 1, NULL, NULL, '2025-08-14 22:42:10', 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(58, 20, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 1, NULL, NULL, '2025-08-14 22:42:10', 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(59, 20, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 1, NULL, NULL, '2025-08-14 22:42:10', 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(60, 20, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 1, NULL, NULL, '2025-08-14 22:42:10', 1, '2025-08-14 22:42:10', '2025-08-14 22:42:10'),
(61, 21, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 0, NULL, NULL, '2025-08-15 21:11:31', 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(62, 21, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 1, NULL, NULL, '2025-08-15 21:11:31', 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(63, 21, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 1, NULL, NULL, '2025-08-15 21:11:31', 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(64, 21, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 1, NULL, NULL, '2025-08-15 21:11:31', 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(65, 21, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 1, NULL, NULL, '2025-08-15 21:11:31', 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(66, 21, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 1, NULL, NULL, '2025-08-15 21:11:31', 1, '2025-08-15 21:11:31', '2025-08-15 21:11:31'),
(67, 22, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 0, NULL, NULL, '2025-08-16 00:16:13', 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(68, 22, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 0, NULL, NULL, '2025-08-16 00:16:13', 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(69, 22, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 0, NULL, NULL, '2025-08-16 00:16:13', 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(70, 22, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 0, NULL, NULL, '2025-08-16 00:16:13', 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(71, 22, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 0, NULL, NULL, '2025-08-16 00:16:13', 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(72, 22, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 0, NULL, NULL, '2025-08-16 00:16:13', 0, '2025-08-16 00:16:13', '2025-08-16 00:16:37'),
(73, 23, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 0, NULL, NULL, '2025-08-16 00:55:10', 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(74, 23, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 0, NULL, NULL, '2025-08-16 00:55:10', 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(75, 23, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 0, NULL, NULL, '2025-08-16 00:55:10', 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(76, 23, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 0, NULL, NULL, '2025-08-16 00:55:10', 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(77, 23, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 0, NULL, NULL, '2025-08-16 00:55:10', 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(78, 23, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 0, NULL, NULL, '2025-08-16 00:55:10', 0, '2025-08-16 00:55:10', '2025-08-16 00:55:20'),
(79, 24, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 0, NULL, NULL, '2025-08-16 13:20:06', 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(80, 24, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 0, NULL, NULL, '2025-08-16 13:20:06', 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(81, 24, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 0, NULL, NULL, '2025-08-16 13:20:06', 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(82, 24, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 0, NULL, NULL, '2025-08-16 13:20:06', 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(83, 24, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 0, NULL, NULL, '2025-08-16 13:20:06', 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(84, 24, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 0, NULL, NULL, '2025-08-16 13:20:06', 0, '2025-08-16 13:20:06', '2025-08-16 16:27:46'),
(85, 25, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 0, NULL, NULL, '2025-08-16 16:27:57', 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(86, 25, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 0, NULL, NULL, '2025-08-16 16:27:57', 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(87, 25, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 0, NULL, NULL, '2025-08-16 16:27:57', 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(88, 25, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 0, NULL, NULL, '2025-08-16 16:27:57', 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(89, 25, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 0, NULL, NULL, '2025-08-16 16:27:57', 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(90, 25, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 0, NULL, NULL, '2025-08-16 16:27:57', 0, '2025-08-16 16:27:57', '2025-08-16 17:20:50'),
(91, 26, 1, 'Stance and Grip Fundamentals', 'Master the basic stance, grip, and bridge formation', 1, 7, 0, NULL, NULL, '2025-08-16 17:21:00', 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(92, 26, 2, 'Straight Shot Mastery', 'Develop accuracy with straight shots and basic aim', 2, 7, 1, NULL, NULL, '2025-08-16 17:21:00', 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(93, 26, 3, 'Basic Cue Ball Control', 'Introduction to stop shots, draw, and follow', 3, 14, 1, NULL, NULL, '2025-08-16 17:21:00', 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(94, 26, 4, 'Simple Position Play', 'Learn to think one ball ahead', 4, 14, 1, NULL, NULL, '2025-08-16 17:21:00', 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(95, 26, 5, 'Pattern Recognition', 'Identify and execute basic patterns', 5, 14, 1, NULL, NULL, '2025-08-16 17:21:00', 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00'),
(96, 26, 6, 'Game Strategy Basics', 'Understand basic 8-ball and 9-ball strategy', 6, 14, 1, NULL, NULL, '2025-08-16 17:21:00', 1, '2025-08-16 17:21:00', '2025-08-16 17:21:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wp_admin_passwords`
--
ALTER TABLE `wp_admin_passwords`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `wp_challenge_events`
--
ALTER TABLE `wp_challenge_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_events_drill_id` (`drill_id`),
  ADD KEY `idx_events_scoring_method_id` (`scoring_method_id`),
  ADD KEY `idx_events_created_by` (`created_by`),
  ADD KEY `idx_events_status_date` (`status`,`start_date`,`end_date`),
  ADD KEY `idx_events_series_name` (`series_name`);

--
-- Indexes for table `wp_challenge_participants`
--
ALTER TABLE `wp_challenge_participants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_participant` (`challenge_event_id`,`user_id`,`is_active`),
  ADD KEY `idx_participants_event_id` (`challenge_event_id`),
  ADD KEY `idx_participants_user_id` (`user_id`),
  ADD KEY `idx_participants_enrolled_by` (`enrolled_by`);

--
-- Indexes for table `wp_challenge_scores`
--
ALTER TABLE `wp_challenge_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scores_event_id` (`challenge_event_id`),
  ADD KEY `idx_scores_user_id` (`user_id`),
  ADD KEY `idx_scores_drill_id` (`drill_id`),
  ADD KEY `idx_scores_submitted_by` (`submitted_by`),
  ADD KEY `idx_scores_event_user` (`challenge_event_id`,`user_id`),
  ADD KEY `idx_scores_practice_date` (`practice_date`),
  ADD KEY `idx_scores_percentage` (`percentage`);

--
-- Indexes for table `wp_challenge_scoring_methods`
--
ALTER TABLE `wp_challenge_scoring_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_scoring_method_name` (`name`);

--
-- Indexes for table `wp_coach_passwords`
--
ALTER TABLE `wp_coach_passwords`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coach_id` (`coach_id`);

--
-- Indexes for table `wp_credit_to`
--
ALTER TABLE `wp_credit_to`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_credit_created_by` (`created_by`),
  ADD KEY `idx_credit_organization_name` (`organization_name`),
  ADD KEY `idx_credit_created_at` (`created_at`),
  ADD KEY `idx_credit_active` (`is_active`);

--
-- Indexes for table `wp_diagrams`
--
ALTER TABLE `wp_diagrams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diagrams_active` (`is_active`),
  ADD KEY `idx_diagrams_name` (`name`),
  ADD KEY `fk_diagrams_created_by` (`created_by`),
  ADD KEY `idx_diagrams_type` (`diagram_type`),
  ADD KEY `idx_diagrams_visibility` (`visibility`),
  ADD KEY `idx_diagrams_vector_data` (`vector_data`(255)),
  ADD KEY `idx_diagrams_is_vector` (`is_vector`),
  ADD KEY `idx_diagrams_credit_id` (`credit_id`);

--
-- Indexes for table `wp_drills`
--
ALTER TABLE `wp_drills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `skill_id` (`skill_id`),
  ADD KEY `name` (`name`),
  ADD KEY `idx_drills_category_skill` (`category_id`,`skill_id`,`is_active`),
  ADD KEY `idx_drills_diagram_id` (`diagram_id`),
  ADD KEY `idx_drills_credit_id` (`credit_id`);

--
-- Indexes for table `wp_drill_assignments`
--
ALTER TABLE `wp_drill_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment` (`user_id`,`drill_id`,`is_active`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `drill_id` (`drill_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_assignments_user_active` (`user_id`,`is_active`);

--
-- Indexes for table `wp_drill_categories`
--
ALTER TABLE `wp_drill_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `wp_drill_journal`
--
ALTER TABLE `wp_drill_journal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `wp_drill_scores`
--
ALTER TABLE `wp_drill_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `drill_id` (`drill_id`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `practice_date` (`practice_date`),
  ADD KEY `percentage` (`percentage`),
  ADD KEY `idx_scores_user_date` (`user_id`,`practice_date`),
  ADD KEY `idx_scores_drill_date` (`drill_id`,`practice_date`);

--
-- Indexes for table `wp_drill_skills`
--
ALTER TABLE `wp_drill_skills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `wp_drill_users`
--
ALTER TABLE `wp_drill_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `wp_user_id` (`wp_user_id`),
  ADD KEY `idx_coach_id` (`coach_id`);

--
-- Indexes for table `wp_practice_sessions`
--
ALTER TABLE `wp_practice_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_date` (`session_date`);

--
-- Indexes for table `wp_training_content`
--
ALTER TABLE `wp_training_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content_active` (`is_active`),
  ADD KEY `idx_content_name` (`name`),
  ADD KEY `idx_content_type` (`content_type`),
  ADD KEY `idx_content_category` (`category_id`),
  ADD KEY `idx_content_skill` (`skill_id`),
  ADD KEY `idx_content_difficulty` (`difficulty_level`),
  ADD KEY `idx_content_visibility` (`visibility`),
  ADD KEY `fk_content_created_by` (`created_by`),
  ADD KEY `idx_content_external_url` (`external_url`),
  ADD KEY `idx_training_content_credit_id` (`credit_id`);

--
-- Indexes for table `wp_training_programs`
--
ALTER TABLE `wp_training_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_training_programs_category` (`category_id`),
  ADD KEY `idx_training_programs_skill` (`skill_id`),
  ADD KEY `idx_training_programs_created_by` (`created_by`),
  ADD KEY `idx_training_programs_active` (`is_active`),
  ADD KEY `idx_training_programs_credit_id` (`credit_id`);

--
-- Indexes for table `wp_training_program_assigned`
--
ALTER TABLE `wp_training_program_assigned`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment_program` (`assignment_id`),
  ADD KEY `idx_assigned_assignment` (`assignment_id`),
  ADD KEY `idx_assigned_original_program` (`original_program_id`),
  ADD KEY `idx_assigned_category` (`category_id`),
  ADD KEY `idx_assigned_skill` (`skill_id`),
  ADD KEY `fk_assigned_created_by` (`created_by`),
  ADD KEY `idx_assigned_program_user` (`user_id`),
  ADD KEY `idx_assigned_program_active` (`is_active`);

--
-- Indexes for table `wp_training_program_assignments`
--
ALTER TABLE `wp_training_program_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_program_user_sequence` (`program_id`,`user_id`,`assignment_sequence`) COMMENT 'Ensures unique sequence numbers per user/program combination',
  ADD KEY `idx_program_assignments_program` (`program_id`),
  ADD KEY `idx_program_assignments_user` (`user_id`),
  ADD KEY `idx_program_assignments_assigned_by` (`assigned_by`),
  ADD KEY `idx_program_assignments_status` (`status`),
  ADD KEY `idx_program_assignments_current_unit` (`current_unit_id`),
  ADD KEY `idx_user_program_active` (`user_id`,`program_id`,`is_active`) COMMENT 'Quick lookup for active assignments per user/program',
  ADD KEY `idx_user_program_sequence` (`user_id`,`program_id`,`assignment_sequence` DESC) COMMENT 'Quick lookup for latest assignment sequence';

--
-- Indexes for table `wp_training_program_content`
--
ALTER TABLE `wp_training_program_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content_unit_id` (`unit_id`),
  ADD KEY `idx_content_drill_id` (`drill_id`),
  ADD KEY `idx_content_created_by` (`created_by`),
  ADD KEY `idx_content_type` (`content_type`),
  ADD KEY `idx_content_order` (`content_order`),
  ADD KEY `idx_content_active` (`is_active`),
  ADD KEY `idx_content_unit_order` (`unit_id`,`content_order`,`is_active`),
  ADD KEY `idx_content_unit_type_order` (`unit_id`,`content_type`,`content_order`),
  ADD KEY `idx_training_content_id` (`content_id`);

--
-- Indexes for table `wp_training_program_content_assigned`
--
ALTER TABLE `wp_training_program_content_assigned`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_content_assigned_assignment` (`assignment_id`),
  ADD KEY `idx_content_assigned_unit` (`assigned_unit_id`),
  ADD KEY `idx_content_assigned_original` (`original_content_id`),
  ADD KEY `idx_content_assigned_drill` (`drill_id`),
  ADD KEY `idx_content_assigned_training_content` (`content_id`),
  ADD KEY `idx_content_assigned_order` (`assigned_unit_id`,`content_order`),
  ADD KEY `idx_content_assigned_type` (`content_type`),
  ADD KEY `idx_content_assigned_unit_order` (`assigned_unit_id`,`content_order`,`is_active`);

--
-- Indexes for table `wp_training_program_progress`
--
ALTER TABLE `wp_training_program_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_content_progress` (`user_id`,`content_id`),
  ADD KEY `idx_progress_user_id` (`user_id`),
  ADD KEY `idx_progress_program_id` (`program_id`),
  ADD KEY `idx_progress_unit_id` (`unit_id`),
  ADD KEY `idx_progress_content_id` (`content_id`),
  ADD KEY `idx_progress_status` (`status`),
  ADD KEY `idx_progress_user_program` (`user_id`,`program_id`,`status`),
  ADD KEY `idx_progress_completion_date` (`completion_date`),
  ADD KEY `idx_progress_user_status` (`user_id`,`status`),
  ADD KEY `idx_progress_unit_assigned` (`unit_assigned_id`),
  ADD KEY `idx_progress_content_assigned` (`content_assigned_id`);

--
-- Indexes for table `wp_training_program_units`
--
ALTER TABLE `wp_training_program_units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_training_units_program` (`program_id`),
  ADD KEY `idx_training_units_order` (`program_id`,`unit_order`),
  ADD KEY `idx_training_units_active` (`is_active`);

--
-- Indexes for table `wp_training_program_units_assigned`
--
ALTER TABLE `wp_training_program_units_assigned`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assignment_unit_order` (`assignment_id`,`unit_order`),
  ADD KEY `idx_unit_assigned_assignment` (`assignment_id`),
  ADD KEY `idx_unit_assigned_original` (`original_unit_id`),
  ADD KEY `idx_unit_assigned_order` (`assignment_id`,`unit_order`),
  ADD KEY `idx_unit_assigned_locked` (`assignment_id`,`is_locked`),
  ADD KEY `idx_unit_assigned_unlocked_by` (`unlocked_by`),
  ADD KEY `idx_unit_assigned_accessibility` (`assignment_id`,`is_locked`,`unit_order`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `wp_admin_passwords`
--
ALTER TABLE `wp_admin_passwords`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wp_challenge_events`
--
ALTER TABLE `wp_challenge_events`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wp_challenge_participants`
--
ALTER TABLE `wp_challenge_participants`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `wp_challenge_scores`
--
ALTER TABLE `wp_challenge_scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_challenge_scoring_methods`
--
ALTER TABLE `wp_challenge_scoring_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wp_coach_passwords`
--
ALTER TABLE `wp_coach_passwords`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wp_credit_to`
--
ALTER TABLE `wp_credit_to`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wp_diagrams`
--
ALTER TABLE `wp_diagrams`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wp_drills`
--
ALTER TABLE `wp_drills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `wp_drill_assignments`
--
ALTER TABLE `wp_drill_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `wp_drill_categories`
--
ALTER TABLE `wp_drill_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wp_drill_journal`
--
ALTER TABLE `wp_drill_journal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wp_drill_scores`
--
ALTER TABLE `wp_drill_scores`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `wp_drill_skills`
--
ALTER TABLE `wp_drill_skills`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `wp_drill_users`
--
ALTER TABLE `wp_drill_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wp_practice_sessions`
--
ALTER TABLE `wp_practice_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_training_content`
--
ALTER TABLE `wp_training_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wp_training_programs`
--
ALTER TABLE `wp_training_programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wp_training_program_assigned`
--
ALTER TABLE `wp_training_program_assigned`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `wp_training_program_assignments`
--
ALTER TABLE `wp_training_program_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_training_program_content`
--
ALTER TABLE `wp_training_program_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wp_training_program_content_assigned`
--
ALTER TABLE `wp_training_program_content_assigned`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `wp_training_program_progress`
--
ALTER TABLE `wp_training_program_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wp_training_program_units`
--
ALTER TABLE `wp_training_program_units`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `wp_training_program_units_assigned`
--
ALTER TABLE `wp_training_program_units_assigned`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

-- --------------------------------------------------------

--
-- Structure for view `vw_unit_content_with_details`
--
DROP TABLE IF EXISTS `vw_unit_content_with_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_unit_content_with_details`  AS SELECT `tpc`.`id` AS `id`, `tpc`.`unit_id` AS `unit_id`, `tpc`.`content_type` AS `content_type`, `tpc`.`drill_id` AS `drill_id`, `tpc`.`content_title` AS `content_title`, `tpc`.`content_description` AS `content_description`, `tpc`.`content_data` AS `content_data`, `tpc`.`content_order` AS `content_order`, `tpc`.`is_required` AS `is_required`, `tpc`.`estimated_duration_minutes` AS `estimated_duration_minutes`, `tpc`.`points_possible` AS `points_possible`, `tpc`.`created_by` AS `created_by`, `tpc`.`is_active` AS `is_active`, `tpc`.`created_at` AS `created_at`, `tpc`.`updated_at` AS `updated_at`, `tpu`.`name` AS `unit_name`, `tpu`.`description` AS `unit_description`, `tp`.`name` AS `program_name`, `d`.`name` AS `drill_name`, `d`.`description` AS `drill_description`, `d`.`max_score` AS `drill_max_score`, `d`.`difficulty_rating` AS `drill_difficulty`, `dc`.`display_name` AS `drill_category`, `ds`.`display_name` AS `drill_skill`, `u`.`display_name` AS `created_by_name` FROM ((((((`wp_training_program_content` `tpc` join `wp_training_program_units` `tpu` on((`tpc`.`unit_id` = `tpu`.`id`))) join `wp_training_programs` `tp` on((`tpu`.`program_id` = `tp`.`id`))) left join `wp_drills` `d` on(((`tpc`.`drill_id` = `d`.`id`) and (`tpc`.`content_type` = 'drill')))) left join `wp_drill_categories` `dc` on((`d`.`category_id` = `dc`.`id`))) left join `wp_drill_skills` `ds` on((`d`.`skill_id` = `ds`.`id`))) left join `wp_drill_users` `u` on((`tpc`.`created_by` = `u`.`id`))) WHERE ((`tpc`.`is_active` = 1) AND (`tpu`.`is_active` = 1) AND (`tp`.`is_active` = 1)) ORDER BY `tpc`.`unit_id` ASC, `tpc`.`content_order` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_program_progress`
--
DROP TABLE IF EXISTS `vw_user_program_progress`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_user_program_progress`  AS SELECT `p`.`user_id` AS `user_id`, `p`.`program_id` AS `program_id`, `tp`.`name` AS `program_name`, `u`.`display_name` AS `user_name`, count(`p`.`id`) AS `total_content_items`, count((case when (`p`.`status` = 'completed') then 1 end)) AS `completed_items`, count((case when (`p`.`status` = 'in_progress') then 1 end)) AS `in_progress_items`, count((case when (`p`.`status` = 'not_started') then 1 end)) AS `not_started_items`, round(((count((case when (`p`.`status` = 'completed') then 1 end)) * 100.0) / count(`p`.`id`)),2) AS `completion_percentage`, avg((case when ((`p`.`score_achieved` is not null) and (`p`.`max_possible_score` > 0)) then ((`p`.`score_achieved` / `p`.`max_possible_score`) * 100) end)) AS `average_score_percentage`, sum(`p`.`time_spent_minutes`) AS `total_time_spent_minutes`, max(`p`.`updated_at`) AS `last_activity_date` FROM ((`wp_training_program_progress` `p` join `wp_training_programs` `tp` on((`p`.`program_id` = `tp`.`id`))) join `wp_drill_users` `u` on((`p`.`user_id` = `u`.`id`))) WHERE ((`tp`.`is_active` = 1) AND (`u`.`is_active` = 1)) GROUP BY `p`.`user_id`, `p`.`program_id`, `tp`.`name`, `u`.`display_name` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `wp_admin_passwords`
--
ALTER TABLE `wp_admin_passwords`
  ADD CONSTRAINT `wp_admin_passwords_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_challenge_events`
--
ALTER TABLE `wp_challenge_events`
  ADD CONSTRAINT `fk_events_created_by` FOREIGN KEY (`created_by`) REFERENCES `wp_drill_users` (`id`),
  ADD CONSTRAINT `fk_events_drill` FOREIGN KEY (`drill_id`) REFERENCES `wp_drills` (`id`),
  ADD CONSTRAINT `fk_events_scoring_method` FOREIGN KEY (`scoring_method_id`) REFERENCES `wp_challenge_scoring_methods` (`id`);

--
-- Constraints for table `wp_challenge_participants`
--
ALTER TABLE `wp_challenge_participants`
  ADD CONSTRAINT `fk_participants_enrolled_by` FOREIGN KEY (`enrolled_by`) REFERENCES `wp_drill_users` (`id`),
  ADD CONSTRAINT `fk_participants_event` FOREIGN KEY (`challenge_event_id`) REFERENCES `wp_challenge_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_participants_user` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_challenge_scores`
--
ALTER TABLE `wp_challenge_scores`
  ADD CONSTRAINT `fk_scores_drill` FOREIGN KEY (`drill_id`) REFERENCES `wp_drills` (`id`),
  ADD CONSTRAINT `fk_scores_event` FOREIGN KEY (`challenge_event_id`) REFERENCES `wp_challenge_events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_scores_submitted_by` FOREIGN KEY (`submitted_by`) REFERENCES `wp_drill_users` (`id`),
  ADD CONSTRAINT `fk_scores_user` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`);

--
-- Constraints for table `wp_coach_passwords`
--
ALTER TABLE `wp_coach_passwords`
  ADD CONSTRAINT `wp_coach_passwords_ibfk_1` FOREIGN KEY (`coach_id`) REFERENCES `wp_drill_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_credit_to`
--
ALTER TABLE `wp_credit_to`
  ADD CONSTRAINT `fk_credit_created_by` FOREIGN KEY (`created_by`) REFERENCES `wp_drill_users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wp_diagrams`
--
ALTER TABLE `wp_diagrams`
  ADD CONSTRAINT `fk_diagrams_created_by` FOREIGN KEY (`created_by`) REFERENCES `wp_drill_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_diagrams_credit` FOREIGN KEY (`credit_id`) REFERENCES `wp_credit_to` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wp_drills`
--
ALTER TABLE `wp_drills`
  ADD CONSTRAINT `fk_drills_credit` FOREIGN KEY (`credit_id`) REFERENCES `wp_credit_to` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_drills_diagram_id` FOREIGN KEY (`diagram_id`) REFERENCES `wp_diagrams` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `wp_drills_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `wp_drill_categories` (`id`),
  ADD CONSTRAINT `wp_drills_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `wp_drill_skills` (`id`);

--
-- Constraints for table `wp_drill_assignments`
--
ALTER TABLE `wp_drill_assignments`
  ADD CONSTRAINT `wp_drill_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wp_drill_assignments_ibfk_2` FOREIGN KEY (`drill_id`) REFERENCES `wp_drills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wp_drill_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `wp_drill_users` (`id`);

--
-- Constraints for table `wp_drill_journal`
--
ALTER TABLE `wp_drill_journal`
  ADD CONSTRAINT `fk_journal_user` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_drill_scores`
--
ALTER TABLE `wp_drill_scores`
  ADD CONSTRAINT `wp_drill_scores_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`),
  ADD CONSTRAINT `wp_drill_scores_ibfk_2` FOREIGN KEY (`drill_id`) REFERENCES `wp_drills` (`id`),
  ADD CONSTRAINT `wp_drill_scores_ibfk_3` FOREIGN KEY (`submitted_by`) REFERENCES `wp_drill_users` (`id`),
  ADD CONSTRAINT `wp_drill_scores_ibfk_4` FOREIGN KEY (`assignment_id`) REFERENCES `wp_drill_assignments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wp_drill_users`
--
ALTER TABLE `wp_drill_users`
  ADD CONSTRAINT `fk_drill_users_wp` FOREIGN KEY (`wp_user_id`) REFERENCES `40s_users` (`ID`) ON DELETE SET NULL;

--
-- Constraints for table `wp_practice_sessions`
--
ALTER TABLE `wp_practice_sessions`
  ADD CONSTRAINT `wp_practice_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`);

--
-- Constraints for table `wp_training_content`
--
ALTER TABLE `wp_training_content`
  ADD CONSTRAINT `fk_training_content_category` FOREIGN KEY (`category_id`) REFERENCES `wp_drill_categories` (`id`),
  ADD CONSTRAINT `fk_training_content_created_by` FOREIGN KEY (`created_by`) REFERENCES `wp_drill_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_training_content_credit` FOREIGN KEY (`credit_id`) REFERENCES `wp_credit_to` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_training_content_skill` FOREIGN KEY (`skill_id`) REFERENCES `wp_drill_skills` (`id`);

--
-- Constraints for table `wp_training_programs`
--
ALTER TABLE `wp_training_programs`
  ADD CONSTRAINT `fk_training_programs_credit` FOREIGN KEY (`credit_id`) REFERENCES `wp_credit_to` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wp_training_programs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `wp_drill_categories` (`id`),
  ADD CONSTRAINT `wp_training_programs_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `wp_drill_skills` (`id`),
  ADD CONSTRAINT `wp_training_programs_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `wp_drill_users` (`id`);

--
-- Constraints for table `wp_training_program_assigned`
--
ALTER TABLE `wp_training_program_assigned`
  ADD CONSTRAINT `fk_assigned_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `wp_training_program_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assigned_category` FOREIGN KEY (`category_id`) REFERENCES `wp_drill_categories` (`id`),
  ADD CONSTRAINT `fk_assigned_created_by` FOREIGN KEY (`created_by`) REFERENCES `wp_drill_users` (`id`),
  ADD CONSTRAINT `fk_assigned_original_program` FOREIGN KEY (`original_program_id`) REFERENCES `wp_training_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assigned_program_user` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assigned_skill` FOREIGN KEY (`skill_id`) REFERENCES `wp_drill_skills` (`id`);

--
-- Constraints for table `wp_training_program_assignments`
--
ALTER TABLE `wp_training_program_assignments`
  ADD CONSTRAINT `wp_training_program_assignments_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `wp_training_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wp_training_program_assignments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wp_training_program_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `wp_drill_users` (`id`),
  ADD CONSTRAINT `wp_training_program_assignments_ibfk_4` FOREIGN KEY (`current_unit_id`) REFERENCES `wp_training_program_units` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `wp_training_program_content`
--
ALTER TABLE `wp_training_program_content`
  ADD CONSTRAINT `fk_content_created_by` FOREIGN KEY (`created_by`) REFERENCES `wp_drill_users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_content_drill` FOREIGN KEY (`drill_id`) REFERENCES `wp_drills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_content_unit` FOREIGN KEY (`unit_id`) REFERENCES `wp_training_program_units` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_training_program_content_content_id` FOREIGN KEY (`content_id`) REFERENCES `wp_training_content` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_training_program_content_assigned`
--
ALTER TABLE `wp_training_program_content_assigned`
  ADD CONSTRAINT `fk_content_assigned_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `wp_training_program_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_content_assigned_drill` FOREIGN KEY (`drill_id`) REFERENCES `wp_drills` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_content_assigned_original` FOREIGN KEY (`original_content_id`) REFERENCES `wp_training_program_content` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_content_assigned_training_content` FOREIGN KEY (`content_id`) REFERENCES `wp_training_content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_content_assigned_unit` FOREIGN KEY (`assigned_unit_id`) REFERENCES `wp_training_program_units_assigned` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_training_program_progress`
--
ALTER TABLE `wp_training_program_progress`
  ADD CONSTRAINT `fk_progress_content` FOREIGN KEY (`content_id`) REFERENCES `wp_training_program_content` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progress_content_assigned` FOREIGN KEY (`content_assigned_id`) REFERENCES `wp_training_program_content_assigned` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progress_program` FOREIGN KEY (`program_id`) REFERENCES `wp_training_programs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progress_unit` FOREIGN KEY (`unit_id`) REFERENCES `wp_training_program_units` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progress_unit_assigned` FOREIGN KEY (`unit_assigned_id`) REFERENCES `wp_training_program_units_assigned` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `wp_drill_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_training_program_units`
--
ALTER TABLE `wp_training_program_units`
  ADD CONSTRAINT `wp_training_program_units_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `wp_training_programs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wp_training_program_units_assigned`
--
ALTER TABLE `wp_training_program_units_assigned`
  ADD CONSTRAINT `fk_unit_assigned_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `wp_training_program_assignments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_unit_assigned_original` FOREIGN KEY (`original_unit_id`) REFERENCES `wp_training_program_units` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_unit_assigned_unlocked_by` FOREIGN KEY (`unlocked_by`) REFERENCES `wp_drill_users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
