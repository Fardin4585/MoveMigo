-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 03, 2025 at 04:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `movemigo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(11) NOT NULL,
  `tenant_id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `home_id` int(11) DEFAULT NULL,
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `home`
--

CREATE TABLE `home` (
  `id` int(11) NOT NULL,
  `homeowner_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `home`
--

INSERT INTO `home` (`id`, `homeowner_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-07-31 16:57:42', '2025-07-31 16:57:42'),
(4, 2, '2025-07-31 19:36:19', '2025-07-31 19:36:19'),
(5, 2, '2025-07-31 19:55:49', '2025-07-31 19:55:49'),
(6, 2, '2025-08-01 07:36:31', '2025-08-01 07:36:31'),
(8, 2, '2025-08-01 09:09:55', '2025-08-01 09:09:55'),
(10, 2, '2025-08-01 13:32:20', '2025-08-01 13:32:20'),
(11, 1, '2025-08-03 13:51:17', '2025-08-03 13:51:17');

-- --------------------------------------------------------

--
-- Table structure for table `homeowners`
--

CREATE TABLE `homeowners` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `years_experience` int(11) DEFAULT NULL,
  `number_of_homes` int(11) DEFAULT 0,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `homeowners`
--

INSERT INTO `homeowners` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `years_experience`, `number_of_homes`, `verification_status`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'baker.bhai@gmail.com', '$2y$10$uW7DiacDQ7cU/qvvpwIvL.aTGJvpfYnWZLD27X2k/sf5M2korkuKS', 'Baker ', 'bhai', '39487598374', NULL, 2, 'pending', '2025-07-31 16:18:46', '2025-08-03 13:51:17', 1),
(2, 'lutfur.chacha@gmail.com', '$2y$10$86VgLz4JEWoctD2GIhPlTO94U5hn/bqtRqpnaRgkOT3ZKAqJxIhW2', 'lutfur', 'chacha', '93483493', NULL, 5, 'pending', '2025-07-31 19:33:29', '2025-08-01 13:32:20', 1);

-- --------------------------------------------------------

--
-- Table structure for table `home_details`
--

CREATE TABLE `home_details` (
  `id` int(11) NOT NULL,
  `home_id` int(11) NOT NULL,
  `home_name` varchar(255) NOT NULL,
  `num_of_bedrooms` int(11) NOT NULL,
  `washrooms` int(11) NOT NULL,
  `rent_monthly` decimal(10,2) NOT NULL,
  `utility_bills` decimal(10,2) DEFAULT 0.00,
  `facilities` set('wifi','water','gas','parking','furnished','AC') DEFAULT '',
  `family_bachelor_status` enum('family','bachelor','both') DEFAULT 'both',
  `address` varchar(500) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `home_details`
--

INSERT INTO `home_details` (`id`, `home_id`, `home_name`, `num_of_bedrooms`, `washrooms`, `rent_monthly`, `utility_bills`, `facilities`, `family_bachelor_status`, `address`, `city`, `state`, `zip_code`, `description`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 1, 'Baker Villa', 3, 3, 30000.00, 3000.00, 'wifi,water,gas,furnished', 'family', 'Bashundhara R/A', 'Dhaka', '', '1229', 'This is a well-kept home that I personally furnished to my liking, which is why I’m not willing to rent it out to bachelors. As I’m moving to a new home, I’m offering this one for rent to you.', 1, '2025-07-31 16:57:42', '2025-07-31 16:59:56'),
(4, 4, 'lutfur bilash', 4, 3, 35000.00, 1200.00, 'wifi,water,gas,parking,furnished,AC', 'both', 'Rampura,banasree A block road 6', 'Dhaka', '', '1229', 'elegant,furnished.this could be your dream home', 1, '2025-07-31 19:36:19', '2025-07-31 19:36:19'),
(5, 5, 'Tea break', 2, 1, 2000.00, 300.00, 'wifi,water,gas', 'both', 'Mirpur 10', 'Dhaka', '', '342', 'Basic amenities will be there. Bachelor and small family can choose this one.', 1, '2025-07-31 19:55:49', '2025-08-01 07:35:04'),
(6, 6, 'lutfurer bagan ', 3, 2, 34000.00, 1200.00, 'wifi,water,gas', 'bachelor', 'baridhara', 'Dhaka', '', '1230', '', 1, '2025-08-01 07:36:31', '2025-08-01 09:08:58'),
(8, 8, 'lutfurerMeyerBasha', 4, 3, 37000.00, 0.00, 'water,gas,parking,furnished', 'family', 'Gulshan', 'Dhaka', '', '3444', '', 1, '2025-08-01 09:09:55', '2025-08-01 10:50:17'),
(10, 10, 'lutfurer bilashita', 5, 4, 34000.00, 1600.00, 'wifi,water,gas,furnished', 'both', 'hajipara petrol pump ', 'Dhaka', '', '3434', '', 1, '2025-08-01 13:32:20', '2025-08-01 13:32:20'),
(11, 11, 'kader\'s kokila basha', 6, 5, 45000.00, 6000.00, 'water,gas,parking,furnished,AC', 'family', 'Banani', 'Dhaka', '', '1230', 'one of the best home you\'ll find in banani\r\n', 1, '2025-08-03 13:51:17', '2025-08-03 13:51:17');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_type` enum('tenant','homeowner') NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `receiver_type` enum('tenant','homeowner') NOT NULL,
  `content` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `preferred_location` varchar(255) DEFAULT NULL,
  `move_in_date` date DEFAULT NULL,
  `number_of_tenants` int(11) DEFAULT 1,
  `employment_status` varchar(50) DEFAULT NULL,
  `annual_income` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `preferred_location`, `move_in_date`, `number_of_tenants`, `employment_status`, `annual_income`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'mehedi.mahmud@northsouth.edu', '$2y$10$dZxawFPCBJR2O6.CessmBeca2hqt09VMtAGSOx7iSvgg3Jwi0wdu.', 'Mehedi', 'Mahmud', '016734898', NULL, NULL, 1, NULL, NULL, '2025-07-31 15:51:29', '2025-07-31 15:51:29', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('tenant','homeowner') NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `user_type`, `session_token`, `expires_at`, `created_at`) VALUES
(1, 1, 'tenant', '306f53935d8ca0f2a424d057b813027ef4b2264b05fc323b9013f5dc3de39f1d', '2025-08-01 12:01:59', '2025-07-31 16:01:59'),
(2, 1, 'homeowner', 'ab7cc70221646ca2c6e078f5669b6e410d6036d7400521a71da92b2ef734c3b8', '2025-08-01 12:18:59', '2025-07-31 16:18:59'),
(3, 1, 'homeowner', '904e09a89ebcb93784d161224de443af35782a157b050b8c07e567762bfc2957', '2025-08-01 12:40:39', '2025-07-31 16:40:39'),
(4, 1, 'homeowner', '733eff6867b138b2fa1c69b2c3e3b9da67aaf1e0971e4828aa26f22f880738d8', '2025-08-01 13:33:19', '2025-07-31 17:33:19'),
(5, 1, 'tenant', '0f9c288f416efbd13719eb05366397333be4e161ce1e8b477d73f70586cbbd42', '2025-08-01 13:34:27', '2025-07-31 17:34:27'),
(6, 1, 'tenant', 'ace7ca6b8039d334a71c9ffe61957dfaf7a0f173315e7d0d309ce98af466721e', '2025-08-01 14:29:01', '2025-07-31 18:29:01'),
(7, 1, 'tenant', '79badd4f94392a873b83a5b797072890e37dcf7835a90854ac72e1dbe81929ce', '2025-08-01 14:41:08', '2025-07-31 18:41:08'),
(8, 1, 'tenant', '63afbdd9599f21fccdeeae10a122e09e2b19dafbc1a0d5d1372f9b33e93f9ca1', '2025-08-01 14:43:32', '2025-07-31 18:43:32'),
(9, 1, 'tenant', '2dbc62264426af1d47311ce0f4dcb1074b19c4b66041cd964368399e262fcee7', '2025-08-01 14:52:23', '2025-07-31 18:52:23'),
(10, 1, 'tenant', '59ee2fcc5bf30c1783b16dc7a1189f23cb4f4ff29937c7c74a22a557a86c8cdb', '2025-08-01 14:55:35', '2025-07-31 18:55:35'),
(11, 1, 'tenant', '26eb67afe021f776f7afed45d2136558deb796a0d2ea9e75258634b960fb64fa', '2025-08-01 14:59:10', '2025-07-31 18:59:10'),
(12, 1, 'tenant', 'f846bb7920522e15878ac4f3f34bdf2d708c33babfd672a33e3c102f93c5cf02', '2025-08-01 15:07:07', '2025-07-31 19:07:07'),
(13, 1, 'tenant', '83f7edae3d72c98d734f6217e2848cfe9d18541965a694aa1c330e067cb86648', '2025-08-01 15:31:14', '2025-07-31 19:31:14'),
(14, 2, 'homeowner', '7608dad6e63ecff80139fa9a23a1477c2610e7a6098f989713c755714295c6f3', '2025-08-01 15:33:41', '2025-07-31 19:33:41'),
(15, 1, 'tenant', '1ecf5b79783a47f50d8bed0ab6504d1353d8aed750844ea03faf78d966c3d549', '2025-08-01 15:36:30', '2025-07-31 19:36:30'),
(16, 2, 'homeowner', '27ba978a9416a2898d810c1b74f35f565a0c464fe72e2214bc561c3edf9107e6', '2025-08-01 15:37:24', '2025-07-31 19:37:24'),
(17, 1, 'tenant', '81d0de5fec154520b28b02ae85cb1c4e7f698c632165b3b6cb6050b4ec325702', '2025-08-01 15:37:46', '2025-07-31 19:37:46'),
(18, 1, 'tenant', '96595a4e2f860b92fca7945dbcafdddf80e46bf91052d86e22cf9259aa1c148a', '2025-08-01 15:46:00', '2025-07-31 19:46:00'),
(20, 1, 'tenant', 'a17b69dd3c34da5e3c5d97c22cccbbe3de7bcb8fd5e41864d83feddb5557259f', '2025-08-01 15:56:52', '2025-07-31 19:56:52'),
(21, 1, 'tenant', 'b906aae643b6eb354f4f0bdc79be0cf20cbd9ab6e1e083ebd707f0fad9eb0302', '2025-08-01 16:06:59', '2025-07-31 20:06:59'),
(22, 1, 'tenant', '5263e460e3a43132ff96811e5a2e79d5d22a79874dd5ad63ee452f94e272dde2', '2025-08-02 00:49:17', '2025-08-01 04:49:17'),
(23, 2, 'homeowner', '9770e9006a5b0524fc594a5cdb6ead84f1f35b6864380765fd6d21c261e1083c', '2025-08-02 00:52:12', '2025-08-01 04:52:12'),
(24, 1, 'tenant', 'd159fff28e2a9a2db24e8470c74a02e6dc97881a99c252ea544d40c50fb653a7', '2025-08-02 00:53:12', '2025-08-01 04:53:12'),
(25, 2, 'homeowner', 'f142b8c29ffa9fae694c804b534a6ec54235854ae51c0861c14bccd4d04e06fb', '2025-08-02 00:53:40', '2025-08-01 04:53:40'),
(26, 1, 'tenant', '042819fc8799eabca45b172bc9cdd8d4aab6718c1d4497ff8f1cbafd734b2513', '2025-08-02 00:54:01', '2025-08-01 04:54:01'),
(27, 1, 'tenant', '5494648a66b5a93e11de3e9d6a8ba17d6ac4ea5582362ed561082369e7489f12', '2025-08-02 01:52:16', '2025-08-01 05:52:16'),
(28, 2, 'homeowner', '54b33c48f6b6691df2eda5ced3af9fa1edaeba309a9395635888bd46925c94b1', '2025-08-02 01:54:03', '2025-08-01 05:54:03'),
(29, 1, 'tenant', '6f075ed93e8a3db9f9e0ec9cf2a0bac3bf632ee0fc84731361a9ef860326b6ef', '2025-08-02 02:14:00', '2025-08-01 06:14:00'),
(30, 1, 'tenant', 'd0c066a53c227bbc22abb4d622df5f9754f8cd8c006bb67f350b1952c475401f', '2025-08-02 02:23:33', '2025-08-01 06:23:33'),
(31, 2, 'homeowner', '1508a9a8da03ab565b566ef10d012eef862f880da993fb6a8069baf4c8450f78', '2025-08-02 03:34:02', '2025-08-01 07:34:02'),
(32, 2, 'homeowner', 'de6de58a66423f8af1ab004087bd1c68d8d723cf0a006c043ad64f2a95726d4a', '2025-08-02 03:34:18', '2025-08-01 07:34:18'),
(33, 1, 'tenant', '5017309e18b0e0a1daecd3a6d0236e23e2a6c82409fa5530d74c6f2c796ba01c', '2025-08-02 03:37:02', '2025-08-01 07:37:02'),
(34, 1, 'tenant', 'ac19f612a20049f20f6438ea5ea02eb8a962cc2a9500af49492a78b7fcf15788', '2025-08-02 04:59:57', '2025-08-01 08:59:57'),
(35, 1, 'tenant', 'f1dc08c168658c2da784927e7a8de877550874cae4dbb1514ff95887bcb3de32', '2025-08-02 05:08:01', '2025-08-01 09:08:01'),
(36, 2, 'homeowner', 'd566ed6567e78cfa7eb716c5dda2bfdca4ab9b443bb100f6d7709808fc3a176a', '2025-08-02 05:08:43', '2025-08-01 09:08:43'),
(37, 1, 'tenant', '522c421501f2b4df0f34483b3eb170ec19c3b3a89d1e0fff5c9c798872969229', '2025-08-02 05:11:30', '2025-08-01 09:11:30'),
(38, 2, 'homeowner', 'ff6f3ebfaa187cb9160f5a72d05d233ecf99f2599f3ebb65aaec7d2888e72b12', '2025-08-02 05:36:58', '2025-08-01 09:36:58'),
(39, 1, 'tenant', 'ad4636b359a30568a1155bc1978099e2d412b3ca0abd08e00505af258c210c59', '2025-08-02 05:37:59', '2025-08-01 09:37:59'),
(40, 2, 'homeowner', 'b27573ce0e4531e6a4f17c44c79d07b73b1a1e69b103f9cc524c4ed332784147', '2025-08-02 05:38:18', '2025-08-01 09:38:18'),
(45, 1, 'tenant', 'd9773646bb1dc0dffd03b71632fa0252b0b90628f9cd9c1a35d78a21195edb72', '2025-08-02 06:48:02', '2025-08-01 10:48:02'),
(46, 2, 'homeowner', '1099c6b924b5024b397623c5a1e2b4826f4fddcfff5467d6e8a7cab6ebd9f9a2', '2025-08-02 06:49:54', '2025-08-01 10:49:54'),
(48, 1, 'tenant', '9c4649abc45e378f349d54461bd525117021a6a28e9132fa8976028fc99a99f6', '2025-08-02 09:29:16', '2025-08-01 13:29:16'),
(49, 2, 'homeowner', '9eac25a121bfbfa71749eb003f9be01135a1e0b01fc0741178fed50e181d59b5', '2025-08-02 09:30:19', '2025-08-01 13:30:19'),
(52, 1, 'tenant', '13da7800cf6f221c7fb3650baf75c814e22a5116f0546a69590309bd8ed40c13', '2025-08-04 09:51:59', '2025-08-03 13:51:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation` (`tenant_id`,`homeowner_id`,`home_id`),
  ADD KEY `homeowner_id` (`homeowner_id`),
  ADD KEY `home_id` (`home_id`);

--
-- Indexes for table `home`
--
ALTER TABLE `home`
  ADD PRIMARY KEY (`id`),
  ADD KEY `homeowner_id` (`homeowner_id`);

--
-- Indexes for table `homeowners`
--
ALTER TABLE `homeowners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `home_details`
--
ALTER TABLE `home_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `home_id` (`home_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `home`
--
ALTER TABLE `home`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `homeowners`
--
ALTER TABLE `homeowners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `home_details`
--
ALTER TABLE `home_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `conversations`
--
ALTER TABLE `conversations`
  ADD CONSTRAINT `conversations_ibfk_1` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_2` FOREIGN KEY (`homeowner_id`) REFERENCES `homeowners` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conversations_ibfk_3` FOREIGN KEY (`home_id`) REFERENCES `home` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `home`
--
ALTER TABLE `home`
  ADD CONSTRAINT `home_ibfk_1` FOREIGN KEY (`homeowner_id`) REFERENCES `homeowners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `home_details`
--
ALTER TABLE `home_details`
  ADD CONSTRAINT `home_details_ibfk_1` FOREIGN KEY (`home_id`) REFERENCES `home` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
