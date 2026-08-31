-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 10:13 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `khalilabad`
--

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `municipality_name` varchar(255) DEFAULT NULL,
  `year_of_assessment` varchar(20) DEFAULT NULL,
  `zone_id` int(11) DEFAULT 1,
  `ward_id` int(11) DEFAULT NULL,
  `mohalla_id` int(11) DEFAULT NULL,
  `property_id` varchar(20) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `new_holding` varchar(100) DEFAULT NULL,
  `previous_holding` varchar(100) DEFAULT NULL,
  `property_status` varchar(50) DEFAULT NULL,
  `old_holding` varchar(100) DEFAULT NULL,
  `old_pid` varchar(100) DEFAULT NULL,
  `property_type` varchar(50) DEFAULT NULL,
  `road` varchar(255) DEFAULT NULL,
  `plot_area` decimal(10,2) DEFAULT NULL,
  `building_type` varchar(50) DEFAULT NULL,
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL,
  `house_no` varchar(100) DEFAULT NULL,
  `plot_no` varchar(100) DEFAULT NULL,
  `khata_no` varchar(100) DEFAULT NULL,
  `khasra_no` varchar(100) DEFAULT NULL,
  `addr1` varchar(255) DEFAULT NULL,
  `addr2` varchar(255) DEFAULT NULL,
  `pincode` varchar(20) DEFAULT NULL,
  `water_tax` decimal(10,2) DEFAULT NULL,
  `center_image_gps` varchar(255) DEFAULT NULL,
  `left_image_gps` varchar(255) DEFAULT NULL,
  `right_image_gps` varchar(255) DEFAULT NULL,
  `doc_proof` varchar(255) DEFAULT NULL,
  `supporting_doc_1` varchar(255) DEFAULT NULL,
  `supporting_doc_2` varchar(255) DEFAULT NULL,
  `supporting_doc_3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_remark` text DEFAULT NULL,
  `surveyor_id` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `environment` varchar(50) NOT NULL DEFAULT 'web',
  `verification_status` enum('pending','reject','approved') NOT NULL DEFAULT 'pending',
  `current_verification_role_id` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `rejection_remark` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `municipality_name`, `year_of_assessment`, `zone_id`, `ward_id`, `mohalla_id`, `property_id`, `ward`, `new_holding`, `previous_holding`, `property_status`, `old_holding`, `old_pid`, `property_type`, `road`, `plot_area`, `building_type`, `latitude`, `longitude`, `house_no`, `plot_no`, `khata_no`, `khasra_no`, `addr1`, `addr2`, `pincode`, `water_tax`, `center_image_gps`, `left_image_gps`, `right_image_gps`, `doc_proof`, `supporting_doc_1`, `supporting_doc_2`, `supporting_doc_3`, `created_at`, `is_deleted`, `deleted_at`, `deleted_remark`, `surveyor_id`, `created_by`, `updated_by`, `updated_at`, `environment`, `verification_status`, `current_verification_role_id`, `verified_by`, `verified_at`, `rejection_remark`) VALUES
(1, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520025R', '2', 'KLB-W2-00001', NULL, 'old', NULL, '0', '14', '1', 110.00, '1', '0', '0', '9A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(2, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520027R', '2', 'KLB-W2-00002', NULL, 'old', NULL, '0', '14', '1', 330.00, '1', '0', '0', '13', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(3, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520029R', '2', 'KLB-W2-00003', NULL, 'old', NULL, '0', '14', '1', 500.00, '1', '0', '0', '35A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(4, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520031R', '2', 'KLB-W2-00004', NULL, 'old', NULL, '0', '14', '1', 380.00, '1', '0', '0', '35B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(5, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520033R', '2', 'KLB-W2-00005', NULL, 'old', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '53A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(6, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520035R', '2', 'KLB-W2-00006', NULL, 'old', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '66B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(7, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520037R', '2', 'KLB-W2-00007', NULL, 'old', NULL, '0', '14', '1', 625.00, '1', '0', '0', '76A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(8, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520039R', '2', 'KLB-W2-00008', NULL, 'old', NULL, '0', '14', '1', 675.00, '1', '0', '0', '76B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(9, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520041R', '2', 'KLB-W2-00009', NULL, 'new', NULL, '0', '14', '1', 2110.00, '1', '0', '0', '79A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(10, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520043R', '2', 'KLB-W2-00010', NULL, 'new', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '79B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:56:54', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:56:54', 'web', 'approved', NULL, NULL, NULL, NULL),
(11, 'Nagar Palika Parishad Khalilabad', '2025-2026', 1, 11, 42, '0952601011520044N', '11', 'KLB-W11-00001', NULL, 'New', '', '', 'Non Residential', '12 to 24 Meters', 42.00, 'Vaccant Land', '25.93029004284818', '81.71701966630684', 'Recusandae Architec', 'Quis est quasi quis ', 'Aute id excepturi qu', 'Consectetur nihil d', 'Totam accusantium mo', 'Similique minim exer', '230128', 1.00, 'uploads/KLB-W11-00001/1788163698_6a953672a8ddf_logo-main.png', 'uploads/KLB-W11-00001/1788163698_6a953672a903a_logo-main.png', 'uploads/KLB-W11-00001/1788163698_6a953672a94ff_logo-main.png', 'uploads/KLB-W11-00001/1788163698_6a953672a8b31_logo-main.png', NULL, NULL, NULL, '2026-08-31 08:08:18', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 08:09:57', 'web', 'pending', 8, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessment_floors`
--

CREATE TABLE `assessment_floors` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `floor_no` varchar(50) NOT NULL,
  `residential_type` varchar(100) DEFAULT NULL,
  `construction_type` varchar(100) DEFAULT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `occupancy_type` varchar(100) DEFAULT NULL,
  `build_up_area` decimal(10,2) DEFAULT 0.00,
  `usage_type` varchar(100) DEFAULT NULL,
  `non_residential_group` varchar(150) DEFAULT NULL,
  `property_name` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_remark` text DEFAULT NULL,
  `surveyor_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment_floors`
--

INSERT INTO `assessment_floors` (`id`, `assessment_id`, `floor_no`, `residential_type`, `construction_type`, `date_from`, `date_to`, `occupancy_type`, `build_up_area`, `usage_type`, `non_residential_group`, `property_name`, `created_at`, `is_deleted`, `deleted_at`, `deleted_remark`, `surveyor_id`) VALUES
(1, 11, 'Fourth Floor - 5', NULL, 'RCC', '1979-07-15', '1982-06-15', 'Tenanted (T)', 43.00, 'Non-Residential', 'group4', 'All Other Commercial Buildings Which Are Not Included In Above Mentioned', '2026-08-31 08:08:18', 0, NULL, NULL, NULL),
(2, 11, 'Fifth Floor - 6', NULL, 'Vacant Land', '2021-01-07', '1998-10-07', 'Self-Occupied (S)', 34.00, 'Fully Residential', '', '', '2026-08-31 08:08:18', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessment_media`
--

CREATE TABLE `assessment_media` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `media_type` varchar(50) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_owners`
--

CREATE TABLE `assessment_owners` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `father_husband_pan` varchar(255) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `mobile` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_remark` text DEFAULT NULL,
  `surveyor_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment_owners`
--

INSERT INTO `assessment_owners` (`id`, `assessment_id`, `owner_name`, `father_husband_pan`, `gender`, `mobile`, `email`, `is_deleted`, `deleted_at`, `deleted_remark`, `surveyor_id`) VALUES
(1, 1, 'TASAUWAR HUSAN', 'MOH WAX', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(2, 2, 'SHABBIR AHAMA', 'ALIRAZA', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(3, 3, 'JAIBULLA NISHA, MANOWA, SAMSAD', 'HASAN RAZA', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(4, 4, 'GULFESHA KHATUN', 'MUZABIL HUSAN', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(5, 5, 'KUDDYASH', 'SAMI ULLHA', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(6, 6, 'AKHTAR AJEEBULHA ADI', 'BITAULLAH', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(7, 7, 'HAYAT', 'HOSHILDAR', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(8, 8, 'MOH HARISH', 'HOSHILDAR', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(9, 9, 'ZAHIR AHAMAD', 'AKULL GAFFAR', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(10, 10, 'SAYAD ALI, SAYAD AHAMAD, MAMIN NABI, ABDUL GAFFAR, MUSTAKIM- OTH', 'ABDUL KHALID', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(11, 11, 'Mr. Dana Freeman', 'Quas cumque sit rep', 'Other', '2222222222', 'nafoqexup@mailinator.com', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `assessment_updates_history`
--

CREATE TABLE `assessment_updates_history` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `property_id` varchar(17) DEFAULT NULL,
  `entity_type` enum('ASSESSMENT','OWNER','FLOOR') NOT NULL DEFAULT 'ASSESSMENT',
  `entity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','RESTORE') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(150) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `changed_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changed_fields`)),
  `old_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_data`)),
  `new_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_data`)),
  `remark` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessment_updates_history`
--

INSERT INTO `assessment_updates_history` (`id`, `assessment_id`, `property_id`, `entity_type`, `entity_id`, `action`, `user_id`, `user_name`, `username`, `changed_fields`, `old_data`, `new_data`, `remark`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 57, '0952601002520057R', 'ASSESSMENT', 57, 'UPDATE', 1, 'Administrator', 'admin', '[\"ward_id\",\"mohalla_id\",\"property_status\",\"old_pid\",\"property_type\",\"road\",\"plot_area\",\"building_type\",\"latitude\",\"longitude\",\"house_no\",\"plot_no\",\"khata_no\",\"khasra_no\",\"addr1\",\"addr2\",\"pincode\",\"floor.new.1\"]', '{\"ward_id\":\"2\",\"mohalla_id\":\"33\",\"property_status\":\"new\",\"old_pid\":\"0\",\"property_type\":\"14\",\"road\":\"1\",\"plot_area\":\"9999.00\",\"building_type\":\"1\",\"latitude\":\"0\",\"longitude\":\"0\",\"house_no\":\"100A\",\"plot_no\":\"\",\"khata_no\":\"\",\"khasra_no\":\"\",\"addr1\":\"BIDHIYANI\",\"addr2\":\"BIDHIYANI\",\"pincode\":\"\",\"floor.new.1\":\"\"}', '{\"ward_id\":\"23\",\"mohalla_id\":\"54\",\"property_status\":\"New\",\"old_pid\":\"\",\"property_type\":\"Industrial\",\"road\":\"12 to 24 Meters\",\"plot_area\":\"41\",\"building_type\":\"Vaccant Land\",\"latitude\":\"25.930283396214534\",\"longitude\":\"81.7170822026966\",\"house_no\":\"Tenetur nihil quia s\",\"plot_no\":\"A id ipsam veritatis\",\"khata_no\":\"Doloribus dolor assu\",\"khasra_no\":\"Cumque architecto do\",\"addr1\":\"In neque veritatis q\",\"addr2\":\"Duis deserunt qui li\",\"pincode\":\"230128\",\"floor.new.1\":\"Floor Added: Sixth Floor - 7\"}', 'Assessment updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 13:14:45'),
(2, 58, '0952601002520059R', 'ASSESSMENT', 58, 'UPDATE', 1, 'Administrator', 'admin', '[\"ward_id\",\"mohalla_id\",\"property_status\",\"old_pid\",\"property_type\",\"road\",\"plot_area\",\"building_type\",\"latitude\",\"longitude\",\"house_no\",\"plot_no\",\"khata_no\",\"khasra_no\",\"addr1\",\"addr2\",\"pincode\",\"water_tax\",\"floor.new.2\",\"floor.new.3\"]', '{\"ward_id\":\"2\",\"mohalla_id\":\"33\",\"property_status\":\"new\",\"old_pid\":\"NA\",\"property_type\":\"14\",\"road\":\"1\",\"plot_area\":\"528.00\",\"building_type\":\"1\",\"latitude\":\"0\",\"longitude\":\"0\",\"house_no\":\"116B\",\"plot_no\":\"\",\"khata_no\":\"\",\"khasra_no\":\"\",\"addr1\":\"BIDHIYANI\",\"addr2\":\"BIDHIYANI\",\"pincode\":\"\",\"water_tax\":\"0\",\"floor.new.2\":\"\",\"floor.new.3\":\"\"}', '{\"ward_id\":\"5\",\"mohalla_id\":\"36\",\"property_status\":\"New\",\"old_pid\":\"\",\"property_type\":\"Non Residential\",\"road\":\"12 to 24 Meters\",\"plot_area\":\"46\",\"building_type\":\"Vaccant Land\",\"latitude\":\"25.93029371838731\",\"longitude\":\"81.71701615043119\",\"house_no\":\"Ducimus eaque volup\",\"plot_no\":\"Irure voluptatum ita\",\"khata_no\":\"Voluptatem amet imp\",\"khasra_no\":\"Dolor ut non commodo\",\"addr1\":\"Tempore nobis ipsum\",\"addr2\":\"Aliquam sed voluptas\",\"pincode\":\"230128\",\"water_tax\":\"1\",\"floor.new.2\":\"Floor Added: Second Floor - 3\",\"floor.new.3\":\"Floor Added: Fifth Floor - 6\"}', 'Assessment updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '2026-08-31 13:16:07');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_verifications`
--

CREATE TABLE `assessment_verifications` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `verification_step` tinyint(4) NOT NULL,
  `action` enum('verified','rejected','approved') NOT NULL,
  `remark` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mohalla`
--

CREATE TABLE `mohalla` (
  `mohalla_id` int(11) NOT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `mohalla_name` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mohalla`
--

INSERT INTO `mohalla` (`mohalla_id`, `ward_id`, `mohalla_name`, `created_at`) VALUES
(32, 1, '32- TITAUWA (टिटौवा)', '2026-08-23 10:52:34'),
(33, 2, '33- BIDHIYANI (बिधियानी)', '2026-08-23 10:52:34'),
(34, 3, '34- ACHAKWAPUR (अचकवापुर)', '2026-08-23 10:52:34'),
(35, 4, '35- MADYA (मड़या)', '2026-08-23 10:52:34'),
(36, 5, '36- BARAI TOLA (बरई टोला)', '2026-08-23 10:52:34'),
(37, 6, '37- PURANI TAHSIL DACHNI (पुरानी तहसील दक्षिणी)', '2026-08-23 10:52:34'),
(38, 7, '38- BAGAHIYA (बगहिया)', '2026-08-23 10:52:34'),
(39, 8, '39- TRIPATHI NAGAR (त्रिपाठी नगर)', '2026-08-23 10:52:34'),
(40, 9, '40- GORKHAR (गोरखर)', '2026-08-23 10:52:34'),
(41, 10, '41- PATHAN TOLA PURVI (पठान टोला पूर्वी)', '2026-08-23 10:52:34'),
(42, 11, '42- BANJARIYA PASHMI (बंजरिया पश्मी)', '2026-08-23 10:52:34'),
(43, 12, '43- AUDYOGIK NAGAR (औद्योगिक नगर)', '2026-08-23 10:52:34'),
(44, 13, '44- BHITWA TOLA (भिटवा टोला)', '2026-08-23 10:52:34'),
(45, 14, '45- MATIHNA (मटिहना)', '2026-08-23 10:52:34'),
(46, 15, '46- BANJARIYA PURVI (बंजरिया पूर्वी)', '2026-08-23 10:52:34'),
(47, 16, '47- BARDAHIYA BAZAR (बरदहिया बाजार)', '2026-08-23 10:52:34'),
(48, 17, '48- MOTI NAGAR (मोती नगर)', '2026-08-23 10:52:34'),
(49, 18, '49- STATION PURWA (स्टेशन पुरवा)', '2026-08-23 10:52:34'),
(50, 19, '50- ANSAR TOLA (अंसार टोला)', '2026-08-23 10:52:34'),
(51, 20, '51- MALI TOLA (माली टोला)', '2026-08-23 10:52:34'),
(52, 21, '52- SHASTRI NAGAR (शास्त्री नगर)', '2026-08-23 10:52:34'),
(53, 22, '53- PURANI TAHSIL (पुरानी तहसील)', '2026-08-23 10:52:34'),
(54, 23, '54- GOLA BAZAR DAKCHINI (गोला बाजार दक्षिणी)', '2026-08-23 10:52:34'),
(55, 24, '55- GOLA BAZAR UTTRI (गोला बाजार उत्तरी)', '2026-08-23 10:52:34'),
(56, 25, '56- PATHAN TOLA PACHCMI (पठान टोला पश्चिमी)', '2026-08-23 10:52:34');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_key` varchar(150) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `module_name`, `permission_name`, `permission_key`, `created_at`) VALUES
(1, 'Dashboard', 'View Dashboard', 'dashboard.view', '2026-08-20 10:13:10'),
(2, 'Assessments', 'View Assessments', 'assessments.view', '2026-08-20 10:13:10'),
(3, 'Assessments', 'Add Assessment', 'assessments.add', '2026-08-20 10:13:10'),
(4, 'Assessments', 'Edit Assessment', 'assessments.edit', '2026-08-20 10:13:10'),
(5, 'Assessments', 'Delete Assessment', 'assessments.delete', '2026-08-20 10:13:10'),
(6, 'Assessments', 'Export Assessments', 'assessments.export', '2026-08-20 10:13:10'),
(7, 'Assessments', 'Bulk Upload', 'assessments.bulk_upload', '2026-08-20 10:13:10'),
(8, 'Surveyor Management', 'View Surveyors', 'surveyor.view', '2026-08-20 10:13:10'),
(9, 'Surveyor Management', 'Add Surveyor', 'surveyor.add', '2026-08-20 10:13:10'),
(10, 'Surveyor Management', 'Edit Surveyor', 'surveyor.edit', '2026-08-20 10:13:10'),
(11, 'Surveyor Management', 'Delete Surveyor', 'surveyor.delete', '2026-08-20 10:13:10'),
(12, 'Payments', 'View Payments', 'payments.view', '2026-08-20 10:13:10'),
(13, 'Payments', 'Add Payment', 'payments.add', '2026-08-20 10:13:10'),
(14, 'Payments', 'Edit Payment', 'payments.edit', '2026-08-20 10:13:10'),
(15, 'Payments', 'Delete Payment', 'payments.delete', '2026-08-20 10:13:10'),
(16, 'Payments', 'Export Payments', 'payments.export', '2026-08-20 10:13:10'),
(17, 'Analytics', 'View Analytics', 'analytics.view', '2026-08-20 10:13:10'),
(18, 'Analytics', 'Export Analytics', 'analytics.export', '2026-08-20 10:13:10'),
(19, 'Users', 'View Users', 'users.view', '2026-08-20 10:13:10'),
(20, 'Users', 'Add User', 'users.add', '2026-08-20 10:13:10'),
(21, 'Users', 'Edit User', 'users.edit', '2026-08-20 10:13:10'),
(22, 'Users', 'Delete User', 'users.delete', '2026-08-20 10:13:10'),
(23, 'Roles & Permissions', 'View Roles', 'roles.view', '2026-08-20 10:13:10'),
(24, 'Roles & Permissions', 'Add Role', 'roles.add', '2026-08-20 10:13:10'),
(25, 'Roles & Permissions', 'Edit Role', 'roles.edit', '2026-08-20 10:13:10'),
(26, 'Roles & Permissions', 'Delete Role', 'roles.delete', '2026-08-20 10:13:10'),
(27, 'Roles & Permissions', 'Manage Permissions', 'roles.manage_permissions', '2026-08-20 10:13:10'),
(28, 'Settings', 'View Settings', 'settings.view', '2026-08-20 10:13:10'),
(29, 'Settings', 'Edit Settings', 'settings.edit', '2026-08-20 10:13:10');

-- --------------------------------------------------------

--
-- Table structure for table `property_arv_details`
--

CREATE TABLE `property_arv_details` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `zone_id` int(11) DEFAULT NULL,
  `ward_id` int(11) DEFAULT NULL,
  `mohalla_id` int(11) DEFAULT NULL,
  `financial_year` varchar(20) DEFAULT NULL,
  `road_width` decimal(10,2) DEFAULT NULL,
  `construction_type` varchar(50) DEFAULT NULL,
  `rate_master_id` int(11) DEFAULT NULL,
  `property_id` varchar(100) DEFAULT NULL,
  `bill_no` varchar(100) DEFAULT NULL,
  `bill_date` date DEFAULT NULL,
  `house_tax_arv` decimal(15,2) DEFAULT 0.00,
  `house_tax_rate` decimal(5,2) DEFAULT 0.00,
  `house_tax_amount` decimal(15,2) DEFAULT 0.00,
  `water_tax_arv` decimal(15,2) DEFAULT 0.00,
  `water_tax_rate` decimal(5,2) DEFAULT 0.00,
  `water_tax_amount` decimal(15,2) DEFAULT 0.00,
  `water_fee_arv` decimal(15,2) DEFAULT 0.00,
  `water_fee_rate` decimal(5,2) DEFAULT 0.00,
  `water_fee_amount` decimal(15,2) DEFAULT 0.00,
  `sewer_tax_arv` decimal(15,2) DEFAULT 0.00,
  `sewer_tax_rate` decimal(5,2) DEFAULT 0.00,
  `sewer_tax_amount` decimal(15,2) DEFAULT 0.00,
  `other_tax_arv` decimal(15,2) DEFAULT 0.00,
  `other_tax_rate` decimal(5,2) DEFAULT 0.00,
  `other_tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_tax` decimal(15,2) DEFAULT 0.00,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `house_tax_current` decimal(10,2) DEFAULT 0.00,
  `house_tax_arrear` decimal(10,2) DEFAULT 0.00,
  `house_tax_interest` decimal(10,2) DEFAULT 0.00,
  `house_tax_total` decimal(10,2) DEFAULT 0.00,
  `water_tax_current` decimal(10,2) DEFAULT 0.00,
  `water_tax_arrear` decimal(10,2) DEFAULT 0.00,
  `water_tax_interest` decimal(10,2) DEFAULT 0.00,
  `water_tax_total` decimal(10,2) DEFAULT 0.00,
  `water_fee_current` decimal(10,2) DEFAULT 0.00,
  `water_fee_arrear` decimal(10,2) DEFAULT 0.00,
  `water_fee_interest` decimal(10,2) DEFAULT 0.00,
  `water_fee_total` decimal(10,2) DEFAULT 0.00,
  `sewer_tax_current` decimal(10,2) DEFAULT 0.00,
  `sewer_tax_arrear` decimal(10,2) DEFAULT 0.00,
  `sewer_tax_interest` decimal(10,2) DEFAULT 0.00,
  `sewer_tax_total` decimal(10,2) DEFAULT 0.00,
  `other_tax_current` decimal(10,2) DEFAULT 0.00,
  `other_tax_arrear` decimal(10,2) DEFAULT 0.00,
  `other_tax_interest` decimal(10,2) DEFAULT 0.00,
  `other_tax_total` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `advance_deposit` decimal(10,2) DEFAULT 0.00,
  `arv_status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_arv_details`
--

INSERT INTO `property_arv_details` (`id`, `assessment_id`, `zone_id`, `ward_id`, `mohalla_id`, `financial_year`, `road_width`, `construction_type`, `rate_master_id`, `property_id`, `bill_no`, `bill_date`, `house_tax_arv`, `house_tax_rate`, `house_tax_amount`, `water_tax_arv`, `water_tax_rate`, `water_tax_amount`, `water_fee_arv`, `water_fee_rate`, `water_fee_amount`, `sewer_tax_arv`, `sewer_tax_rate`, `sewer_tax_amount`, `other_tax_arv`, `other_tax_rate`, `other_tax_amount`, `total_tax`, `status`, `created_at`, `house_tax_current`, `house_tax_arrear`, `house_tax_interest`, `house_tax_total`, `water_tax_current`, `water_tax_arrear`, `water_tax_interest`, `water_tax_total`, `water_fee_current`, `water_fee_arrear`, `water_fee_interest`, `water_fee_total`, `sewer_tax_current`, `sewer_tax_arrear`, `sewer_tax_interest`, `sewer_tax_total`, `other_tax_current`, `other_tax_arrear`, `other_tax_interest`, `other_tax_total`, `discount_amount`, `advance_deposit`, `arv_status`) VALUES
(1, 1, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520025R', '5.26E+13', '2025-04-01', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:56:54', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(2, 2, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520027R', '5.26E+13', '2025-04-01', 831.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:56:54', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(3, 3, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520029R', '5.26E+13', '2025-04-01', 1260.00, 0.00, 267.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 267.00, 1, '2026-08-31 07:56:54', 126.00, 126.00, 15.00, 267.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(4, 4, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520031R', '5.26E+13', '2025-04-01', 1368.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:56:54', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(5, 5, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520033R', '5.26E+13', '2025-04-01', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:56:54', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(6, 6, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520035R', '5.26E+13', '2025-04-01', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:56:54', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(7, 7, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520037R', '5.26E+13', '2025-04-01', 975.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 1, '2026-08-31 07:56:54', 100.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(8, 8, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520039R', '5.26E+13', '2025-04-01', 972.00, 0.00, 436.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 436.00, 1, '2026-08-31 07:56:54', 100.00, 324.00, 12.00, 436.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(9, 9, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520041R', '5.26E+13', '2025-04-01', 5072.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:56:54', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(10, 10, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520043R', '5.26E+13', '2025-04-01', 2000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:56:54', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(11, 11, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '095520111000001R', 'B5522526000001', '2026-08-31', 2562.48, 10.00, 256.25, 2562.48, 10.00, 256.25, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 512.50, 1, '2026-08-31 08:12:34', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'GBW');

-- --------------------------------------------------------

--
-- Table structure for table `rate_master`
--

CREATE TABLE `rate_master` (
  `id` int(11) NOT NULL,
  `financial_year` varchar(10) DEFAULT NULL,
  `ward_no` int(11) DEFAULT NULL,
  `ward_name` varchar(255) DEFAULT NULL,
  `road_width` varchar(50) DEFAULT NULL,
  `rcc_rate` decimal(10,2) DEFAULT NULL,
  `acc_rate` decimal(10,2) DEFAULT NULL,
  `other_rate` decimal(10,2) DEFAULT NULL,
  `open_plot_rate` decimal(10,2) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rate_master`
--

INSERT INTO `rate_master` (`id`, `financial_year`, `ward_no`, `ward_name`, `road_width`, `rcc_rate`, `acc_rate`, `other_rate`, `open_plot_rate`, `status`, `updated_at`) VALUES
(1, '2024-25', 1, 'RAGHWAPUR', 'Upto 9 Meters', 0.55, 0.45, 0.35, 0.20, 'active', '2026-02-13 12:14:48'),
(2, '2024-25', 1, 'RAGHWAPUR', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(3, '2024-25', 1, 'RAGHWAPUR', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(4, '2024-25', 1, 'RAGHWAPUR', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(5, '2024-25', 2, 'MAA NAGARWA BHAGWATI GHUS', 'Upto 9 Meters', 0.55, 0.45, 0.35, 0.20, 'active', '2026-02-13 12:14:48'),
(6, '2024-25', 2, 'MAA NAGARWA BHAGWATI GHUS', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(7, '2024-25', 2, 'MAA NAGARWA BHAGWATI GHUS', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(8, '2024-25', 2, 'MAA NAGARWA BHAGWATI GHUS', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(9, '2024-25', 3, 'MEHRA', 'Upto 9 Meters', 0.60, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(10, '2024-25', 3, 'MEHRA', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(11, '2024-25', 3, 'MEHRA', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(12, '2024-25', 3, 'MEHRA', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(13, '2024-25', 4, 'KATHINAHIYA / KATRARI', 'Upto 9 Meters', 0.55, 0.45, 0.35, 0.20, 'active', '2026-02-13 12:14:48'),
(14, '2024-25', 4, 'KATHINAHIYA / KATRARI', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(15, '2024-25', 4, 'KATHINAHIYA / KATRARI', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(16, '2024-25', 4, 'KATHINAHIYA / KATRARI', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(17, '2024-25', 5, 'DANOPUR / BHATWALIYA', 'Upto 9 Meters', 0.55, 0.45, 0.35, 0.20, 'active', '2026-02-13 12:14:48'),
(18, '2024-25', 5, 'DANOPUR / BHATWALIYA', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(19, '2024-25', 5, 'DANOPUR / BHATWALIYA', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(20, '2024-25', 5, 'DANOPUR / BHATWALIYA', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(21, '2024-25', 6, 'AMBEDKAR NAGAR', 'Upto 9 Meters', 0.60, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(22, '2024-25', 6, 'AMBEDKAR NAGAR', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(23, '2024-25', 6, 'AMBEDKAR NAGAR', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(24, '2024-25', 6, 'AMBEDKAR NAGAR', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(25, '2024-25', 7, 'DEORIA KHAS (WEST)', 'Upto 9 Meters', 0.55, 0.45, 0.35, 0.20, 'active', '2026-02-13 12:14:48'),
(26, '2024-25', 7, 'DEORIA KHAS (WEST)', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(27, '2024-25', 7, 'DEORIA KHAS (WEST)', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(28, '2024-25', 7, 'DEORIA KHAS (WEST)', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(29, '2024-25', 8, 'SAKET NAGAR', 'Upto 9 Meters', 0.55, 0.45, 0.35, 0.20, 'active', '2026-02-13 12:14:48'),
(30, '2024-25', 8, 'SAKET NAGAR', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(31, '2024-25', 8, 'SAKET NAGAR', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(32, '2024-25', 8, 'SAKET NAGAR', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(33, '2024-25', 9, 'PINDRA', 'Upto 9 Meters', 0.55, 0.45, 0.35, 0.20, 'active', '2026-02-13 12:14:48'),
(34, '2024-25', 9, 'PINDRA', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(35, '2024-25', 9, 'PINDRA', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(36, '2024-25', 9, 'PINDRA', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(37, '2024-25', 10, 'SOMNATH NAGAR', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(38, '2024-25', 10, 'SOMNATH NAGAR', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(39, '2024-25', 10, 'SOMNATH NAGAR', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(40, '2024-25', 10, 'SOMNATH NAGAR', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(41, '2024-25', 11, 'CHAKIYAWA', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(42, '2024-25', 11, 'CHAKIYAWA', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(43, '2024-25', 11, 'CHAKIYAWA', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(44, '2024-25', 11, 'CHAKIYAWA', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(45, '2024-25', 12, 'RAM GULAM TOLA (EAST)', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(46, '2024-25', 12, 'RAM GULAM TOLA (EAST)', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(47, '2024-25', 12, 'RAM GULAM TOLA (EAST)', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(48, '2024-25', 12, 'RAM GULAM TOLA (EAST)', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(49, '2024-25', 13, 'SINDHI MILL COLONY', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(50, '2024-25', 13, 'SINDHI MILL COLONY', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(51, '2024-25', 13, 'SINDHI MILL COLONY', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(52, '2024-25', 13, 'SINDHI MILL COLONY', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(53, '2024-25', 14, 'UMA NAGAR / KATRARI', 'Upto 9 Meters', 0.60, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(54, '2024-25', 14, 'UMA NAGAR / KATRARI', '9 to 12 Meters', 0.90, 0.80, 0.70, 0.25, 'active', '2026-02-13 12:14:48'),
(55, '2024-25', 14, 'UMA NAGAR / KATRARI', '12 to 24 Meters', 1.30, 1.20, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(56, '2024-25', 14, 'UMA NAGAR / KATRARI', 'Above 24 Meters', 1.35, 1.25, 1.15, 0.40, 'active', '2026-02-13 12:14:48'),
(57, '2024-25', 15, 'SONDA', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(58, '2024-25', 15, 'SONDA', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(59, '2024-25', 15, 'SONDA', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(60, '2024-25', 15, 'SONDA', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(61, '2024-25', 16, 'AAZAD NAGAR', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(62, '2024-25', 16, 'AAZAD NAGAR', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(63, '2024-25', 16, 'AAZAD NAGAR', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(64, '2024-25', 16, 'AAZAD NAGAR', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(65, '2024-25', 17, 'PAGRA URF PARSIYA', 'Upto 9 Meters', 0.55, 0.45, 0.35, 0.20, 'active', '2026-02-13 12:14:48'),
(66, '2024-25', 17, 'PAGRA URF PARSIYA', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(67, '2024-25', 17, 'PAGRA URF PARSIYA', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(68, '2024-25', 17, 'PAGRA URF PARSIYA', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(69, '2024-25', 18, 'MUNSI GORAKHNATH TOLA', 'Upto 9 Meters', 0.75, 0.70, 0.60, 0.20, 'active', '2026-02-13 12:14:48'),
(70, '2024-25', 18, 'MUNSI GORAKHNATH TOLA', '9 to 12 Meters', 0.90, 0.80, 0.70, 0.25, 'active', '2026-02-13 12:14:48'),
(71, '2024-25', 18, 'MUNSI GORAKHNATH TOLA', '12 to 24 Meters', 1.35, 1.25, 1.15, 0.35, 'active', '2026-02-13 12:14:48'),
(72, '2024-25', 18, 'MUNSI GORAKHNATH TOLA', 'Above 24 Meters', 1.60, 1.50, 1.40, 0.40, 'active', '2026-02-13 12:14:48'),
(73, '2024-25', 19, 'DEORIA RAMNATH (NORTH)', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(74, '2024-25', 19, 'DEORIA RAMNATH (NORTH)', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(75, '2024-25', 19, 'DEORIA RAMNATH (NORTH)', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(76, '2024-25', 19, 'DEORIA RAMNATH (NORTH)', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(77, '2024-25', 20, 'RAM GULAM TOLA (WEST)', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(78, '2024-25', 20, 'RAM GULAM TOLA (WEST)', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(79, '2024-25', 20, 'RAM GULAM TOLA (WEST)', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(80, '2024-25', 20, 'RAM GULAM TOLA (WEST)', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(81, '2024-25', 21, 'BAANS DEORIA', 'Upto 9 Meters', 0.75, 0.65, 0.55, 0.20, 'active', '2026-02-13 12:14:48'),
(82, '2024-25', 21, 'BAANS DEORIA', '9 to 12 Meters', 1.10, 1.00, 0.90, 0.25, 'active', '2026-02-13 12:14:48'),
(83, '2024-25', 21, 'BAANS DEORIA', '12 to 24 Meters', 1.35, 1.25, 1.15, 0.35, 'active', '2026-02-13 12:14:48'),
(84, '2024-25', 21, 'BAANS DEORIA', 'Above 24 Meters', 1.50, 1.40, 1.30, 0.40, 'active', '2026-02-13 12:14:48'),
(85, '2024-25', 22, 'DEORIA KHAS (EAST)', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(86, '2024-25', 22, 'DEORIA KHAS (EAST)', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(87, '2024-25', 22, 'DEORIA KHAS (EAST)', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(88, '2024-25', 22, 'DEORIA KHAS (EAST)', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(89, '2024-25', 23, 'RAGHAV NAGAR WEST', 'Upto 9 Meters', 0.70, 0.65, 0.55, 0.20, 'active', '2026-02-13 12:14:48'),
(90, '2024-25', 23, 'RAGHAV NAGAR WEST', '9 to 12 Meters', 0.90, 0.80, 0.70, 0.25, 'active', '2026-02-13 12:14:48'),
(91, '2024-25', 23, 'RAGHAV NAGAR WEST', '12 to 24 Meters', 1.35, 1.25, 1.15, 0.35, 'active', '2026-02-13 12:14:48'),
(92, '2024-25', 23, 'RAGHAV NAGAR WEST', 'Above 24 Meters', 1.50, 1.40, 1.30, 0.40, 'active', '2026-02-13 12:14:48'),
(93, '2024-25', 24, 'RAGHAV NAGAR EAST', 'Upto 9 Meters', 0.70, 0.65, 0.55, 0.20, 'active', '2026-02-13 12:14:48'),
(94, '2024-25', 24, 'RAGHAV NAGAR EAST', '9 to 12 Meters', 0.90, 0.80, 0.70, 0.25, 'active', '2026-02-13 12:14:48'),
(95, '2024-25', 24, 'RAGHAV NAGAR EAST', '12 to 24 Meters', 1.35, 1.25, 1.15, 0.35, 'active', '2026-02-13 12:14:48'),
(96, '2024-25', 24, 'RAGHAV NAGAR EAST', 'Above 24 Meters', 1.50, 1.40, 1.30, 0.40, 'active', '2026-02-13 12:14:48'),
(97, '2024-25', 25, 'AACHARYA RAMCHAND SHUKLA NAGAR', 'Upto 9 Meters', 0.60, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(98, '2024-25', 25, 'AACHARYA RAMCHAND SHUKLA NAGAR', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(99, '2024-25', 25, 'AACHARYA RAMCHAND SHUKLA NAGAR', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(100, '2024-25', 25, 'AACHARYA RAMCHAND SHUKLA NAGAR', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(101, '2024-25', 26, 'NAYI BAZAR', 'Upto 9 Meters', 1.00, 0.95, 0.85, 0.20, 'active', '2026-02-13 12:14:48'),
(102, '2024-25', 26, 'NAYI BAZAR', '9 to 12 Meters', 1.50, 1.40, 1.30, 0.25, 'active', '2026-02-13 12:14:48'),
(103, '2024-25', 26, 'NAYI BAZAR', '12 to 24 Meters', 1.90, 1.80, 1.70, 0.35, 'active', '2026-02-13 12:14:48'),
(104, '2024-25', 26, 'NAYI BAZAR', 'Above 24 Meters', 2.25, 2.15, 2.05, 0.40, 'active', '2026-02-13 12:14:48'),
(105, '2024-25', 27, 'GARULPAR', 'Upto 9 Meters', 1.00, 0.95, 0.85, 0.20, 'active', '2026-02-13 12:14:48'),
(106, '2024-25', 27, 'GARULPAR', '9 to 12 Meters', 1.50, 1.40, 1.30, 0.25, 'active', '2026-02-13 12:14:48'),
(107, '2024-25', 27, 'GARULPAR', '12 to 24 Meters', 1.90, 1.80, 1.70, 0.35, 'active', '2026-02-13 12:14:48'),
(108, '2024-25', 27, 'GARULPAR', 'Above 24 Meters', 2.25, 2.15, 2.05, 0.40, 'active', '2026-02-13 12:14:48'),
(109, '2024-25', 28, 'RAMNATH DEORIA SOUTH / KATRARI', 'Upto 9 Meters', 0.55, 0.50, 0.40, 0.20, 'active', '2026-02-13 12:14:48'),
(110, '2024-25', 28, 'RAMNATH DEORIA SOUTH / KATRARI', '9 to 12 Meters', 0.80, 0.70, 0.60, 0.25, 'active', '2026-02-13 12:14:48'),
(111, '2024-25', 28, 'RAMNATH DEORIA SOUTH / KATRARI', '12 to 24 Meters', 1.20, 1.10, 1.00, 0.35, 'active', '2026-02-13 12:14:48'),
(112, '2024-25', 28, 'RAMNATH DEORIA SOUTH / KATRARI', 'Above 24 Meters', 1.30, 1.20, 1.10, 0.40, 'active', '2026-02-13 12:14:48'),
(113, '2024-25', 29, 'ROUNIYARI TOLA', 'Upto 9 Meters', 1.00, 0.95, 0.85, 0.20, 'active', '2026-02-13 12:14:48'),
(114, '2024-25', 29, 'ROUNIYARI TOLA', '9 to 12 Meters', 1.50, 1.40, 1.30, 0.25, 'active', '2026-02-13 12:14:48'),
(115, '2024-25', 29, 'ROUNIYARI TOLA', '12 to 24 Meters', 1.90, 1.80, 1.70, 0.35, 'active', '2026-02-13 12:14:48'),
(116, '2024-25', 29, 'ROUNIYARI TOLA', 'Above 24 Meters', 2.25, 2.15, 2.05, 0.40, 'active', '2026-02-13 12:14:48'),
(117, '2024-25', 30, 'NEW COLONY NORTH', 'Upto 9 Meters', 0.70, 0.60, 0.50, 0.20, 'active', '2026-02-13 12:14:48'),
(118, '2024-25', 30, 'NEW COLONY NORTH', '9 to 12 Meters', 0.90, 0.80, 0.70, 0.25, 'active', '2026-02-13 12:14:48'),
(119, '2024-25', 30, 'NEW COLONY NORTH', '12 to 24 Meters', 1.30, 1.25, 1.15, 0.35, 'active', '2026-02-13 12:14:48'),
(120, '2024-25', 30, 'NEW COLONY NORTH', 'Above 24 Meters', 1.50, 1.40, 1.30, 0.40, 'active', '2026-02-13 12:14:48'),
(121, '2024-25', 31, 'ABUBAKR NAGAR NORTH', 'Upto 9 Meters', 0.75, 0.70, 0.60, 0.20, 'active', '2026-02-13 12:14:48'),
(122, '2024-25', 31, 'ABUBAKR NAGAR NORTH', '9 to 12 Meters', 0.90, 0.80, 0.70, 0.25, 'active', '2026-02-13 12:14:48'),
(123, '2024-25', 31, 'ABUBAKR NAGAR NORTH', '12 to 24 Meters', 1.35, 1.25, 1.15, 0.35, 'active', '2026-02-13 12:14:48'),
(124, '2024-25', 31, 'ABUBAKR NAGAR NORTH', 'Above 24 Meters', 1.60, 1.50, 1.40, 0.40, 'active', '2026-02-13 12:14:48'),
(125, '2024-25', 32, 'ABUBAKR NAGAR SOUTH', 'Upto 9 Meters', 0.75, 0.70, 0.60, 0.20, 'active', '2026-02-13 12:14:48'),
(126, '2024-25', 32, 'ABUBAKR NAGAR SOUTH', '9 to 12 Meters', 0.90, 0.80, 0.70, 0.25, 'active', '2026-02-13 12:14:48'),
(127, '2024-25', 32, 'ABUBAKR NAGAR SOUTH', '12 to 24 Meters', 1.35, 1.25, 1.15, 0.35, 'active', '2026-02-13 12:14:48'),
(128, '2024-25', 32, 'ABUBAKR NAGAR SOUTH', 'Above 24 Meters', 1.60, 1.50, 1.40, 0.40, 'active', '2026-02-13 12:14:48'),
(129, '2024-25', 33, 'NEW COLONY SOUTH', 'Upto 9 Meters', 0.65, 0.55, 0.45, 0.20, 'active', '2026-02-13 12:14:48'),
(130, '2024-25', 33, 'NEW COLONY SOUTH', '9 to 12 Meters', 0.90, 0.80, 0.70, 0.25, 'active', '2026-02-13 12:14:48'),
(131, '2024-25', 33, 'NEW COLONY SOUTH', '12 to 24 Meters', 1.35, 1.25, 1.15, 0.35, 'active', '2026-02-13 12:14:48'),
(132, '2024-25', 33, 'NEW COLONY SOUTH', 'Above 24 Meters', 1.50, 1.40, 1.30, 0.40, 'active', '2026-02-13 12:14:48');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `status`, `created_at`) VALUES
(1, 'ADMIN', 'Full system administrator with all permissions', 1, '2026-08-20 09:57:15'),
(2, 'CHAIRMAN', 'Municipal Chairman', 1, '2026-08-20 09:57:15'),
(3, 'EO', 'Executive Officer', 1, '2026-08-20 09:57:15'),
(4, 'TAX SUPERINTENDENT / KNA', 'Tax Superintendent / KNA', 1, '2026-08-20 09:57:15'),
(5, 'REVENUE INSPECTOR', 'Revenue Inspector', 1, '2026-08-20 09:57:15'),
(6, 'TAX COLLECTOR', 'Tax Collector', 1, '2026-08-20 09:57:15'),
(7, 'CLERK', 'Clerk', 1, '2026-08-20 09:57:15'),
(8, 'LIPIK', 'Lipik', 1, '2026-08-20 09:57:15');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(57, 1, 17, '2026-08-20 10:25:11'),
(58, 1, 18, '2026-08-20 10:25:11'),
(59, 1, 2, '2026-08-20 10:25:11'),
(60, 1, 3, '2026-08-20 10:25:11'),
(61, 1, 4, '2026-08-20 10:25:11'),
(62, 1, 5, '2026-08-20 10:25:11'),
(63, 1, 6, '2026-08-20 10:25:11'),
(64, 1, 7, '2026-08-20 10:25:11'),
(65, 1, 1, '2026-08-20 10:25:11'),
(66, 1, 12, '2026-08-20 10:25:11'),
(67, 1, 13, '2026-08-20 10:25:11'),
(68, 1, 14, '2026-08-20 10:25:11'),
(69, 1, 15, '2026-08-20 10:25:11'),
(70, 1, 16, '2026-08-20 10:25:11'),
(71, 1, 23, '2026-08-20 10:25:11'),
(72, 1, 24, '2026-08-20 10:25:11'),
(73, 1, 25, '2026-08-20 10:25:11'),
(74, 1, 26, '2026-08-20 10:25:11'),
(75, 1, 27, '2026-08-20 10:25:11'),
(76, 1, 28, '2026-08-20 10:25:11'),
(77, 1, 29, '2026-08-20 10:25:11'),
(78, 1, 8, '2026-08-20 10:25:11'),
(79, 1, 9, '2026-08-20 10:25:11'),
(80, 1, 10, '2026-08-20 10:25:11'),
(81, 1, 11, '2026-08-20 10:25:11'),
(82, 1, 19, '2026-08-20 10:25:11'),
(83, 1, 20, '2026-08-20 10:25:11'),
(84, 1, 21, '2026-08-20 10:25:11'),
(85, 1, 22, '2026-08-20 10:25:11'),
(664, 8, 2, '2026-08-31 08:05:05'),
(665, 8, 3, '2026-08-31 08:05:05'),
(666, 8, 4, '2026-08-31 08:05:05'),
(667, 8, 5, '2026-08-31 08:05:05'),
(668, 8, 6, '2026-08-31 08:05:05'),
(669, 8, 7, '2026-08-31 08:05:05'),
(670, 8, 1, '2026-08-31 08:05:05'),
(671, 5, 2, '2026-08-31 08:05:27'),
(672, 5, 3, '2026-08-31 08:05:27'),
(673, 5, 4, '2026-08-31 08:05:27'),
(674, 5, 5, '2026-08-31 08:05:27'),
(675, 5, 6, '2026-08-31 08:05:27'),
(676, 5, 7, '2026-08-31 08:05:27'),
(677, 5, 1, '2026-08-31 08:05:27'),
(678, 6, 2, '2026-08-31 08:05:42'),
(679, 6, 3, '2026-08-31 08:05:42'),
(680, 6, 4, '2026-08-31 08:05:42'),
(681, 6, 5, '2026-08-31 08:05:42'),
(682, 6, 6, '2026-08-31 08:05:42'),
(683, 6, 7, '2026-08-31 08:05:42'),
(684, 6, 1, '2026-08-31 08:05:42'),
(685, 4, 2, '2026-08-31 08:05:51'),
(686, 4, 3, '2026-08-31 08:05:51'),
(687, 4, 4, '2026-08-31 08:05:51'),
(688, 4, 5, '2026-08-31 08:05:51'),
(689, 4, 6, '2026-08-31 08:05:51'),
(690, 4, 7, '2026-08-31 08:05:51'),
(691, 4, 1, '2026-08-31 08:05:51'),
(692, 3, 2, '2026-08-31 08:06:09'),
(693, 3, 3, '2026-08-31 08:06:09'),
(694, 3, 4, '2026-08-31 08:06:09'),
(695, 3, 5, '2026-08-31 08:06:09'),
(696, 3, 6, '2026-08-31 08:06:09'),
(697, 3, 7, '2026-08-31 08:06:09'),
(698, 3, 1, '2026-08-31 08:06:09'),
(699, 7, 2, '2026-08-31 08:06:18'),
(700, 7, 3, '2026-08-31 08:06:18'),
(701, 7, 4, '2026-08-31 08:06:18'),
(702, 7, 5, '2026-08-31 08:06:18'),
(703, 7, 6, '2026-08-31 08:06:18'),
(704, 7, 7, '2026-08-31 08:06:18'),
(705, 7, 1, '2026-08-31 08:06:18'),
(706, 2, 2, '2026-08-31 08:06:28'),
(707, 2, 3, '2026-08-31 08:06:28'),
(708, 2, 4, '2026-08-31 08:06:28'),
(709, 2, 5, '2026-08-31 08:06:28'),
(710, 2, 6, '2026-08-31 08:06:28'),
(711, 2, 7, '2026-08-31 08:06:28'),
(712, 2, 1, '2026-08-31 08:06:28');

-- --------------------------------------------------------

--
-- Table structure for table `surveyors`
--

CREATE TABLE `surveyors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'surveyor',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveyors`
--

INSERT INTO `surveyors` (`id`, `name`, `email`, `mobile`, `username`, `password`, `profile_pic`, `role`, `status`, `created_by`, `created_at`) VALUES
(1, 'Ila Pennington', 'test@gmail.com', '8178360198', 'KLB-SUR-001', '$2y$10$7J1whgPMp3ayuBUjIYM9eucRKPt50HkxRKmspH4vlxtqS8L34XXI6', 'profile.png', 'surveyor', 'active', 1, '2026-08-20 07:39:48');

-- --------------------------------------------------------

--
-- Table structure for table `surveyor_wards`
--

CREATE TABLE `surveyor_wards` (
  `surveyor_id` int(11) NOT NULL,
  `ward_id` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveyor_wards`
--

INSERT INTO `surveyor_wards` (`surveyor_id`, `ward_id`, `status`) VALUES
(2, 33, 'active'),
(14, 3, 'inactive'),
(15, 3, 'inactive'),
(1, 1, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `role_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `password`, `profile_pic`, `role`, `role_id`, `status`, `created_at`) VALUES
(1, 'admin', 'Administrator', 'khalilabad.upmunicipal@gmail.com', '$2y$10$To88d19jO.GvkA52L/73DufY4jB2CMlTxo9gPoRSKUKG4W9Nh2bz6', 'profile_6a858a92d4b229.23828021.png', 'ADMIN', 1, 'active', '2025-09-01 06:21:24'),
(8, 'chairam', 'chairman', 'ram@gmail.com', '$2y$10$gM/thWwdjvZ7MH0nJcW/A.6ujEhuMsegJsa0nRAfx494hB13fKqCi', 'user_8.png', 'CHAIRMAN', 2, 'active', '2026-08-20 11:38:34'),
(11, 'eo', 'eo', 'eo@gmail.com', '$2y$10$hpZGDJ0L0j96.5QXrVTd0OYAbbAiifC3Ydw93JtmcjcFRHOR2Eerm', 'user_11.png', 'EO', 3, 'active', '2026-08-29 05:18:27'),
(12, 'lipik', 'lipik', 'lipik@gmail.com', '$2y$10$stHVtL0wRGo4st3ggMAk6OecRycnfouADTUpO8S88XNHi52v98KpW', 'user_12.png', 'LIPIK', 8, 'active', '2026-08-29 05:18:57'),
(13, 'revenue-inspector', 'revenue inspector', 'revenue-inspector@gmail.com', '$2y$10$CC2oxjwyuhrPMv.NU/ES7.hggJvkIZ/1YPOfjf4x0Qccpu7Uu/JK.', 'user_13.png', 'REVENUE INSPECTOR', 5, 'active', '2026-08-29 05:19:45'),
(14, 'tax-collector', 'tax collector', 'tax-collector@gmail.com', '$2y$10$V6zQNBOoVZYaRjWjjaKFtOwKyIM9RfiLPiSl2FA5JqOdagzmg8SEC', 'user_14.png', 'TAX COLLECTOR', 6, 'active', '2026-08-29 05:20:53'),
(15, 'tax-superintendent', 'tax superintendent', 'tax-superintendent@gmail.com', '$2y$10$KCqr3iOtdCHk8IeD7y/obOxch.3j5evEXyVsOFSXcYCwXy9wXZpv2', 'user_15.png', 'TAX SUPERINTENDENT / KNA', 4, 'active', '2026-08-29 05:22:00'),
(16, 'clerk', 'clerk', 'clerk@gmail.com', '$2y$10$bMB0mTo5myxzRqnkitBHH.nie0VY/Vr6LWbUA0t5CzKYkaNmZeK5C', 'user_16.png', 'CLERK', 7, 'active', '2026-08-29 09:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `wards`
--

CREATE TABLE `wards` (
  `ward_id` int(11) NOT NULL,
  `ward_no` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wards`
--

INSERT INTO `wards` (`ward_id`, `ward_no`) VALUES
(1, '1- TITAUWA (टिटौवा)'),
(10, '10- PATHAN TOLA PURVI (पठान टोला पूर्वी)'),
(11, '11- BANJARIYA PASHCHMI (बंजरिया पश्चिमी)'),
(12, '12- AUDYOGIK NAGAR (औद्योगिक नगर)'),
(13, '13- BHITWA TOLA (भिटवा टोला)'),
(14, '14- MATIHANA (मटिहना)'),
(15, '15- BANJARIYA PURVI (बंजरिया पूर्वी)'),
(16, '16- BARDAHIYA BAZAR (बरदहिया बाजार)'),
(17, '17- MOTINAGAR (मोतीनगर)'),
(18, '18- STATION PURWA (स्टेशन पुरवा)'),
(19, '19- ANSAR TOLA (अंसार टोला)'),
(2, '2- BIDHIYANI (बिधियानी)'),
(20, '20- MALI TOLA (माली टोला)'),
(21, '21- SHASTRI NAGAR (शास्त्री नगर)'),
(22, '22- PURANI TEHSIL (पुरानी तहसील)'),
(23, '23- GOLA BAZAR DAKCHINI (गोला बाजार दक्षिणी)'),
(24, '24- GOLA BAZAR UTTARI (गोला बाजार उत्तरी)'),
(25, '25- PATHAN TOLA PASHCHMI (पठान टोला पश्चिमी)'),
(3, '3- ACHAKWAPUR (अचकवापुर)'),
(4, '4- MADYA (मड़या)'),
(5, '5- BARAI TOLA (बरई टोला)'),
(6, '6- PRANI TEHSIL DAKCHNI (पुरानी तहसील दक्षिणी)'),
(7, '7- BAGAHIYA (बगहिया)'),
(8, '8- TRIPATHINAGAR (त्रिपाठीनगर)'),
(9, '9- GORKHAR (गोरखर)');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessment_floors`
--
ALTER TABLE `assessment_floors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessment_media`
--
ALTER TABLE `assessment_media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessment_owners`
--
ALTER TABLE `assessment_owners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessment_updates_history`
--
ALTER TABLE `assessment_updates_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assessment_id` (`assessment_id`),
  ADD KEY `idx_property_id` (`property_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `assessment_verifications`
--
ALTER TABLE `assessment_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `verification_step` (`verification_step`);

--
-- Indexes for table `mohalla`
--
ALTER TABLE `mohalla`
  ADD PRIMARY KEY (`mohalla_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_key` (`permission_key`);

--
-- Indexes for table `property_arv_details`
--
ALTER TABLE `property_arv_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `surveyors`
--
ALTER TABLE `surveyors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `wards`
--
ALTER TABLE `wards`
  ADD PRIMARY KEY (`ward_id`),
  ADD UNIQUE KEY `ward_no` (`ward_no`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `assessment_floors`
--
ALTER TABLE `assessment_floors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assessment_media`
--
ALTER TABLE `assessment_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_owners`
--
ALTER TABLE `assessment_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `assessment_updates_history`
--
ALTER TABLE `assessment_updates_history`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `assessment_verifications`
--
ALTER TABLE `assessment_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mohalla`
--
ALTER TABLE `mohalla`
  MODIFY `mohalla_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `property_arv_details`
--
ALTER TABLE `property_arv_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=713;

--
-- AUTO_INCREMENT for table `surveyors`
--
ALTER TABLE `surveyors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `wards`
--
ALTER TABLE `wards`
  MODIFY `ward_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
