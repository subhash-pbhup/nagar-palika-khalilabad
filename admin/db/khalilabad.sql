-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 09:48 AM
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
(1, 'Nagar Palika Parishad Khalilabad', '2026-2027', 1, 1, 1, 'UP700001', '1', 'KLB-W1-00001', NULL, 'new', NULL, 'OLD3001', 'Residential', 'Alley', 107.35, '0', '26.7521', '83.0524', '101/A', '', '', '', 'House 1, Ward 1, Khalilabad', 'House 1, Ward 1, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(2, 'Nagar Palika Parishad Khalilabad', '2026-2027', 2, 2, 2, 'UP700002', '2', 'KLB-W2-00001', NULL, 'new', NULL, 'OLD3002', 'Commercial', 'Main Road', 114.70, '0', '26.7542', '83.0548', '102/A', '', '', '', 'House 2, Ward 2, Khalilabad', 'House 2, Ward 2, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(3, 'Nagar Palika Parishad Khalilabad', '2026-2027', 3, 3, 3, 'UP700003', '3', 'KLB-W3-00001', NULL, 'old', NULL, 'OLD3003', 'Mixed', 'Alley', 122.05, '0', '26.7563', '83.0572', '103/A', '', '', '', 'House 3, Ward 3, Khalilabad', 'House 3, Ward 3, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(4, 'Nagar Palika Parishad Khalilabad', '2026-2027', 4, 4, 4, 'UP700004', '4', 'KLB-W4-00001', NULL, 'new', NULL, 'OLD3004', 'Residential', 'Main Road', 129.40, '0', '26.7584', '83.0596', '104/A', '', '', '', 'House 4, Ward 4, Khalilabad', 'House 4, Ward 4, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(5, 'Nagar Palika Parishad Khalilabad', '2026-2027', 5, 5, 5, 'UP700005', '5', 'KLB-W5-00001', NULL, 'new', NULL, 'OLD3005', 'Commercial', 'Inner Road', 136.75, '0', '26.7605', '83.062', '105/A', '', '', '', 'House 5, Ward 5, Khalilabad', 'House 5, Ward 5, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(6, 'Nagar Palika Parishad Khalilabad', '2026-2027', 1, 6, 6, 'UP700006', '6', 'KLB-W6-00001', NULL, 'old', NULL, 'OLD3006', 'Mixed', 'Main Road', 144.10, '0', '26.7626', '83.0644', '106/A', '', '', '', 'House 6, Ward 6, Khalilabad', 'House 6, Ward 6, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(7, 'Nagar Palika Parishad Khalilabad', '2026-2027', 2, 7, 7, 'UP700007', '7', 'KLB-W7-00001', NULL, 'new', NULL, 'OLD3007', 'Residential', 'Inner Road', 151.45, '0', '26.7647', '83.0668', '107/A', '', '', '', 'House 7, Ward 7, Khalilabad', 'House 7, Ward 7, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(8, 'Nagar Palika Parishad Khalilabad', '2026-2027', 3, 8, 8, 'UP700008', '8', 'KLB-W8-00001', NULL, 'new', NULL, 'OLD3008', 'Commercial', 'Alley', 158.80, '0', '26.7668', '83.0692', '108/A', '', '', '', 'House 8, Ward 8, Khalilabad', 'House 8, Ward 8, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(9, 'Nagar Palika Parishad Khalilabad', '2026-2027', 4, 9, 9, 'UP700009', '9', 'KLB-W9-00001', NULL, 'old', NULL, 'OLD3009', 'Mixed', 'Main Road', 166.15, '0', '26.7689', '83.0716', '109/A', '', '', '', 'House 9, Ward 9, Khalilabad', 'House 9, Ward 9, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(10, 'Nagar Palika Parishad Khalilabad', '2026-2027', 5, 10, 10, 'UP700010', '10', 'KLB-W10-00001', NULL, 'new', NULL, 'OLD3010', 'Residential', 'Inner Road', 173.50, '0', '26.771', '83.074', '110/A', '', '', '', 'House 10, Ward 10, Khalilabad', 'House 10, Ward 10, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(11, 'Nagar Palika Parishad Khalilabad', '2026-2027', 1, 11, 11, 'UP700011', '11', 'KLB-W11-00001', NULL, 'new', NULL, 'OLD3011', 'Commercial', 'Alley', 180.85, '0', '26.7731', '83.0764', '111/A', '', '', '', 'House 11, Ward 11, Khalilabad', 'House 11, Ward 11, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(12, 'Nagar Palika Parishad Khalilabad', '2026-2027', 2, 12, 12, 'UP700012', '12', 'KLB-W12-00001', NULL, 'old', NULL, 'OLD3012', 'Mixed', 'Main Road', 188.20, '0', '26.7752', '83.0788', '112/A', '', '', '', 'House 12, Ward 12, Khalilabad', 'House 12, Ward 12, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(13, 'Nagar Palika Parishad Khalilabad', '2026-2027', 3, 13, 13, 'UP700013', '13', 'KLB-W13-00001', NULL, 'new', NULL, 'OLD3013', 'Residential', 'Alley', 195.55, '0', '26.7773', '83.0812', '113/A', '', '', '', 'House 13, Ward 13, Khalilabad', 'House 13, Ward 13, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(14, 'Nagar Palika Parishad Khalilabad', '2026-2027', 4, 14, 14, 'UP700014', '14', 'KLB-W14-00001', NULL, 'new', NULL, 'OLD3014', 'Commercial', 'Main Road', 202.90, '0', '26.7794', '83.0836', '114/A', '', '', '', 'House 14, Ward 14, Khalilabad', 'House 14, Ward 14, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(15, 'Nagar Palika Parishad Khalilabad', '2026-2027', 5, 15, 15, 'UP700015', '15', 'KLB-W15-00001', NULL, 'old', NULL, 'OLD3015', 'Mixed', 'Inner Road', 210.25, '0', '26.7815', '83.086', '115/A', '', '', '', 'House 15, Ward 15, Khalilabad', 'House 15, Ward 15, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(16, 'Nagar Palika Parishad Khalilabad', '2026-2027', 1, 16, 16, 'UP700016', '16', 'KLB-W16-00001', NULL, 'new', NULL, 'OLD3016', 'Residential', 'Main Road', 217.60, '0', '26.7836', '83.0884', '116/A', '', '', '', 'House 16, Ward 16, Khalilabad', 'House 16, Ward 16, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(17, 'Nagar Palika Parishad Khalilabad', '2026-2027', 2, 17, 17, 'UP700017', '17', 'KLB-W17-00001', NULL, 'new', NULL, 'OLD3017', 'Commercial', 'Inner Road', 224.95, '0', '26.7857', '83.0908', '117/A', '', '', '', 'House 17, Ward 17, Khalilabad', 'House 17, Ward 17, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(18, 'Nagar Palika Parishad Khalilabad', '2026-2027', 3, 18, 18, 'UP700018', '18', 'KLB-W18-00001', NULL, 'old', NULL, 'OLD3018', 'Mixed', 'Alley', 232.30, '0', '26.7878', '83.0932', '118/A', '', '', '', 'House 18, Ward 18, Khalilabad', 'House 18, Ward 18, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(19, 'Nagar Palika Parishad Khalilabad', '2026-2027', 4, 19, 19, 'UP700019', '19', 'KLB-W19-00001', NULL, 'new', NULL, 'OLD3019', 'Residential', 'Main Road', 239.65, '0', '26.7899', '83.0956', '119/A', '', '', '', 'House 19, Ward 19, Khalilabad', 'House 19, Ward 19, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(20, 'Nagar Palika Parishad Khalilabad', '2026-2027', 5, 20, 20, 'UP700020', '20', 'KLB-W20-00001', NULL, 'new', NULL, 'OLD3020', 'Commercial', 'Inner Road', 247.00, '0', '26.792', '83.098', '120/A', '', '', '', 'House 20, Ward 20, Khalilabad', 'House 20, Ward 20, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(21, 'Nagar Palika Parishad Khalilabad', '2026-2027', 1, 21, 21, 'UP700021', '21', 'KLB-W21-00001', NULL, 'old', NULL, 'OLD3021', 'Mixed', 'Alley', 254.35, '0', '26.7941', '83.1004', '121/A', '', '', '', 'House 21, Ward 21, Khalilabad', 'House 21, Ward 21, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(22, 'Nagar Palika Parishad Khalilabad', '2026-2027', 2, 22, 22, 'UP700022', '22', 'KLB-W22-00001', NULL, 'new', NULL, 'OLD3022', 'Residential', 'Main Road', 261.70, '0', '26.7962', '83.1028', '122/A', '', '', '', 'House 22, Ward 22, Khalilabad', 'House 22, Ward 22, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(23, 'Nagar Palika Parishad Khalilabad', '2026-2027', 3, 23, 23, 'UP700023', '23', 'KLB-W23-00001', NULL, 'new', NULL, 'OLD3023', 'Commercial', 'Alley', 269.05, '0', '26.7983', '83.1052', '123/A', '', '', '', 'House 23, Ward 23, Khalilabad', 'House 23, Ward 23, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(24, 'Nagar Palika Parishad Khalilabad', '2026-2027', 4, 24, 24, 'UP700024', '24', 'KLB-W24-00001', NULL, 'old', NULL, 'OLD3024', 'Mixed', 'Main Road', 276.40, '0', '26.8004', '83.1076', '124/A', '', '', '', 'House 24, Ward 24, Khalilabad', 'House 24, Ward 24, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(25, 'Nagar Palika Parishad Khalilabad', '2026-2027', 5, 25, 25, 'UP700025', '25', 'KLB-W25-00001', NULL, 'new', NULL, 'OLD3025', 'Residential', 'Inner Road', 283.75, '0', '26.8025', '83.11', '125/A', '', '', '', 'House 25, Ward 25, Khalilabad', 'House 25, Ward 25, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(26, 'Nagar Palika Parishad Khalilabad', '2026-2027', 1, 1, 26, 'UP700026', '1', 'KLB-W1-00002', NULL, 'new', NULL, 'OLD3026', 'Commercial', 'Main Road', 291.10, '0', '26.8046', '83.1124', '126/A', '', '', '', 'House 26, Ward 1, Khalilabad', 'House 26, Ward 1, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(27, 'Nagar Palika Parishad Khalilabad', '2026-2027', 2, 2, 27, 'UP700027', '2', 'KLB-W2-00002', NULL, 'old', NULL, 'OLD3027', 'Mixed', 'Inner Road', 298.45, '0', '26.8067', '83.1148', '127/A', '', '', '', 'House 27, Ward 2, Khalilabad', 'House 27, Ward 2, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(28, 'Nagar Palika Parishad Khalilabad', '2026-2027', 3, 3, 28, 'UP700028', '3', 'KLB-W3-00002', NULL, 'new', NULL, 'OLD3028', 'Residential', 'Alley', 305.80, '0', '26.8088', '83.1172', '128/A', '', '', '', 'House 28, Ward 3, Khalilabad', 'House 28, Ward 3, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(29, 'Nagar Palika Parishad Khalilabad', '2026-2027', 4, 4, 29, 'UP700029', '4', 'KLB-W4-00002', NULL, 'new', NULL, 'OLD3029', 'Commercial', 'Main Road', 313.15, '0', '26.8109', '83.1196', '129/A', '', '', '', 'House 29, Ward 4, Khalilabad', 'House 29, Ward 4, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(30, 'Nagar Palika Parishad Khalilabad', '2026-2027', 5, 5, 30, 'UP700030', '5', 'KLB-W5-00002', NULL, 'old', NULL, 'OLD3030', 'Mixed', 'Inner Road', 320.50, '0', '26.813', '83.122', '130/A', '', '', '', 'House 30, Ward 5, Khalilabad', 'House 30, Ward 5, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(31, 'Nagar Palika Parishad Khalilabad', '2026-2027', 1, 6, 31, 'UP700031', '6', 'KLB-W6-00002', NULL, 'new', NULL, 'OLD3031', 'Residential', 'Alley', 327.85, '0', '26.8151', '83.1244', '131/A', '', '', '', 'House 31, Ward 6, Khalilabad', 'House 31, Ward 6, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(32, 'Nagar Palika Parishad Khalilabad', '2026-2027', 2, 7, 32, 'UP700032', '7', 'KLB-W7-00002', NULL, 'new', NULL, 'OLD3032', 'Commercial', 'Main Road', 335.20, '0', '26.8172', '83.1268', '132/A', '', '', '', 'House 32, Ward 7, Khalilabad', 'House 32, Ward 7, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(33, 'Nagar Palika Parishad Khalilabad', '2026-2027', 3, 8, 33, 'UP700033', '8', 'KLB-W8-00002', NULL, 'old', NULL, 'OLD3033', 'Mixed', 'Alley', 342.55, '0', '26.8193', '83.1292', '133/A', '', '', '', 'House 33, Ward 8, Khalilabad', 'House 33, Ward 8, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(34, 'Nagar Palika Parishad Khalilabad', '2026-2027', 4, 9, 34, 'UP700034', '9', 'KLB-W9-00002', NULL, 'new', NULL, 'OLD3034', 'Residential', 'Main Road', 349.90, '0', '26.8214', '83.1316', '134/A', '', '', '', 'House 34, Ward 9, Khalilabad', 'House 34, Ward 9, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(35, 'Nagar Palika Parishad Khalilabad', '2026-2027', 5, 10, 35, 'UP700035', '10', 'KLB-W10-00002', NULL, 'new', NULL, 'OLD3035', 'Commercial', 'Inner Road', 357.25, '0', '26.8235', '83.134', '135/A', '', '', '', 'House 35, Ward 10, Khalilabad', 'House 35, Ward 10, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(36, 'Nagar Palika Parishad Khalilabad', '2026-2027', 1, 11, 36, 'UP700036', '11', 'KLB-W11-00002', NULL, 'old', NULL, 'OLD3036', 'Mixed', 'Main Road', 364.60, '0', '26.8256', '83.1364', '136/A', '', '', '', 'House 36, Ward 11, Khalilabad', 'House 36, Ward 11, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(37, 'Nagar Palika Parishad Khalilabad', '2026-2027', 2, 12, 37, 'UP700037', '12', 'KLB-W12-00002', NULL, 'new', NULL, 'OLD3037', 'Residential', 'Inner Road', 371.95, '0', '26.8277', '83.1388', '137/A', '', '', '', 'House 37, Ward 12, Khalilabad', 'House 37, Ward 12, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(38, 'Nagar Palika Parishad Khalilabad', '2026-2027', 3, 13, 38, 'UP700038', '13', 'KLB-W13-00002', NULL, 'new', NULL, 'OLD3038', 'Commercial', 'Alley', 379.30, '0', '26.8298', '83.1412', '138/A', '', '', '', 'House 38, Ward 13, Khalilabad', 'House 38, Ward 13, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(39, 'Nagar Palika Parishad Khalilabad', '2026-2027', 4, 14, 39, 'UP700039', '14', 'KLB-W14-00002', NULL, 'old', NULL, 'OLD3039', 'Mixed', 'Main Road', 386.65, '0', '26.8319', '83.1436', '139/A', '', '', '', 'House 39, Ward 14, Khalilabad', 'House 39, Ward 14, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(40, 'Nagar Palika Parishad Khalilabad', '2026-2027', 5, 15, 40, 'UP700040', '15', 'KLB-W15-00002', NULL, 'new', NULL, 'OLD3040', 'Residential', 'Inner Road', 394.00, '0', '26.834', '83.146', '140/A', '', '', '', 'House 40, Ward 15, Khalilabad', 'House 40, Ward 15, Khalilabad', NULL, 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:35:55', 0, NULL, NULL, NULL, 12, NULL, '2026-08-31 07:35:55', 'web', 'approved', NULL, NULL, NULL, NULL),
(41, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520025R', '2', 'KLB-W2-00003', NULL, 'old', NULL, '0', '14', '1', 110.00, '1', '0', '0', '9A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(42, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520027R', '2', 'KLB-W2-00004', NULL, 'old', NULL, '0', '14', '1', 330.00, '1', '0', '0', '13', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(43, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520029R', '2', 'KLB-W2-00005', NULL, 'old', NULL, '0', '14', '1', 500.00, '1', '0', '0', '35A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(44, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520031R', '2', 'KLB-W2-00006', NULL, 'old', NULL, '0', '14', '1', 380.00, '1', '0', '0', '35B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(45, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520033R', '2', 'KLB-W2-00007', NULL, 'old', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '53A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(46, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520035R', '2', 'KLB-W2-00008', NULL, 'old', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '66B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(47, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520037R', '2', 'KLB-W2-00009', NULL, 'old', NULL, '0', '14', '1', 625.00, '1', '0', '0', '76A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(48, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520039R', '2', 'KLB-W2-00010', NULL, 'old', NULL, '0', '14', '1', 675.00, '1', '0', '0', '76B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(49, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520041R', '2', 'KLB-W2-00011', NULL, 'new', NULL, '0', '14', '1', 2110.00, '1', '0', '0', '79A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(50, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520043R', '2', 'KLB-W2-00012', NULL, 'new', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '79B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(51, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520045R', '2', 'KLB-W2-00013', NULL, 'new', NULL, '0', '14', '1', 1869.00, '1', '0', '0', '88A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(52, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520047R', '2', 'KLB-W2-00014', NULL, 'new', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '88B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(53, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520049R', '2', 'KLB-W2-00015', NULL, 'new', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '89A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(54, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520051R', '2', 'KLB-W2-00016', NULL, 'new', NULL, '0', '14', '1', 9999.00, '1', '0', '0', '89B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(55, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520053R', '2', 'KLB-W2-00017', NULL, 'new', NULL, '0', '14', '1', 1520.00, '1', '0', '0', '90A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(56, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520055R', '2', 'KLB-W2-00018', NULL, 'new', NULL, '0', '14', '1', 900.00, '1', '0', '0', '90B', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL),
(57, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 23, 54, '0952601002520057R', '2', 'KLB-W2-00019', NULL, 'New', '', '', 'Industrial', '12 to 24 Meters', 41.00, 'Vaccant Land', '25.930283396214534', '81.7170822026966', 'Tenetur nihil quia s', 'A id ipsam veritatis', 'Doloribus dolor assu', 'Cumque architecto do', 'In neque veritatis q', 'Duis deserunt qui li', '230128', 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, 1, '2026-08-31 07:44:45', 'web', 'approved', NULL, NULL, NULL, NULL),
(58, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 5, 36, '0952601002520059R', '2', 'KLB-W2-00020', NULL, 'New', '', '', 'Non Residential', '12 to 24 Meters', 46.00, 'Vaccant Land', '25.93029371838731', '81.71701615043119', 'Ducimus eaque volup', 'Irure voluptatum ita', 'Voluptatem amet imp', 'Dolor ut non commodo', 'Tempore nobis ipsum', 'Aliquam sed voluptas', '230128', 1.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, 1, '2026-08-31 07:46:07', 'web', 'approved', NULL, NULL, NULL, NULL),
(59, 'Nagar Palika Parishad Khalilabad', '2025-26', 1, 2, 33, '0952601002520061R', '2', 'KLB-W2-00021', NULL, 'new', NULL, 'NA', '14', '1', 674.00, '1', '0', '0', '121A', '', '', '', 'BIDHIYANI', 'BIDHIYANI', NULL, 0.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-31 07:41:35', 0, NULL, NULL, NULL, 1, NULL, '2026-08-31 07:41:35', 'web', 'approved', NULL, NULL, NULL, NULL);

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
(1, 57, 'Sixth Floor - 7', '', 'Vacant Land', '2019-01-26', '2013-09-17', 'Tenanted (T)', 56.00, 'Industrial', 'group2', 'Non Govt. Coaching Center', '2026-08-31 07:44:45', 0, NULL, NULL, NULL),
(2, 58, 'Second Floor - 3', 'Residential', 'ACC', '2016-12-13', '1989-06-24', 'Tenanted (T)', 90.00, 'Fully Residential', '', '', '2026-08-31 07:46:07', 0, NULL, NULL, NULL),
(3, 58, 'Fifth Floor - 6', '', 'RCC', '1974-11-28', '2023-01-30', 'Self-Occupied (S)', 85.00, 'Non-Residential', 'group4', 'All Other Commercial Buildings Which Are Not Included In Above Mentioned', '2026-08-31 07:46:07', 0, NULL, NULL, NULL);

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
(1, 1, 'Rahul Sharma', 'Ram Sharma', NULL, '9300000001', NULL, 0, NULL, NULL, NULL),
(2, 2, 'Amit Kumar', 'Shiv Kumar', NULL, '9300000002', NULL, 0, NULL, NULL, NULL),
(3, 3, 'Priya Singh', 'Ashok Singh', NULL, '9300000003', NULL, 0, NULL, NULL, NULL),
(4, 4, 'Sneha Gupta', 'Rajendra Gupta', NULL, '9300000004', NULL, 0, NULL, NULL, NULL),
(5, 5, 'Vikram Verma', 'Dinesh Verma', NULL, '9300000005', NULL, 0, NULL, NULL, NULL),
(6, 6, 'Ramesh Yadav', 'Babu Yadav', NULL, '9300000006', NULL, 0, NULL, NULL, NULL),
(7, 7, 'Suresh Tiwari', 'Ganesh Tiwari', NULL, '9300000007', NULL, 0, NULL, NULL, NULL),
(8, 8, 'Anita Mishra', 'Rakesh Mishra', NULL, '9300000008', NULL, 0, NULL, NULL, NULL),
(9, 9, 'Pooja Patel', 'Kamlesh Patel', NULL, '9300000009', NULL, 0, NULL, NULL, NULL),
(10, 10, 'Ravi Das', 'Mohan Das', NULL, '9300000010', NULL, 0, NULL, NULL, NULL),
(11, 11, 'Sanjay Chaurasia', 'Hari Chaurasia', NULL, '9300000011', NULL, 0, NULL, NULL, NULL),
(12, 12, 'Kiran Devi', 'Lal Devi', NULL, '9300000012', NULL, 0, NULL, NULL, NULL),
(13, 13, 'Sunil Sonkar', 'Raj Sonkar', NULL, '9300000013', NULL, 0, NULL, NULL, NULL),
(14, 14, 'Anjali Pandey', 'Brij Pandey', NULL, '9300000014', NULL, 0, NULL, NULL, NULL),
(15, 15, 'Deepak Chauhan', 'Mahendra Chauhan', NULL, '9300000015', NULL, 0, NULL, NULL, NULL),
(16, 16, 'Gaurav Tripathi', 'Kailash Tripathi', NULL, '9300000016', NULL, 0, NULL, NULL, NULL),
(17, 17, 'Neha Agrawal', 'Om Agrawal', NULL, '9300000017', NULL, 0, NULL, NULL, NULL),
(18, 18, 'Arun Maurya', 'Nand Maurya', NULL, '9300000018', NULL, 0, NULL, NULL, NULL),
(19, 19, 'Vikas Singh', 'Surya Singh', NULL, '9300000019', NULL, 0, NULL, NULL, NULL),
(20, 20, 'Rajesh Jaiswal', 'Vijay Jaiswal', NULL, '9300000020', NULL, 0, NULL, NULL, NULL),
(21, 21, 'Rahul Sharma', 'Ram Sharma', NULL, '9300000021', NULL, 0, NULL, NULL, NULL),
(22, 22, 'Amit Kumar', 'Shiv Kumar', NULL, '9300000022', NULL, 0, NULL, NULL, NULL),
(23, 23, 'Priya Singh', 'Ashok Singh', NULL, '9300000023', NULL, 0, NULL, NULL, NULL),
(24, 24, 'Sneha Gupta', 'Rajendra Gupta', NULL, '9300000024', NULL, 0, NULL, NULL, NULL),
(25, 25, 'Vikram Verma', 'Dinesh Verma', NULL, '9300000025', NULL, 0, NULL, NULL, NULL),
(26, 26, 'Ramesh Yadav', 'Babu Yadav', NULL, '9300000026', NULL, 0, NULL, NULL, NULL),
(27, 27, 'Suresh Tiwari', 'Ganesh Tiwari', NULL, '9300000027', NULL, 0, NULL, NULL, NULL),
(28, 28, 'Anita Mishra', 'Rakesh Mishra', NULL, '9300000028', NULL, 0, NULL, NULL, NULL),
(29, 29, 'Pooja Patel', 'Kamlesh Patel', NULL, '9300000029', NULL, 0, NULL, NULL, NULL),
(30, 30, 'Ravi Das', 'Mohan Das', NULL, '9300000030', NULL, 0, NULL, NULL, NULL),
(31, 31, 'Sanjay Chaurasia', 'Hari Chaurasia', NULL, '9300000031', NULL, 0, NULL, NULL, NULL),
(32, 32, 'Kiran Devi', 'Lal Devi', NULL, '9300000032', NULL, 0, NULL, NULL, NULL),
(33, 33, 'Sunil Sonkar', 'Raj Sonkar', NULL, '9300000033', NULL, 0, NULL, NULL, NULL),
(34, 34, 'Anjali Pandey', 'Brij Pandey', NULL, '9300000034', NULL, 0, NULL, NULL, NULL),
(35, 35, 'Deepak Chauhan', 'Mahendra Chauhan', NULL, '9300000035', NULL, 0, NULL, NULL, NULL),
(36, 36, 'Gaurav Tripathi', 'Kailash Tripathi', NULL, '9300000036', NULL, 0, NULL, NULL, NULL),
(37, 37, 'Neha Agrawal', 'Om Agrawal', NULL, '9300000037', NULL, 0, NULL, NULL, NULL),
(38, 38, 'Arun Maurya', 'Nand Maurya', NULL, '9300000038', NULL, 0, NULL, NULL, NULL),
(39, 39, 'Vikas Singh', 'Surya Singh', NULL, '9300000039', NULL, 0, NULL, NULL, NULL),
(40, 40, 'Rajesh Jaiswal', 'Vijay Jaiswal', NULL, '9300000040', NULL, 0, NULL, NULL, NULL),
(41, 41, 'TASAUWAR HUSAN', 'MOH WAX', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(42, 42, 'SHABBIR AHAMA', 'ALIRAZA', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(43, 43, 'JAIBULLA NISHA, MANOWA, SAMSAD', 'HASAN RAZA', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(44, 44, 'GULFESHA KHATUN', 'MUZABIL HUSAN', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(45, 45, 'KUDDYASH', 'SAMI ULLHA', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(46, 46, 'AKHTAR AJEEBULHA ADI', 'BITAULLAH', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(47, 47, 'HAYAT', 'HOSHILDAR', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(48, 48, 'MOH HARISH', 'HOSHILDAR', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(49, 49, 'ZAHIR AHAMAD', 'AKULL GAFFAR', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(50, 50, 'SAYAD ALI, SAYAD AHAMAD, MAMIN NABI, ABDUL GAFFAR, MUSTAKIM- OTH', 'ABDUL KHALID', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(51, 51, 'MOH GUJRATI', 'HIDAYAT', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(52, 52, 'MOH GUJRATI', 'HIDAYAT', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(53, 53, 'MOH JUMRATI', 'HIDAYAT', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(54, 54, 'MOH JUMRATI', 'HIDAYAT', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(55, 55, 'ABDUL RAHIM,ABDUL AJEEJ ABDUL HADISH', 'MOH ALI', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(56, 56, 'ABDUL RAHIM,ABDUL AJEEJ ABDUL HADISH', 'MOH ALI', NULL, '9999999999', NULL, 0, NULL, NULL, NULL),
(57, 57, 'RAJENDRA', 'MITTHU', '', '9999999999', '', 0, NULL, NULL, NULL),
(58, 58, 'RANJEET YADAV', 'NAKCHEPP YADAV', '', '9999999999', '', 0, NULL, NULL, NULL),
(59, 59, 'LAKCHED', 'SURYA BALI', NULL, '9999999999', NULL, 0, NULL, NULL, NULL);

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
(1, 1, 1, 1, 1, '2026-2027', NULL, 'Kachha', NULL, 'UP700001', 'BILL10001', '2026-08-01', 3250.75, 0.00, 390.09, 0.00, 0.00, 130.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 520.12, 1, '2026-08-31 07:35:55', 390.09, 0.00, 0.00, 390.09, 130.03, 0.00, 0.00, 130.03, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(2, 2, 2, 2, 2, '2026-2027', NULL, 'Pakka', NULL, 'UP700002', 'BILL10002', '2026-08-01', 3501.50, 0.00, 420.18, 0.00, 0.00, 140.06, 0.00, 0.00, 98.82, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 659.06, 1, '2026-08-31 07:35:55', 420.18, 0.00, 0.00, 420.18, 140.06, 0.00, 0.00, 140.06, 81.00, 16.20, 1.62, 98.82, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(3, 3, 3, 3, 3, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700003', 'BILL10003', '2026-08-01', 3752.25, 0.00, 450.27, 0.00, 0.00, 150.09, 0.00, 0.00, 0.00, 0.00, 0.00, 75.05, 0.00, 0.00, 0.00, 675.41, 1, '2026-08-31 07:35:55', 450.27, 0.00, 0.00, 450.27, 150.09, 0.00, 0.00, 150.09, 0.00, 0.00, 0.00, 0.00, 75.05, 0.00, 0.00, 75.05, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(4, 4, 4, 4, 4, '2026-2027', NULL, 'Pakka', NULL, 'UP700004', 'BILL10004', '2026-08-01', 4003.00, 0.00, 1008.76, 0.00, 0.00, 160.12, 0.00, 0.00, 106.14, 0.00, 0.00, 0.00, 0.00, 0.00, 85.40, 1360.42, 1, '2026-08-31 07:35:55', 480.36, 480.36, 48.04, 1008.76, 160.12, 0.00, 0.00, 160.12, 87.00, 17.40, 1.74, 106.14, 0.00, 0.00, 0.00, 0.00, 70.00, 14.00, 1.40, 85.40, 0.00, 0.00, 'bulk'),
(5, 5, 5, 5, 5, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700005', 'BILL10005', '2026-08-01', 4253.75, 0.00, 510.45, 0.00, 0.00, 357.32, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 867.77, 1, '2026-08-31 07:35:55', 510.45, 0.00, 0.00, 510.45, 170.15, 170.15, 17.02, 357.32, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(6, 6, 1, 6, 6, '2026-2027', NULL, 'Kachha', NULL, 'UP700006', 'BILL10006', '2026-08-01', 4504.50, 0.00, 540.54, 0.00, 0.00, 180.18, 0.00, 0.00, 113.46, 0.00, 0.00, 189.19, 0.00, 0.00, 0.00, 1023.37, 1, '2026-08-31 07:35:55', 540.54, 0.00, 0.00, 540.54, 180.18, 0.00, 0.00, 180.18, 93.00, 18.60, 1.86, 113.46, 90.09, 90.09, 9.01, 189.19, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(7, 7, 2, 7, 7, '2026-2027', NULL, 'Pakka', NULL, 'UP700007', 'BILL10007', '2026-08-01', 4755.25, 0.00, 570.63, 0.00, 0.00, 190.21, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 760.84, 1, '2026-08-31 07:35:55', 570.63, 0.00, 0.00, 570.63, 190.21, 0.00, 0.00, 190.21, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(8, 8, 3, 8, 8, '2026-2027', NULL, 'Kachha', NULL, 'UP700008', 'BILL10008', '2026-08-01', 5006.00, 0.00, 1261.51, 0.00, 0.00, 200.24, 0.00, 0.00, 120.78, 0.00, 0.00, 0.00, 0.00, 0.00, 109.80, 1692.33, 1, '2026-08-31 07:35:55', 600.72, 600.72, 60.07, 1261.51, 200.24, 0.00, 0.00, 200.24, 99.00, 19.80, 1.98, 120.78, 0.00, 0.00, 0.00, 0.00, 90.00, 18.00, 1.80, 109.80, 0.00, 0.00, 'bulk'),
(9, 9, 4, 9, 9, '2026-2027', NULL, 'Pakka', NULL, 'UP700009', 'BILL10009', '2026-08-01', 5256.75, 0.00, 630.81, 0.00, 0.00, 210.27, 0.00, 0.00, 0.00, 0.00, 0.00, 105.14, 0.00, 0.00, 0.00, 946.22, 1, '2026-08-31 07:35:55', 630.81, 0.00, 0.00, 630.81, 210.27, 0.00, 0.00, 210.27, 0.00, 0.00, 0.00, 0.00, 105.14, 0.00, 0.00, 105.14, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(10, 10, 5, 10, 10, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700010', 'BILL10010', '2026-08-01', 5507.50, 0.00, 660.90, 0.00, 0.00, 462.63, 0.00, 0.00, 128.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1251.63, 1, '2026-08-31 07:35:55', 660.90, 0.00, 0.00, 660.90, 220.30, 220.30, 22.03, 462.63, 105.00, 21.00, 2.10, 128.10, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(11, 11, 1, 11, 11, '2026-2027', NULL, 'Kachha', NULL, 'UP700011', 'BILL10011', '2026-08-01', 5758.25, 0.00, 690.99, 0.00, 0.00, 230.33, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 921.32, 1, '2026-08-31 07:35:55', 690.99, 0.00, 0.00, 690.99, 230.33, 0.00, 0.00, 230.33, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(12, 12, 2, 12, 12, '2026-2027', NULL, 'Pakka', NULL, 'UP700012', 'BILL10012', '2026-08-01', 6009.00, 0.00, 1514.27, 0.00, 0.00, 240.36, 0.00, 0.00, 135.42, 0.00, 0.00, 252.38, 0.00, 0.00, 134.20, 2276.63, 1, '2026-08-31 07:35:55', 721.08, 721.08, 72.11, 1514.27, 240.36, 0.00, 0.00, 240.36, 111.00, 22.20, 2.22, 135.42, 120.18, 120.18, 12.02, 252.38, 110.00, 22.00, 2.20, 134.20, 0.00, 0.00, 'bulk'),
(13, 13, 3, 13, 13, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700013', 'BILL10013', '2026-08-01', 6259.75, 0.00, 751.17, 0.00, 0.00, 250.39, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1001.56, 1, '2026-08-31 07:35:55', 751.17, 0.00, 0.00, 751.17, 250.39, 0.00, 0.00, 250.39, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(14, 14, 4, 14, 14, '2026-2027', NULL, 'Pakka', NULL, 'UP700014', 'BILL10014', '2026-08-01', 6510.50, 0.00, 781.26, 0.00, 0.00, 260.42, 0.00, 0.00, 142.74, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1184.42, 1, '2026-08-31 07:35:55', 781.26, 0.00, 0.00, 781.26, 260.42, 0.00, 0.00, 260.42, 117.00, 23.40, 2.34, 142.74, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(15, 15, 5, 15, 15, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700015', 'BILL10015', '2026-08-01', 6761.25, 0.00, 811.35, 0.00, 0.00, 567.95, 0.00, 0.00, 0.00, 0.00, 0.00, 135.22, 0.00, 0.00, 0.00, 1514.52, 1, '2026-08-31 07:35:55', 811.35, 0.00, 0.00, 811.35, 270.45, 270.45, 27.05, 567.95, 0.00, 0.00, 0.00, 0.00, 135.22, 0.00, 0.00, 135.22, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(16, 16, 1, 16, 16, '2026-2027', NULL, 'Kachha', NULL, 'UP700016', 'BILL10016', '2026-08-01', 7012.00, 0.00, 1767.02, 0.00, 0.00, 280.48, 0.00, 0.00, 150.06, 0.00, 0.00, 0.00, 0.00, 0.00, 158.60, 2356.16, 1, '2026-08-31 07:35:55', 841.44, 841.44, 84.14, 1767.02, 280.48, 0.00, 0.00, 280.48, 123.00, 24.60, 2.46, 150.06, 0.00, 0.00, 0.00, 0.00, 130.00, 26.00, 2.60, 158.60, 0.00, 0.00, 'bulk'),
(17, 17, 2, 17, 17, '2026-2027', NULL, 'Pakka', NULL, 'UP700017', 'BILL10017', '2026-08-01', 7262.75, 0.00, 871.53, 0.00, 0.00, 290.51, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1162.04, 1, '2026-08-31 07:35:55', 871.53, 0.00, 0.00, 871.53, 290.51, 0.00, 0.00, 290.51, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(18, 18, 3, 18, 18, '2026-2027', NULL, 'Kachha', NULL, 'UP700018', 'BILL10018', '2026-08-01', 7513.50, 0.00, 901.62, 0.00, 0.00, 300.54, 0.00, 0.00, 157.38, 0.00, 0.00, 315.57, 0.00, 0.00, 0.00, 1675.11, 1, '2026-08-31 07:35:55', 901.62, 0.00, 0.00, 901.62, 300.54, 0.00, 0.00, 300.54, 129.00, 25.80, 2.58, 157.38, 150.27, 150.27, 15.03, 315.57, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(19, 19, 4, 19, 19, '2026-2027', NULL, 'Pakka', NULL, 'UP700019', 'BILL10019', '2026-08-01', 7764.25, 0.00, 931.71, 0.00, 0.00, 310.57, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1242.28, 1, '2026-08-31 07:35:55', 931.71, 0.00, 0.00, 931.71, 310.57, 0.00, 0.00, 310.57, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(20, 20, 5, 20, 20, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700020', 'BILL10020', '2026-08-01', 8015.00, 0.00, 2019.78, 0.00, 0.00, 673.26, 0.00, 0.00, 164.70, 0.00, 0.00, 0.00, 0.00, 0.00, 183.00, 3040.74, 1, '2026-08-31 07:35:55', 961.80, 961.80, 96.18, 2019.78, 320.60, 320.60, 32.06, 673.26, 135.00, 27.00, 2.70, 164.70, 0.00, 0.00, 0.00, 0.00, 150.00, 30.00, 3.00, 183.00, 0.00, 0.00, 'bulk'),
(21, 21, 1, 21, 21, '2026-2027', NULL, 'Kachha', NULL, 'UP700021', 'BILL10021', '2026-08-01', 8265.75, 0.00, 991.89, 0.00, 0.00, 330.63, 0.00, 0.00, 0.00, 0.00, 0.00, 165.31, 0.00, 0.00, 0.00, 1487.83, 1, '2026-08-31 07:35:55', 991.89, 0.00, 0.00, 991.89, 330.63, 0.00, 0.00, 330.63, 0.00, 0.00, 0.00, 0.00, 165.31, 0.00, 0.00, 165.31, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(22, 22, 2, 22, 22, '2026-2027', NULL, 'Pakka', NULL, 'UP700022', 'BILL10022', '2026-08-01', 8516.50, 0.00, 1021.98, 0.00, 0.00, 340.66, 0.00, 0.00, 172.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1534.66, 1, '2026-08-31 07:35:55', 1021.98, 0.00, 0.00, 1021.98, 340.66, 0.00, 0.00, 340.66, 141.00, 28.20, 2.82, 172.02, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(23, 23, 3, 23, 23, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700023', 'BILL10023', '2026-08-01', 8767.25, 0.00, 1052.07, 0.00, 0.00, 350.69, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1402.76, 1, '2026-08-31 07:35:55', 1052.07, 0.00, 0.00, 1052.07, 350.69, 0.00, 0.00, 350.69, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(24, 24, 4, 24, 24, '2026-2027', NULL, 'Pakka', NULL, 'UP700024', 'BILL10024', '2026-08-01', 9018.00, 0.00, 2272.54, 0.00, 0.00, 360.72, 0.00, 0.00, 179.34, 0.00, 0.00, 378.76, 0.00, 0.00, 207.40, 3398.76, 1, '2026-08-31 07:35:55', 1082.16, 1082.16, 108.22, 2272.54, 360.72, 0.00, 0.00, 360.72, 147.00, 29.40, 2.94, 179.34, 180.36, 180.36, 18.04, 378.76, 170.00, 34.00, 3.40, 207.40, 0.00, 0.00, 'bulk'),
(25, 25, 5, 25, 25, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700025', 'BILL10025', '2026-08-01', 9268.75, 0.00, 1112.25, 0.00, 0.00, 778.58, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1890.83, 1, '2026-08-31 07:35:55', 1112.25, 0.00, 0.00, 1112.25, 370.75, 370.75, 37.08, 778.58, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(26, 26, 1, 1, 26, '2026-2027', NULL, 'Kachha', NULL, 'UP700026', 'BILL10026', '2026-08-01', 9519.50, 0.00, 1142.34, 0.00, 0.00, 380.78, 0.00, 0.00, 186.66, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1709.78, 1, '2026-08-31 07:35:55', 1142.34, 0.00, 0.00, 1142.34, 380.78, 0.00, 0.00, 380.78, 153.00, 30.60, 3.06, 186.66, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(27, 27, 2, 2, 27, '2026-2027', NULL, 'Pakka', NULL, 'UP700027', 'BILL10027', '2026-08-01', 9770.25, 0.00, 1172.43, 0.00, 0.00, 390.81, 0.00, 0.00, 0.00, 0.00, 0.00, 195.41, 0.00, 0.00, 0.00, 1758.65, 1, '2026-08-31 07:35:55', 1172.43, 0.00, 0.00, 1172.43, 390.81, 0.00, 0.00, 390.81, 0.00, 0.00, 0.00, 0.00, 195.41, 0.00, 0.00, 195.41, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(28, 28, 3, 3, 28, '2026-2027', NULL, 'Kachha', NULL, 'UP700028', 'BILL10028', '2026-08-01', 10021.00, 0.00, 2525.29, 0.00, 0.00, 400.84, 0.00, 0.00, 193.98, 0.00, 0.00, 0.00, 0.00, 0.00, 231.80, 3351.91, 1, '2026-08-31 07:35:55', 1202.52, 1202.52, 120.25, 2525.29, 400.84, 0.00, 0.00, 400.84, 159.00, 31.80, 3.18, 193.98, 0.00, 0.00, 0.00, 0.00, 190.00, 38.00, 3.80, 231.80, 0.00, 0.00, 'bulk'),
(29, 29, 4, 4, 29, '2026-2027', NULL, 'Pakka', NULL, 'UP700029', 'BILL10029', '2026-08-01', 10271.75, 0.00, 1232.61, 0.00, 0.00, 410.87, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1643.48, 1, '2026-08-31 07:35:55', 1232.61, 0.00, 0.00, 1232.61, 410.87, 0.00, 0.00, 410.87, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(30, 30, 5, 5, 30, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700030', 'BILL10030', '2026-08-01', 10522.50, 0.00, 1262.70, 0.00, 0.00, 883.89, 0.00, 0.00, 201.30, 0.00, 0.00, 441.95, 0.00, 0.00, 0.00, 2789.84, 1, '2026-08-31 07:35:55', 1262.70, 0.00, 0.00, 1262.70, 420.90, 420.90, 42.09, 883.89, 165.00, 33.00, 3.30, 201.30, 210.45, 210.45, 21.05, 441.95, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(31, 31, 1, 6, 31, '2026-2027', NULL, 'Kachha', NULL, 'UP700031', 'BILL10031', '2026-08-01', 10773.25, 0.00, 1292.79, 0.00, 0.00, 430.93, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1723.72, 1, '2026-08-31 07:35:55', 1292.79, 0.00, 0.00, 1292.79, 430.93, 0.00, 0.00, 430.93, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(32, 32, 2, 7, 32, '2026-2027', NULL, 'Pakka', NULL, 'UP700032', 'BILL10032', '2026-08-01', 11024.00, 0.00, 2778.05, 0.00, 0.00, 440.96, 0.00, 0.00, 208.62, 0.00, 0.00, 0.00, 0.00, 0.00, 256.20, 3683.83, 1, '2026-08-31 07:35:55', 1322.88, 1322.88, 132.29, 2778.05, 440.96, 0.00, 0.00, 440.96, 171.00, 34.20, 3.42, 208.62, 0.00, 0.00, 0.00, 0.00, 210.00, 42.00, 4.20, 256.20, 0.00, 0.00, 'bulk'),
(33, 33, 3, 8, 33, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700033', 'BILL10033', '2026-08-01', 11274.75, 0.00, 1352.97, 0.00, 0.00, 450.99, 0.00, 0.00, 0.00, 0.00, 0.00, 225.50, 0.00, 0.00, 0.00, 2029.46, 1, '2026-08-31 07:35:55', 1352.97, 0.00, 0.00, 1352.97, 450.99, 0.00, 0.00, 450.99, 0.00, 0.00, 0.00, 0.00, 225.50, 0.00, 0.00, 225.50, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(34, 34, 4, 9, 34, '2026-2027', NULL, 'Pakka', NULL, 'UP700034', 'BILL10034', '2026-08-01', 11525.50, 0.00, 1383.06, 0.00, 0.00, 461.02, 0.00, 0.00, 215.94, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2060.02, 1, '2026-08-31 07:35:55', 1383.06, 0.00, 0.00, 1383.06, 461.02, 0.00, 0.00, 461.02, 177.00, 35.40, 3.54, 215.94, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(35, 35, 5, 10, 35, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700035', 'BILL10035', '2026-08-01', 11776.25, 0.00, 1413.15, 0.00, 0.00, 989.21, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2402.36, 1, '2026-08-31 07:35:55', 1413.15, 0.00, 0.00, 1413.15, 471.05, 471.05, 47.11, 989.21, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(36, 36, 1, 11, 36, '2026-2027', NULL, 'Kachha', NULL, 'UP700036', 'BILL10036', '2026-08-01', 12027.00, 0.00, 3030.80, 0.00, 0.00, 481.08, 0.00, 0.00, 223.26, 0.00, 0.00, 505.13, 0.00, 0.00, 280.60, 4520.87, 1, '2026-08-31 07:35:55', 1443.24, 1443.24, 144.32, 3030.80, 481.08, 0.00, 0.00, 481.08, 183.00, 36.60, 3.66, 223.26, 240.54, 240.54, 24.05, 505.13, 230.00, 46.00, 4.60, 280.60, 0.00, 0.00, 'bulk'),
(37, 37, 2, 12, 37, '2026-2027', NULL, 'Pakka', NULL, 'UP700037', 'BILL10037', '2026-08-01', 12277.75, 0.00, 1473.33, 0.00, 0.00, 491.11, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1964.44, 1, '2026-08-31 07:35:55', 1473.33, 0.00, 0.00, 1473.33, 491.11, 0.00, 0.00, 491.11, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(38, 38, 3, 13, 38, '2026-2027', NULL, 'Kachha', NULL, 'UP700038', 'BILL10038', '2026-08-01', 12528.50, 0.00, 1503.42, 0.00, 0.00, 501.14, 0.00, 0.00, 230.58, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2235.14, 1, '2026-08-31 07:35:55', 1503.42, 0.00, 0.00, 1503.42, 501.14, 0.00, 0.00, 501.14, 189.00, 37.80, 3.78, 230.58, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(39, 39, 4, 14, 39, '2026-2027', NULL, 'Pakka', NULL, 'UP700039', 'BILL10039', '2026-08-01', 12779.25, 0.00, 1533.51, 0.00, 0.00, 511.17, 0.00, 0.00, 0.00, 0.00, 0.00, 255.59, 0.00, 0.00, 0.00, 2300.27, 1, '2026-08-31 07:35:55', 1533.51, 0.00, 0.00, 1533.51, 511.17, 0.00, 0.00, 511.17, 0.00, 0.00, 0.00, 0.00, 255.59, 0.00, 0.00, 255.59, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(40, 40, 5, 15, 40, '2026-2027', NULL, 'Semi-Pakka', NULL, 'UP700040', 'BILL10040', '2026-08-01', 13030.00, 0.00, 3283.56, 0.00, 0.00, 1094.52, 0.00, 0.00, 237.90, 0.00, 0.00, 0.00, 0.00, 0.00, 305.00, 4920.98, 1, '2026-08-31 07:35:55', 1563.60, 1563.60, 156.36, 3283.56, 521.20, 521.20, 52.12, 1094.52, 195.00, 39.00, 3.90, 237.90, 0.00, 0.00, 0.00, 0.00, 250.00, 50.00, 5.00, 305.00, 0.00, 0.00, 'bulk'),
(41, 41, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520025R', '5.26E+13', '2025-04-01', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(42, 42, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520027R', '5.26E+13', '2025-04-01', 831.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(43, 43, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520029R', '5.26E+13', '2025-04-01', 1260.00, 0.00, 267.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 267.00, 1, '2026-08-31 07:41:35', 126.00, 126.00, 15.00, 267.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(44, 44, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520031R', '5.26E+13', '2025-04-01', 1368.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(45, 45, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520033R', '5.26E+13', '2025-04-01', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(46, 46, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520035R', '5.26E+13', '2025-04-01', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(47, 47, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520037R', '5.26E+13', '2025-04-01', 975.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 1, '2026-08-31 07:41:35', 100.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(48, 48, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520039R', '5.26E+13', '2025-04-01', 972.00, 0.00, 436.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 436.00, 1, '2026-08-31 07:41:35', 100.00, 324.00, 12.00, 436.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(49, 49, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520041R', '5.26E+13', '2025-04-01', 5072.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(50, 50, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520043R', '5.26E+13', '2025-04-01', 2000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(51, 51, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520045R', '5.26E+13', '2025-04-01', 2749.00, 0.00, 274.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 274.00, 1, '2026-08-31 07:41:35', 274.00, 0.00, 0.00, 274.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(52, 52, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520047R', '5.26E+13', '2025-04-01', 1000.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 100.00, 1, '2026-08-31 07:41:35', 100.00, 0.00, 0.00, 100.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(53, 53, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520049R', '5.26E+13', '2025-04-01', 1600.00, 0.00, 160.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 160.00, 1, '2026-08-31 07:41:35', 160.00, 0.00, 0.00, 160.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(54, 54, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520051R', '5.26E+13', '2025-04-01', 600.00, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 60.00, 1, '2026-08-31 07:41:35', 60.00, 0.00, 0.00, 60.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(55, 55, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520053R', '5.26E+13', '2025-04-01', 324.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(56, 56, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520055R', '5.26E+13', '2025-04-01', 1550.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(57, 57, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520057R', '5.26E+13', '2025-04-01', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(58, 58, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520059R', '5.26E+13', '2025-04-01', 1329.00, 0.00, 130.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 130.00, 1, '2026-08-31 07:41:35', 130.00, 0.00, 0.00, 130.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk'),
(59, 59, 1, 2, 33, '2025-26', NULL, '1', NULL, '0952601002520061R', '5.26E+13', '2025-04-01', 1607.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 1, '2026-08-31 07:41:35', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'bulk');

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
(293, 2, 17, '2026-08-29 06:10:56'),
(294, 2, 18, '2026-08-29 06:10:56'),
(295, 2, 2, '2026-08-29 06:10:56'),
(296, 2, 3, '2026-08-29 06:10:56'),
(297, 2, 4, '2026-08-29 06:10:56'),
(298, 2, 5, '2026-08-29 06:10:56'),
(299, 2, 6, '2026-08-29 06:10:56'),
(300, 2, 7, '2026-08-29 06:10:56'),
(301, 2, 1, '2026-08-29 06:10:56'),
(302, 2, 12, '2026-08-29 06:10:56'),
(303, 2, 13, '2026-08-29 06:10:56'),
(304, 2, 14, '2026-08-29 06:10:56'),
(305, 2, 15, '2026-08-29 06:10:56'),
(306, 2, 16, '2026-08-29 06:10:56'),
(307, 2, 23, '2026-08-29 06:10:56'),
(308, 2, 24, '2026-08-29 06:10:56'),
(309, 2, 25, '2026-08-29 06:10:56'),
(310, 2, 26, '2026-08-29 06:10:56'),
(311, 2, 27, '2026-08-29 06:10:56'),
(312, 2, 28, '2026-08-29 06:10:56'),
(313, 2, 29, '2026-08-29 06:10:56'),
(314, 2, 8, '2026-08-29 06:10:56'),
(315, 2, 9, '2026-08-29 06:10:56'),
(316, 2, 10, '2026-08-29 06:10:56'),
(317, 2, 11, '2026-08-29 06:10:56'),
(318, 2, 19, '2026-08-29 06:10:56'),
(319, 2, 20, '2026-08-29 06:10:56'),
(320, 2, 21, '2026-08-29 06:10:56'),
(321, 2, 22, '2026-08-29 06:10:56'),
(465, 8, 17, '2026-08-29 06:32:51'),
(466, 8, 18, '2026-08-29 06:32:51'),
(467, 8, 2, '2026-08-29 06:32:51'),
(468, 8, 3, '2026-08-29 06:32:51'),
(469, 8, 4, '2026-08-29 06:32:51'),
(470, 8, 5, '2026-08-29 06:32:51'),
(471, 8, 6, '2026-08-29 06:32:51'),
(472, 8, 7, '2026-08-29 06:32:51'),
(473, 8, 1, '2026-08-29 06:32:51'),
(474, 8, 12, '2026-08-29 06:32:51'),
(475, 8, 13, '2026-08-29 06:32:51'),
(476, 8, 14, '2026-08-29 06:32:51'),
(477, 8, 15, '2026-08-29 06:32:51'),
(478, 8, 16, '2026-08-29 06:32:51'),
(479, 8, 23, '2026-08-29 06:32:51'),
(480, 8, 24, '2026-08-29 06:32:51'),
(481, 8, 25, '2026-08-29 06:32:51'),
(482, 8, 26, '2026-08-29 06:32:51'),
(483, 8, 27, '2026-08-29 06:32:51'),
(484, 8, 28, '2026-08-29 06:32:51'),
(485, 8, 29, '2026-08-29 06:32:51'),
(486, 8, 8, '2026-08-29 06:32:51'),
(487, 8, 9, '2026-08-29 06:32:51'),
(488, 8, 10, '2026-08-29 06:32:51'),
(489, 8, 11, '2026-08-29 06:32:51'),
(490, 8, 19, '2026-08-29 06:32:51'),
(491, 8, 20, '2026-08-29 06:32:51'),
(492, 8, 21, '2026-08-29 06:32:51'),
(493, 8, 22, '2026-08-29 06:32:51'),
(512, 6, 17, '2026-08-29 10:22:02'),
(513, 6, 18, '2026-08-29 10:22:02'),
(514, 6, 2, '2026-08-29 10:22:02'),
(515, 6, 3, '2026-08-29 10:22:02'),
(516, 6, 4, '2026-08-29 10:22:02'),
(517, 6, 5, '2026-08-29 10:22:02'),
(518, 6, 6, '2026-08-29 10:22:02'),
(519, 6, 7, '2026-08-29 10:22:02'),
(520, 6, 1, '2026-08-29 10:22:02'),
(521, 6, 12, '2026-08-29 10:22:02'),
(522, 6, 13, '2026-08-29 10:22:02'),
(523, 6, 14, '2026-08-29 10:22:02'),
(524, 6, 15, '2026-08-29 10:22:02'),
(525, 6, 16, '2026-08-29 10:22:02'),
(526, 6, 23, '2026-08-29 10:22:02'),
(527, 6, 24, '2026-08-29 10:22:02'),
(528, 6, 25, '2026-08-29 10:22:02'),
(529, 6, 26, '2026-08-29 10:22:02'),
(530, 6, 27, '2026-08-29 10:22:02'),
(531, 6, 28, '2026-08-29 10:22:02'),
(532, 6, 29, '2026-08-29 10:22:02'),
(533, 6, 8, '2026-08-29 10:22:02'),
(534, 6, 9, '2026-08-29 10:22:02'),
(535, 6, 10, '2026-08-29 10:22:02'),
(536, 6, 11, '2026-08-29 10:22:02'),
(537, 6, 19, '2026-08-29 10:22:02'),
(538, 6, 20, '2026-08-29 10:22:02'),
(539, 6, 21, '2026-08-29 10:22:02'),
(540, 6, 22, '2026-08-29 10:22:02'),
(541, 7, 17, '2026-08-29 11:29:49'),
(542, 7, 18, '2026-08-29 11:29:49'),
(543, 7, 2, '2026-08-29 11:29:49'),
(544, 7, 3, '2026-08-29 11:29:49'),
(545, 7, 4, '2026-08-29 11:29:49'),
(546, 7, 5, '2026-08-29 11:29:49'),
(547, 7, 6, '2026-08-29 11:29:49'),
(548, 7, 7, '2026-08-29 11:29:49'),
(549, 7, 1, '2026-08-29 11:29:49'),
(550, 7, 12, '2026-08-29 11:29:49'),
(551, 7, 13, '2026-08-29 11:29:49'),
(552, 7, 14, '2026-08-29 11:29:49'),
(553, 7, 15, '2026-08-29 11:29:49'),
(554, 7, 16, '2026-08-29 11:29:49'),
(555, 7, 23, '2026-08-29 11:29:49'),
(556, 7, 24, '2026-08-29 11:29:49'),
(557, 7, 25, '2026-08-29 11:29:49'),
(558, 7, 26, '2026-08-29 11:29:49'),
(559, 7, 27, '2026-08-29 11:29:49'),
(560, 7, 28, '2026-08-29 11:29:49'),
(561, 7, 29, '2026-08-29 11:29:49'),
(562, 7, 8, '2026-08-29 11:29:49'),
(563, 7, 9, '2026-08-29 11:29:49'),
(564, 7, 10, '2026-08-29 11:29:49'),
(565, 7, 11, '2026-08-29 11:29:49'),
(566, 7, 19, '2026-08-29 11:29:49'),
(567, 7, 20, '2026-08-29 11:29:49'),
(568, 7, 21, '2026-08-29 11:29:49'),
(569, 7, 22, '2026-08-29 11:29:49'),
(570, 3, 17, '2026-08-29 11:29:56'),
(571, 3, 18, '2026-08-29 11:29:56'),
(572, 3, 2, '2026-08-29 11:29:56'),
(573, 3, 3, '2026-08-29 11:29:56'),
(574, 3, 4, '2026-08-29 11:29:56'),
(575, 3, 5, '2026-08-29 11:29:56'),
(576, 3, 6, '2026-08-29 11:29:56'),
(577, 3, 7, '2026-08-29 11:29:56'),
(578, 3, 1, '2026-08-29 11:29:56'),
(579, 3, 12, '2026-08-29 11:29:56'),
(580, 3, 13, '2026-08-29 11:29:56'),
(581, 3, 14, '2026-08-29 11:29:56'),
(582, 3, 15, '2026-08-29 11:29:56'),
(583, 3, 16, '2026-08-29 11:29:56'),
(584, 3, 23, '2026-08-29 11:29:56'),
(585, 3, 24, '2026-08-29 11:29:56'),
(586, 3, 25, '2026-08-29 11:29:56'),
(587, 3, 26, '2026-08-29 11:29:56'),
(588, 3, 27, '2026-08-29 11:29:56'),
(589, 3, 28, '2026-08-29 11:29:56'),
(590, 3, 29, '2026-08-29 11:29:56'),
(591, 3, 8, '2026-08-29 11:29:56'),
(592, 3, 9, '2026-08-29 11:29:56'),
(593, 3, 10, '2026-08-29 11:29:56'),
(594, 3, 11, '2026-08-29 11:29:56'),
(595, 3, 19, '2026-08-29 11:29:56'),
(596, 3, 20, '2026-08-29 11:29:56'),
(597, 3, 21, '2026-08-29 11:29:56'),
(598, 3, 22, '2026-08-29 11:29:56'),
(599, 5, 17, '2026-08-29 11:30:05'),
(600, 5, 18, '2026-08-29 11:30:05'),
(601, 5, 2, '2026-08-29 11:30:05'),
(602, 5, 3, '2026-08-29 11:30:05'),
(603, 5, 4, '2026-08-29 11:30:05'),
(604, 5, 5, '2026-08-29 11:30:05'),
(605, 5, 6, '2026-08-29 11:30:05'),
(606, 5, 7, '2026-08-29 11:30:05'),
(607, 5, 1, '2026-08-29 11:30:05'),
(608, 5, 12, '2026-08-29 11:30:05'),
(609, 5, 13, '2026-08-29 11:30:05'),
(610, 5, 14, '2026-08-29 11:30:05'),
(611, 5, 15, '2026-08-29 11:30:05'),
(612, 5, 16, '2026-08-29 11:30:05'),
(613, 5, 23, '2026-08-29 11:30:05'),
(614, 5, 24, '2026-08-29 11:30:05'),
(615, 5, 25, '2026-08-29 11:30:05'),
(616, 5, 26, '2026-08-29 11:30:05'),
(617, 5, 27, '2026-08-29 11:30:05'),
(618, 5, 28, '2026-08-29 11:30:05'),
(619, 5, 29, '2026-08-29 11:30:05'),
(620, 5, 8, '2026-08-29 11:30:05'),
(621, 5, 9, '2026-08-29 11:30:05'),
(622, 5, 10, '2026-08-29 11:30:05'),
(623, 5, 11, '2026-08-29 11:30:05'),
(624, 5, 19, '2026-08-29 11:30:05'),
(625, 5, 20, '2026-08-29 11:30:05'),
(626, 5, 21, '2026-08-29 11:30:05'),
(627, 5, 22, '2026-08-29 11:30:05'),
(628, 4, 17, '2026-08-29 11:30:18'),
(629, 4, 18, '2026-08-29 11:30:18'),
(630, 4, 2, '2026-08-29 11:30:18'),
(631, 4, 3, '2026-08-29 11:30:18'),
(632, 4, 4, '2026-08-29 11:30:18'),
(633, 4, 5, '2026-08-29 11:30:18'),
(634, 4, 6, '2026-08-29 11:30:18'),
(635, 4, 7, '2026-08-29 11:30:18'),
(636, 4, 1, '2026-08-29 11:30:18'),
(637, 4, 12, '2026-08-29 11:30:18'),
(638, 4, 13, '2026-08-29 11:30:18'),
(639, 4, 14, '2026-08-29 11:30:18'),
(640, 4, 15, '2026-08-29 11:30:18'),
(641, 4, 16, '2026-08-29 11:30:18'),
(642, 4, 23, '2026-08-29 11:30:18'),
(643, 4, 24, '2026-08-29 11:30:18'),
(644, 4, 25, '2026-08-29 11:30:18'),
(645, 4, 26, '2026-08-29 11:30:18'),
(646, 4, 27, '2026-08-29 11:30:18'),
(647, 4, 28, '2026-08-29 11:30:18'),
(648, 4, 29, '2026-08-29 11:30:18'),
(649, 4, 8, '2026-08-29 11:30:18'),
(650, 4, 9, '2026-08-29 11:30:18'),
(651, 4, 10, '2026-08-29 11:30:18'),
(652, 4, 11, '2026-08-29 11:30:18'),
(653, 4, 19, '2026-08-29 11:30:18'),
(654, 4, 20, '2026-08-29 11:30:18'),
(655, 4, 21, '2026-08-29 11:30:18'),
(656, 4, 22, '2026-08-29 11:30:18');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `assessment_floors`
--
ALTER TABLE `assessment_floors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `assessment_media`
--
ALTER TABLE `assessment_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_owners`
--
ALTER TABLE `assessment_owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=657;

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
