-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 26, 2025 at 12:18 AM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hostlicc_demostoresdf34sdf`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED NOT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_value`)),
  `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_value`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `target_type`, `target_id`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'update_product_status', 'product', 12, NULL, '{\"status\":\"active\"}', '78.183.249.232', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', '2025-12-25 08:39:32'),
(2, 1, 'update_product_status', 'product', 9, NULL, '{\"status\":\"active\"}', '78.183.249.232', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', '2025-12-25 08:39:53'),
(3, 1, 'add_credit', 'user', 15, NULL, '{\"amount\":10,\"reason\":\"test\"}', '78.183.249.232', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36', '2025-12-25 08:41:23');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `icon`, `parent_id`, `sort_order`, `status`, `created_at`) VALUES
(1, 'Telegram', 'telegram', 'Telegram accounts and bots', 'fa-paper-plane', NULL, 1, 'active', '2025-12-12 16:44:08'),
(2, 'Accounts', 'accounts', 'Digital accounts and credentials', 'fa-user-circle', NULL, 2, 'active', '2025-12-12 16:44:08'),
(3, 'Software', 'software', 'Software licenses and keys', 'fa-laptop-code', NULL, 3, 'active', '2025-12-12 16:44:08'),
(4, 'Gaming', 'gaming', 'Game accounts, items, and currencies', 'fa-gamepad', NULL, 4, 'active', '2025-12-12 16:44:08'),
(5, 'E-books', 'ebooks', 'Digital books and guides', 'fa-book', NULL, 5, 'active', '2025-12-12 16:44:08'),
(6, 'Graphics', 'graphics', 'Design templates and assets', 'fa-palette', NULL, 6, 'active', '2025-12-12 16:44:08'),
(8, 'Other', 'other', 'Other digital products', 'fa-box', NULL, 8, 'active', '2025-12-12 16:44:08'),
(9, 'Google', 'google', '', 'fa-folder', NULL, 0, 'active', '2025-12-12 16:56:33'),
(10, 'Discord', 'discord', '', 'fa-discord', NULL, 0, 'active', '2025-12-12 16:56:39'),
(11, 'Facebook', 'facebook', '', 'fa-folder', NULL, 0, 'active', '2025-12-12 16:58:08'),
(13, 'ABC Test', 'abc-test', 'ABC test accounts', 'fa-folder', NULL, 0, 'active', '2025-12-17 19:16:02'),
(15, 'Test Category', 'test-category', 'Test Category', 'fa-folder', NULL, 0, 'inactive', '2025-12-22 20:27:55');

-- --------------------------------------------------------

--
-- Table structure for table `crypto_payments`
--

CREATE TABLE `crypto_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `payment_id` varchar(100) DEFAULT NULL COMMENT 'NOWPayments payment ID',
  `invoice_id` varchar(100) DEFAULT NULL COMMENT 'NOWPayments invoice ID',
  `order_id` varchar(100) NOT NULL COMMENT 'Our internal order ID',
  `payment_type` enum('deposit','verification','other') DEFAULT 'deposit',
  `price_amount` decimal(18,6) NOT NULL COMMENT 'Amount in price currency (USD)',
  `price_currency` varchar(10) DEFAULT 'usd',
  `pay_amount` decimal(18,8) DEFAULT NULL COMMENT 'Amount to pay in crypto',
  `pay_currency` varchar(20) DEFAULT NULL COMMENT 'Crypto currency code',
  `actually_paid` decimal(18,8) DEFAULT NULL COMMENT 'Amount actually paid',
  `pay_address` varchar(255) DEFAULT NULL COMMENT 'Deposit address',
  `status` varchar(50) DEFAULT 'waiting',
  `invoice_url` varchar(500) DEFAULT NULL COMMENT 'NOWPayments invoice URL',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crypto_payments`
--

INSERT INTO `crypto_payments` (`id`, `user_id`, `payment_id`, `invoice_id`, `order_id`, `payment_type`, `price_amount`, `price_currency`, `pay_amount`, `pay_currency`, `actually_paid`, `pay_address`, `status`, `invoice_url`, `created_at`, `updated_at`, `expires_at`) VALUES
(1, 3, '4900039068', NULL, 'DEP-3-1765595845', 'deposit', 10.000000, 'usd', 13.66405900, 'usdttrc20', NULL, 'TYLZMP16bdUJUvSN3QEfjDVcen1ZKnQza9', 'cancelled', NULL, '2025-12-13 03:17:26', '2025-12-13 03:19:42', '2025-12-14 08:17:26'),
(2, 3, '5275471015', NULL, 'DEP-3-1765629371', 'deposit', 10.000000, 'usd', 13.66501600, 'usdttrc20', NULL, 'TFSF48fsXguoJuW1PGQ6mUCL3wRsDJfvdS', 'cancelled', NULL, '2025-12-13 12:36:13', '2025-12-13 12:36:27', '2025-12-14 17:36:13'),
(3, 8, '4612380250', NULL, 'DEP-8-1765630025', 'deposit', 100.000000, 'usd', 104.98227100, 'usdttrc20', NULL, 'TVFbHFy76Xoad5aYJa59hX8JVinQnfGKyr', 'cancelled', NULL, '2025-12-13 12:47:06', '2025-12-13 12:47:22', '2025-12-14 17:47:06'),
(4, 10, '4778886015', NULL, 'DEP-10-1766006927', 'deposit', 10.000000, 'usd', 0.00012634, 'btc', NULL, '3JBEZCupv5mzHSRtqhoxygvzn6z51RuBWp', 'cancelled', NULL, '2025-12-17 21:28:48', '2025-12-17 21:28:56', '2025-12-18 21:28:48'),
(5, 10, '6302998981', NULL, 'DEP-10-1766006947', 'deposit', 10.000000, 'usd', 13.65270100, 'usdttrc20', NULL, 'TKjYufMESh8h4SiNsAZAm3vxPEbesn9Tkx', 'cancelled', NULL, '2025-12-17 21:29:08', '2025-12-17 21:29:15', '2025-12-18 21:29:08'),
(6, 10, '4581441583', NULL, 'DEP-10-1766006959', 'deposit', 10.000000, 'usd', 0.08340632, 'sol', NULL, '6BnaqnYyE3aCKjF774Qk6oDenKXNJKmPTGMtFggiR61Q', 'cancelled', NULL, '2025-12-17 21:29:20', '2025-12-17 21:29:23', '2025-12-18 21:29:20'),
(7, 10, '5798062003', NULL, 'DEP-10-1766006975', 'deposit', 10.000000, 'usd', 0.00362715, 'eth', NULL, '0x7A5B07b2D74711bebFcf6Ff345FD54311e8E516d', 'cancelled', NULL, '2025-12-17 21:29:36', '2025-12-17 21:29:41', '2025-12-18 21:29:36'),
(8, 10, '6127292198', NULL, 'DEP-10-1766006976', 'deposit', 10.000000, 'usd', 0.00362715, 'eth', NULL, '0x6aA7E6Fb0D1B46E6FE6CD8d831674aE77F4978F0', 'cancelled', NULL, '2025-12-17 21:29:36', '2025-12-17 21:29:41', '2025-12-18 21:29:36'),
(9, 10, '5808989656', NULL, 'DEP-10-1766006976', 'deposit', 10.000000, 'usd', 0.00362815, 'eth', NULL, '0x00c975A317F52d058Ee5C3882a5C3022Af5b0346', 'cancelled', NULL, '2025-12-17 21:29:37', '2025-12-17 21:29:41', '2025-12-18 21:29:37'),
(10, 10, '4963005982', NULL, 'DEP-10-1766006989', 'deposit', 10.000000, 'usd', 13.65672700, 'usdttrc20', NULL, 'TYfvTpjC7VJe61ASLdsvH4QAxCmktcX12V', 'cancelled', NULL, '2025-12-17 21:29:49', '2025-12-17 21:29:56', '2025-12-18 21:29:49'),
(11, 10, '6006578716', NULL, 'DEP-10-1766007000', 'deposit', 10.000000, 'usd', 13.65880000, 'usdttrc20', NULL, 'TPVNZNn4z6gAqJwkWkyerLbAvCDbT9YSz3', 'expired', NULL, '2025-12-17 21:30:03', '2025-12-17 21:40:50', '2025-12-18 21:30:03'),
(12, 10, '4977018730', NULL, 'DEP-10-1766007710', 'deposit', 10.000000, 'usd', 13.64707600, 'usdttrc20', NULL, 'TPGtRWyVKetUE1Yx92E9T21dQNwpccaBm3', 'expired', NULL, '2025-12-17 21:41:52', '2025-12-17 21:52:50', '2025-12-18 21:41:52'),
(13, 10, '5745058367', NULL, 'DEP-10-1766008430', 'deposit', 10.000000, 'usd', 13.65814900, 'usdttrc20', NULL, 'TQM1jwXG9DJgj7iZVJ7uYLdWidtcJ1DTQm', 'expired', NULL, '2025-12-17 21:53:51', '2025-12-17 22:04:50', '2025-12-18 21:53:51'),
(14, 10, '5854121634', NULL, 'DEP-10-1766009150', 'deposit', 10.000000, 'usd', 13.66548000, 'usdttrc20', NULL, 'TQowcq9FbdfKtkKrE46jcbo8w4Zh5oAPL7', 'expired', NULL, '2025-12-17 22:05:54', '2025-12-17 22:16:50', '2025-12-18 22:05:54'),
(15, 10, '5737603193', NULL, 'DEP-10-1766009870', 'deposit', 10.000000, 'usd', 13.63918700, 'usdttrc20', NULL, 'TWU9fASRQPMXbLaBFLxYLYD9xoRnZxd74r', 'expired', NULL, '2025-12-17 22:17:51', '2025-12-17 22:28:50', '2025-12-18 22:17:51'),
(16, 10, '6430257464', NULL, 'DEP-10-1766010590', 'deposit', 10.000000, 'usd', 13.62517300, 'usdttrc20', NULL, 'TVXTKVxYGKdqYYUUY9mrGD598A6tnK9RZQ', 'expired', NULL, '2025-12-17 22:29:51', '2025-12-17 22:40:50', '2025-12-18 22:29:51'),
(17, 10, '6328469530', NULL, 'DEP-10-1766011310', 'deposit', 10.000000, 'usd', 13.67040600, 'usdttrc20', NULL, 'TNoxJEqcNFFtFqyqUbWmDgQp6L6RTMY98J', 'expired', NULL, '2025-12-17 22:41:51', '2025-12-17 22:52:49', '2025-12-18 22:41:51'),
(18, 10, '6022547014', NULL, 'DEP-10-1766012029', 'deposit', 10.000000, 'usd', 13.64957800, 'usdttrc20', NULL, 'TPtHq4DpqrdSHsXQvN3jKjjeYEVp3jXZfk', 'expired', NULL, '2025-12-17 22:53:49', '2025-12-17 23:04:49', '2025-12-18 22:53:49'),
(19, 10, '4418738816', NULL, 'DEP-10-1766012749', 'deposit', 10.000000, 'usd', 13.67813800, 'usdttrc20', NULL, 'TKeRdJBQ529GUXXX74WCwhM3nEd8ceciK6', 'expired', NULL, '2025-12-17 23:05:49', '2025-12-17 23:16:49', '2025-12-18 23:05:49'),
(20, 10, '5168743029', NULL, 'DEP-10-1766013469', 'deposit', 10.000000, 'usd', 13.67427400, 'usdttrc20', NULL, 'TCu3KzCenmd3AE1Nw6Cp5o8ve3izzHGMZ6', 'expired', NULL, '2025-12-17 23:17:50', '2025-12-17 23:28:48', '2025-12-18 23:17:50'),
(21, 10, '5048623398', NULL, 'DEP-10-1766014189', 'deposit', 10.000000, 'usd', 13.66700300, 'usdttrc20', NULL, 'TC7dv1kS4KZs13CbaBsvckycELPkNWGkj3', 'expired', NULL, '2025-12-17 23:29:49', '2025-12-17 23:40:49', '2025-12-18 23:29:49'),
(22, 10, '6221647130', NULL, 'DEP-10-1766014909', 'deposit', 10.000000, 'usd', 13.65051800, 'usdttrc20', NULL, 'TAcQA1Vgvm3YaXQBwxeUA4cgJA2NXyVM4m', 'expired', NULL, '2025-12-17 23:41:50', '2025-12-17 23:52:49', '2025-12-18 23:41:50'),
(23, 10, '5893517384', NULL, 'DEP-10-1766015629', 'deposit', 10.000000, 'usd', 13.65976200, 'usdttrc20', NULL, 'TSXaYUiVSxiFNxu4SSWKehQGXwo1TqMyVn', 'expired', NULL, '2025-12-17 23:53:50', '2025-12-18 00:04:49', '2025-12-18 23:53:50'),
(24, 10, '5304144932', NULL, 'DEP-10-1766016349', 'deposit', 10.000000, 'usd', 13.65183500, 'usdttrc20', NULL, 'TMa3tLzsow7JtVGKuScZPnpPgJpJLLmurd', 'expired', NULL, '2025-12-18 00:05:49', '2025-12-18 00:16:49', '2025-12-19 00:05:49'),
(25, 10, '4730378658', NULL, 'DEP-10-1766017069', 'deposit', 10.000000, 'usd', 13.65367600, 'usdttrc20', NULL, 'TVt35K7WTLuFNayZLbAki4Y3sz9wgbWdvy', 'expired', NULL, '2025-12-18 00:17:50', '2025-12-18 00:28:49', '2025-12-19 00:17:50'),
(26, 10, '6426627804', NULL, 'DEP-10-1766017789', 'deposit', 10.000000, 'usd', 13.64878900, 'usdttrc20', NULL, 'TPGDiQT4bZFioGT8P5qVdkMvzi5UG1CAcJ', 'expired', NULL, '2025-12-18 00:29:49', '2025-12-18 00:40:49', '2025-12-19 00:29:49'),
(27, 10, '4562332743', NULL, 'DEP-10-1766018509', 'deposit', 10.000000, 'usd', 13.65865300, 'usdttrc20', NULL, 'TAgGtfae3AaMTC7a5ekMSNZEcyhMmhpHFE', 'expired', NULL, '2025-12-18 00:41:50', '2025-12-18 00:52:49', '2025-12-19 00:41:50'),
(28, 10, '4354795351', NULL, 'DEP-10-1766019229', 'deposit', 10.000000, 'usd', 13.64772100, 'usdttrc20', NULL, 'TWrGD8J9BZViDaeprKTqdQd1t7HiUPbFRY', 'expired', NULL, '2025-12-18 00:53:49', '2025-12-18 01:04:49', '2025-12-19 00:53:49'),
(29, 10, '6296208670', NULL, 'DEP-10-1766019949', 'deposit', 10.000000, 'usd', 13.66300600, 'usdttrc20', NULL, 'THUWwKuPCzUndYHzF2WJi76aWFQpHemtei', 'expired', NULL, '2025-12-18 01:05:50', '2025-12-18 01:16:49', '2025-12-19 01:05:50'),
(30, 10, '5858642831', NULL, 'DEP-10-1766020669', 'deposit', 10.000000, 'usd', 13.64477800, 'usdttrc20', NULL, 'TPszHyU2RywioMAsMvshkhzebrs6P1iJL6', 'expired', NULL, '2025-12-18 01:17:49', '2025-12-18 01:28:49', '2025-12-19 01:17:49'),
(31, 10, '4563524324', NULL, 'DEP-10-1766021389', 'deposit', 10.000000, 'usd', 13.65496700, 'usdttrc20', NULL, 'TDYNmb1jR7PiqJMRcon2f2JbFymgbWE83V', 'expired', NULL, '2025-12-18 01:29:50', '2025-12-18 01:40:49', '2025-12-19 01:29:50'),
(32, 11, '4561235971', NULL, 'DEP-11-1766433917', 'deposit', 10.000000, 'usd', 13.68029700, 'usdttrc20', NULL, 'TJfrjBWEEz38hxGiLR5mYGZS6BgE7TwM6f', 'waiting', NULL, '2025-12-22 20:05:17', '2025-12-22 20:05:17', '2025-12-23 20:05:17');

-- --------------------------------------------------------

--
-- Table structure for table `disputes`
--

CREATE TABLE `disputes` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `initiated_by` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('open','under_review','resolved_buyer','resolved_seller','closed') NOT NULL DEFAULT 'open',
  `admin_notes` text DEFAULT NULL,
  `resolved_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `esim_countries`
--

CREATE TABLE `esim_countries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `esim_countries`
--

INSERT INTO `esim_countries` (`id`, `name`, `code`, `status`, `sort_order`, `created_at`) VALUES
(1, 'USA', 'US', 'active', 1, '2025-12-16 02:49:18'),
(2, 'UK', 'GB', 'active', 2, '2025-12-16 02:49:18'),
(3, 'Germany', 'DE', 'active', 3, '2025-12-16 02:49:18'),
(4, 'France', 'FR', 'active', 4, '2025-12-16 02:49:18'),
(5, 'UAE', 'AE', 'active', 5, '2025-12-16 02:49:18'),
(6, 'Saudi Arabia', 'SA', 'active', 6, '2025-12-16 02:49:18'),
(7, 'India', 'IN', 'active', 7, '2025-12-16 02:49:18'),
(8, 'Canada', 'CA', 'active', 8, '2025-12-16 02:49:18'),
(9, 'Australia', 'AU', 'active', 9, '2025-12-16 02:49:18'),
(10, 'Japan', 'JP', 'active', 10, '2025-12-16 02:49:18'),
(11, 'South Korea', 'KR', 'active', 11, '2025-12-16 02:49:18'),
(12, 'Singapore', 'SG', 'active', 12, '2025-12-16 02:49:18'),
(13, 'Thailand', 'TH', 'active', 13, '2025-12-16 02:49:18'),
(14, 'Malaysia', 'MY', 'active', 14, '2025-12-16 02:49:18'),
(15, 'Indonesia', 'ID', 'active', 15, '2025-12-16 02:49:18'),
(16, 'Turkey', 'TR', 'active', 16, '2025-12-16 02:49:18'),
(17, 'Italy', 'IT', 'active', 17, '2025-12-16 02:49:18'),
(18, 'Spain', 'ES', 'active', 18, '2025-12-16 02:49:18'),
(19, 'Netherlands', 'NL', 'active', 19, '2025-12-16 02:49:18'),
(20, 'Switzerland', 'CH', 'active', 20, '2025-12-16 02:49:18'),
(21, 'Global eSIM', 'GLOBAL', 'active', 100, '2025-12-16 02:49:18'),
(22, 'Austria', 'AT', 'active', 21, '2025-12-16 03:09:29'),
(23, 'Belgium', 'BE', 'active', 22, '2025-12-16 03:09:29'),
(24, 'Poland', 'PL', 'active', 23, '2025-12-16 03:09:29'),
(25, 'Portugal', 'PT', 'active', 24, '2025-12-16 03:09:29'),
(26, 'Czech Republic', 'CZ', 'active', 25, '2025-12-16 03:09:29'),
(27, 'Greece', 'GR', 'active', 26, '2025-12-16 03:09:29'),
(28, 'Sweden', 'SE', 'active', 27, '2025-12-16 03:09:29'),
(29, 'Norway', 'NO', 'active', 28, '2025-12-16 03:09:29'),
(30, 'Denmark', 'DK', 'active', 29, '2025-12-16 03:09:29'),
(31, 'Finland', 'FI', 'active', 30, '2025-12-16 03:09:29'),
(32, 'Ireland', 'IE', 'active', 31, '2025-12-16 03:09:29'),
(33, 'Hungary', 'HU', 'active', 32, '2025-12-16 03:09:29'),
(34, 'Romania', 'RO', 'active', 33, '2025-12-16 03:09:29'),
(35, 'Bulgaria', 'BG', 'active', 34, '2025-12-16 03:09:29'),
(36, 'Croatia', 'HR', 'active', 35, '2025-12-16 03:09:29'),
(37, 'Slovakia', 'SK', 'active', 36, '2025-12-16 03:09:29'),
(38, 'Slovenia', 'SI', 'active', 37, '2025-12-16 03:09:29'),
(39, 'Estonia', 'EE', 'active', 38, '2025-12-16 03:09:29'),
(40, 'Latvia', 'LV', 'active', 39, '2025-12-16 03:09:29'),
(41, 'Lithuania', 'LT', 'active', 40, '2025-12-16 03:09:29'),
(42, 'Luxembourg', 'LU', 'active', 41, '2025-12-16 03:09:29'),
(43, 'Malta', 'MT', 'active', 42, '2025-12-16 03:09:29'),
(44, 'Cyprus', 'CY', 'active', 43, '2025-12-16 03:09:29'),
(45, 'Iceland', 'IS', 'active', 44, '2025-12-16 03:09:29'),
(46, 'Ukraine', 'UA', 'active', 45, '2025-12-16 03:09:29'),
(47, 'Russia', 'RU', 'active', 46, '2025-12-16 03:09:29'),
(48, 'China', 'CN', 'active', 47, '2025-12-16 03:09:29'),
(49, 'Hong Kong', 'HK', 'active', 48, '2025-12-16 03:09:29'),
(50, 'Taiwan', 'TW', 'active', 49, '2025-12-16 03:09:29'),
(51, 'Vietnam', 'VN', 'active', 50, '2025-12-16 03:09:29'),
(52, 'Philippines', 'PH', 'active', 51, '2025-12-16 03:09:29'),
(53, 'Pakistan', 'PK', 'active', 52, '2025-12-16 03:09:29'),
(54, 'Bangladesh', 'BD', 'active', 53, '2025-12-16 03:09:29'),
(55, 'Sri Lanka', 'LK', 'active', 54, '2025-12-16 03:09:29'),
(56, 'Nepal', 'NP', 'active', 55, '2025-12-16 03:09:29'),
(57, 'Myanmar', 'MM', 'active', 56, '2025-12-16 03:09:29'),
(58, 'Cambodia', 'KH', 'active', 57, '2025-12-16 03:09:29'),
(59, 'Laos', 'LA', 'active', 58, '2025-12-16 03:09:29'),
(60, 'Mongolia', 'MN', 'active', 59, '2025-12-16 03:09:29'),
(61, 'Maldives', 'MV', 'active', 60, '2025-12-16 03:09:29'),
(62, 'Brunei', 'BN', 'active', 61, '2025-12-16 03:09:29'),
(63, 'Macau', 'MO', 'active', 62, '2025-12-16 03:09:29'),
(64, 'Qatar', 'QA', 'active', 63, '2025-12-16 03:09:29'),
(65, 'Kuwait', 'KW', 'active', 64, '2025-12-16 03:09:29'),
(66, 'Bahrain', 'BH', 'active', 65, '2025-12-16 03:09:29'),
(67, 'Oman', 'OM', 'active', 66, '2025-12-16 03:09:29'),
(68, 'Egypt', 'EG', 'active', 67, '2025-12-16 03:09:29'),
(69, 'Israel', 'IL', 'active', 68, '2025-12-16 03:09:29'),
(70, 'Jordan', 'JO', 'active', 69, '2025-12-16 03:09:29'),
(71, 'Lebanon', 'LB', 'active', 70, '2025-12-16 03:09:29'),
(72, 'Iraq', 'IQ', 'active', 71, '2025-12-16 03:09:29'),
(73, 'Iran', 'IR', 'active', 72, '2025-12-16 03:09:29'),
(74, 'Mexico', 'MX', 'active', 73, '2025-12-16 03:09:29'),
(75, 'Brazil', 'BR', 'active', 74, '2025-12-16 03:09:29'),
(76, 'Argentina', 'AR', 'active', 75, '2025-12-16 03:09:29'),
(77, 'Chile', 'CL', 'active', 76, '2025-12-16 03:09:29'),
(78, 'Colombia', 'CO', 'active', 77, '2025-12-16 03:09:29'),
(79, 'Peru', 'PE', 'active', 78, '2025-12-16 03:09:29'),
(80, 'Venezuela', 'VE', 'active', 79, '2025-12-16 03:09:29'),
(81, 'Ecuador', 'EC', 'active', 80, '2025-12-16 03:09:29'),
(82, 'Bolivia', 'BO', 'active', 81, '2025-12-16 03:09:29'),
(83, 'Paraguay', 'PY', 'active', 82, '2025-12-16 03:09:29'),
(84, 'Uruguay', 'UY', 'active', 83, '2025-12-16 03:09:29'),
(85, 'Costa Rica', 'CR', 'active', 84, '2025-12-16 03:09:29'),
(86, 'Panama', 'PA', 'active', 85, '2025-12-16 03:09:29'),
(87, 'Puerto Rico', 'PR', 'active', 86, '2025-12-16 03:09:29'),
(88, 'Dominican Republic', 'DO', 'active', 87, '2025-12-16 03:09:29'),
(89, 'Jamaica', 'JM', 'active', 88, '2025-12-16 03:09:29'),
(90, 'Cuba', 'CU', 'active', 89, '2025-12-16 03:09:29'),
(91, 'Guatemala', 'GT', 'active', 90, '2025-12-16 03:09:29'),
(92, 'Honduras', 'HN', 'active', 91, '2025-12-16 03:09:29'),
(93, 'El Salvador', 'SV', 'active', 92, '2025-12-16 03:09:29'),
(94, 'Nicaragua', 'NI', 'active', 93, '2025-12-16 03:09:29'),
(95, 'South Africa', 'ZA', 'active', 94, '2025-12-16 03:09:29'),
(96, 'Morocco', 'MA', 'active', 95, '2025-12-16 03:09:29'),
(97, 'Kenya', 'KE', 'active', 96, '2025-12-16 03:09:29'),
(98, 'Nigeria', 'NG', 'active', 97, '2025-12-16 03:09:29'),
(99, 'Ghana', 'GH', 'active', 98, '2025-12-16 03:09:29'),
(100, 'Tanzania', 'TZ', 'active', 99, '2025-12-16 03:09:29'),
(101, 'Ethiopia', 'ET', 'active', 100, '2025-12-16 03:09:29'),
(102, 'Uganda', 'UG', 'active', 101, '2025-12-16 03:09:29'),
(103, 'Algeria', 'DZ', 'active', 102, '2025-12-16 03:09:29'),
(104, 'Tunisia', 'TN', 'active', 103, '2025-12-16 03:09:29'),
(105, 'Senegal', 'SN', 'active', 104, '2025-12-16 03:09:29'),
(106, 'Ivory Coast', 'CI', 'active', 105, '2025-12-16 03:09:29'),
(107, 'Cameroon', 'CM', 'active', 106, '2025-12-16 03:09:29'),
(108, 'Zimbabwe', 'ZW', 'active', 107, '2025-12-16 03:09:29'),
(109, 'Zambia', 'ZM', 'active', 108, '2025-12-16 03:09:29'),
(110, 'Botswana', 'BW', 'active', 109, '2025-12-16 03:09:29'),
(111, 'Namibia', 'NA', 'active', 110, '2025-12-16 03:09:29'),
(112, 'Mauritius', 'MU', 'active', 111, '2025-12-16 03:09:29'),
(113, 'New Zealand', 'NZ', 'active', 112, '2025-12-16 03:09:29'),
(114, 'Fiji', 'FJ', 'active', 113, '2025-12-16 03:09:29'),
(115, 'Papua New Guinea', 'PG', 'active', 114, '2025-12-16 03:09:29'),
(116, 'Samoa', 'WS', 'active', 115, '2025-12-16 03:09:29'),
(117, 'Tonga', 'TO', 'active', 116, '2025-12-16 03:09:29'),
(118, 'Vanuatu', 'VU', 'active', 117, '2025-12-16 03:09:29'),
(119, 'Europe eSIM', 'EU', 'active', 201, '2025-12-16 03:09:29'),
(120, 'Asia eSIM', 'ASIA', 'active', 202, '2025-12-16 03:09:29'),
(121, 'Americas eSIM', 'AMER', 'active', 203, '2025-12-16 03:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `frozen_payments`
--

CREATE TABLE `frozen_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(18,6) NOT NULL,
  `status` enum('frozen','released','refunded') NOT NULL DEFAULT 'frozen',
  `frozen_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `release_at` datetime NOT NULL,
  `released_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `frozen_payments`
--

INSERT INTO `frozen_payments` (`id`, `seller_id`, `order_id`, `amount`, `status`, `frozen_at`, `release_at`, `released_at`) VALUES
(1, 1, 1, 13.140000, 'frozen', '2025-12-13 11:38:54', '2025-12-14 16:38:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(29, 1, 'support', 'New Support Ticket', 'New ticket #TKT-E124029A: sadfasdfsd', 'http://localhost/admin/support.php?view=1', 1, '2025-12-13 14:26:32'),
(30, 1, 'support', 'New Support Ticket', 'New ticket #TKT-1FC2BF17: asdfasdf', 'http://localhost/admin/support.php?view=2', 1, '2025-12-13 14:27:37'),
(42, 12, 'verification', 'Account Verified', 'Your seller account has been verified by admin.', NULL, 0, '2025-12-22 19:58:16'),
(43, 12, 'product', 'Product Approved', 'Your product \"Twitter Accounts\" has been approved and is now live!', 'https://techvoyager.site/product/9', 0, '2025-12-22 19:59:23'),
(44, 12, 'product', 'Product Approved', 'Your product \"Twitter Accounts\" has been approved and is now live!', 'https://techvoyager.site/product/9', 0, '2025-12-22 20:08:02'),
(45, 10, 'wallet', 'Credit Added', '1.00 USDT has been added to your wallet. Reason: ', 'https://techvoyager.site/wallet.php', 0, '2025-12-22 20:15:36'),
(46, 10, 'order', 'Order Placed', 'Your order #ORD-F11CF7-1745 has been placed.', 'https://techvoyager.site/orders.php?view=7', 0, '2025-12-22 20:15:43'),
(47, 12, 'sale', 'New Sale', 'You have a new order #ORD-F11CF7-1745', 'https://techvoyager.site/seller/order/7', 0, '2025-12-22 20:15:43'),
(48, 12, 'product', 'Product Approved', 'Your product \"Twitter Accounts\" has been approved and is now live!', 'https://techvoyager.site/product/9', 0, '2025-12-22 20:17:48'),
(49, 10, 'order', 'Order Placed', 'Your order #ORD-D5B200-6439 has been placed.', 'https://techvoyager.site/orders.php?view=8', 0, '2025-12-22 20:18:37'),
(50, 12, 'sale', 'New Sale', 'You have a new order #ORD-D5B200-6439', 'https://techvoyager.site/seller/order/8', 0, '2025-12-22 20:18:37'),
(51, 12, 'product', 'Product Approved', 'Your product \"Twitter Accounts\" has been approved and is now live!', 'https://techvoyager.site/product/9', 0, '2025-12-22 20:19:21'),
(52, 10, 'order', 'Order Placed', 'Your order #ORD-417F1E-7788 has been placed.', 'https://techvoyager.site/orders.php?view=9', 0, '2025-12-22 20:20:04'),
(53, 12, 'sale', 'New Sale', 'You have a new order #ORD-417F1E-7788', 'https://techvoyager.site/seller/order/9', 0, '2025-12-22 20:20:04'),
(54, 10, 'order', 'Order Placed', 'Your order #ORD-027DE1-2283 has been placed.', 'https://techvoyager.site/orders.php?view=10', 0, '2025-12-22 20:22:08'),
(55, 12, 'sale', 'New Sale', 'You have a new order #ORD-027DE1-2283', 'https://techvoyager.site/seller/order/10', 0, '2025-12-22 20:22:08'),
(56, 12, 'product', 'Product Approved', 'Your product \"Twitter Accounts\" has been approved and is now live!', 'https://techvoyager.site/product/9', 0, '2025-12-22 20:23:40'),
(57, 10, 'wallet', 'Credit Added', '15.00 USDT has been added to your wallet. Reason: ', 'https://techvoyager.site/wallet.php', 0, '2025-12-22 20:24:07'),
(58, 10, 'order', 'Order Placed', 'Your order #ORD-9D4F90-9384 has been placed.', 'https://techvoyager.site/orders.php?view=11', 0, '2025-12-22 20:24:09'),
(59, 12, 'sale', 'New Sale', 'You have a new order #ORD-9D4F90-9384', 'https://techvoyager.site/seller/order/11', 0, '2025-12-22 20:24:09'),
(60, 10, 'order', 'Order Placed', 'Your order #ORD-98D907-6587 has been placed.', 'https://techvoyager.site/orders.php?view=12', 0, '2025-12-22 20:24:57'),
(61, 12, 'sale', 'New Sale', 'You have a new order #ORD-98D907-6587', 'https://techvoyager.site/seller/order/12', 0, '2025-12-22 20:24:57'),
(62, 11, 'wallet', 'Credit Added', '10.00 USDT has been added to your wallet. Reason: test', 'https://techvoyager.site/wallet.php', 0, '2025-12-22 20:43:08'),
(63, 12, 'product', 'Product Approved', 'Your product \"Twitter Accounts\" has been approved and is now live!', 'https://techvoyager.site/product/9', 0, '2025-12-22 20:47:19'),
(64, 12, 'product', 'Product Approved', 'Your product \"Twitter Accounts\" has been approved and is now live!', 'https://techvoyager.site/product/9', 0, '2025-12-22 20:49:12'),
(65, 12, 'product', 'Product Rejected', 'Your product \"Twitter Accounts\" has been rejected. Please review and resubmit.', 'https://techvoyager.site/seller/products.php?action=edit&id=9', 0, '2025-12-22 20:49:19'),
(66, 13, 'product', 'Product Approved', 'Your product \"teststst\" has been approved and is now live!', 'https://techvoyager.site/product/10', 0, '2025-12-24 09:12:40'),
(67, 14, 'wallet', 'Credit Added', '50.00 USDT has been added to your wallet. Reason: ', 'https://techvoyager.site/wallet.php', 0, '2025-12-24 09:13:38'),
(68, 14, 'order', 'Order Placed', 'Your order #ORD-B42C78-1864 has been placed.', 'https://techvoyager.site/orders.php?view=13', 0, '2025-12-24 09:13:47'),
(69, 13, 'sale', 'New Sale', 'You have a new order #ORD-B42C78-1864', 'https://techvoyager.site/seller/order/13', 0, '2025-12-24 09:13:47'),
(70, 13, 'product', 'Product Approved', 'Your product \"testnew\" has been approved and is now live!', 'https://techvoyager.site/product/11', 0, '2025-12-24 09:26:06'),
(71, 14, 'order', 'Order Placed', 'Your order #ORD-0420A4-4211 has been placed.', 'https://techvoyager.site/orders.php?view=14', 0, '2025-12-24 09:26:24'),
(72, 13, 'sale', 'New Sale', 'You have a new order #ORD-0420A4-4211', 'https://techvoyager.site/seller/order/14', 0, '2025-12-24 09:26:24'),
(73, 11, 'product', 'Product Approved', 'Your product \"testtt\" has been approved and is now live!', 'https://techvoyager.site/product/12', 0, '2025-12-25 01:24:31'),
(74, 11, 'product', 'Product Approved', 'Your product \"testtt\" has been approved and is now live!', 'https://techvoyager.site/product/12', 0, '2025-12-25 01:24:35'),
(75, 11, 'product', 'Product Approved', 'Your product \"testtt\" has been approved and is now live!', 'https://techvoyager.site/product/12', 0, '2025-12-25 08:39:32'),
(76, 12, 'product', 'Product Approved', 'Your product \"Twitter Accounts\" has been approved and is now live!', 'https://techvoyager.site/product/9', 0, '2025-12-25 08:39:53'),
(77, 15, 'wallet', 'Credit Added', '10.00 USDT has been added to your wallet. Reason: test', 'https://techvoyager.site/wallet.php', 0, '2025-12-25 08:41:23'),
(78, 15, 'order', 'Order Placed', 'Your order #ORD-116D36-8054 has been placed.', 'https://techvoyager.site/orders.php?view=15', 0, '2025-12-25 08:41:53'),
(79, 12, 'sale', 'New Sale', 'You have a new order #ORD-116D36-8054', 'https://techvoyager.site/seller/order/15', 0, '2025-12-25 08:41:53');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(18,6) NOT NULL,
  `total_amount` decimal(18,6) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `commission_amount` decimal(18,6) NOT NULL,
  `seller_amount` decimal(18,6) NOT NULL,
  `delivery_type` enum('auto','manual') NOT NULL,
  `delivery_data` text DEFAULT NULL,
  `status` enum('pending','processing','delivered','completed','cancelled','refunded','disputed') NOT NULL DEFAULT 'pending',
  `buyer_reviewed` tinyint(1) DEFAULT 0,
  `seller_reviewed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `payment_released` tinyint(1) DEFAULT 0,
  `payment_released_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `buyer_id`, `seller_id`, `product_id`, `quantity`, `unit_price`, `total_amount`, `commission_rate`, `commission_amount`, `seller_amount`, `delivery_type`, `delivery_data`, `status`, `buyer_reviewed`, `seller_reviewed`, `created_at`, `completed_at`, `delivered_at`, `payment_released`, `payment_released_at`, `updated_at`) VALUES
(7, 'ORD-F11CF7-1745', 10, 7, 9, 2, 0.010000, 0.020000, 2.00, 0.000400, 0.019600, 'auto', 'test1:test\ntest2:tes', 'completed', 0, 0, '2025-12-22 20:15:43', '2025-12-22 20:15:43', NULL, 0, NULL, '2025-12-22 20:15:43'),
(8, 'ORD-D5B200-6439', 10, 7, 9, 2, 0.010000, 0.020000, 2.00, 0.000400, 0.019600, 'auto', 'test3:test\ntest4:testa', 'completed', 0, 1, '2025-12-22 20:18:37', '2025-12-22 20:18:37', NULL, 0, NULL, '2025-12-22 20:19:40'),
(9, 'ORD-417F1E-7788', 10, 7, 9, 3, 0.010000, 0.030000, 2.00, 0.000600, 0.029400, 'auto', 'test3:test\ntest4:testa\ntest3:test', 'completed', 0, 0, '2025-12-22 20:20:04', '2025-12-22 20:20:04', NULL, 0, NULL, '2025-12-22 20:20:04'),
(10, 'ORD-027DE1-2283', 10, 7, 9, 16, 0.010000, 0.160000, 2.00, 0.003200, 0.156800, 'auto', 'test4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa', 'completed', 0, 0, '2025-12-22 20:22:08', '2025-12-22 20:22:08', NULL, 0, NULL, '2025-12-22 20:22:08'),
(11, 'ORD-9D4F90-9384', 10, 7, 9, 1, 0.010000, 0.010000, 2.00, 0.000200, 0.009800, 'auto', 'test3:test', 'completed', 0, 0, '2025-12-22 20:24:09', '2025-12-22 20:24:09', NULL, 0, NULL, '2025-12-22 20:24:09'),
(12, 'ORD-98D907-6587', 10, 7, 9, 89, 0.010000, 0.890000, 2.00, 0.017800, 0.872200, 'auto', 'test4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa\ntest3:test\ntest4:testa:3834823:3434:^434333\ntest4:testa\ntest3:test\ntest4:testa', 'completed', 0, 0, '2025-12-22 20:24:57', '2025-12-22 20:24:57', NULL, 0, NULL, '2025-12-22 20:24:57'),
(14, 'ORD-0420A4-4211', 14, 8, 11, 5, 1.000000, 5.000000, 2.00, 0.100000, 4.900000, 'auto', 'fsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf', 'completed', 0, 0, '2025-12-24 09:26:24', '2025-12-24 09:26:24', NULL, 0, NULL, '2025-12-24 09:26:24'),
(15, 'ORD-116D36-8054', 15, 7, 9, 1, 0.010000, 0.010000, 2.00, 0.000200, 0.009800, 'auto', 'test3:test', 'completed', 0, 0, '2025-12-25 08:41:53', '2025-12-25 08:41:53', NULL, 0, NULL, '2025-12-25 08:41:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_messages`
--

CREATE TABLE `order_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `attachment_name` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `price` decimal(18,6) NOT NULL,
  `stock_quantity` int(11) DEFAULT -1,
  `delivery_type` enum('auto','manual') NOT NULL DEFAULT 'manual',
  `delivery_data` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `status` enum('active','inactive','pending','rejected') NOT NULL DEFAULT 'pending',
  `total_sales` int(10) UNSIGNED DEFAULT 0,
  `rating_average` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `name`, `slug`, `description`, `short_description`, `price`, `stock_quantity`, `delivery_type`, `delivery_data`, `thumbnail`, `images`, `status`, `total_sales`, `rating_average`, `rating_count`, `created_at`, `updated_at`) VALUES
(9, 7, 13, 'Twitter Accounts', 'twitter-accounts', 'Login to accounts is done via login:password with the provided 2FA ( 2fa.fb.rip ), or via the provided AUTH_TOKEN\r\nAll accounts have two-factor authentication installed ( 2fa.fb.rip ).\r\nThe profile is not filled in (empty).\r\nGender: male or female.\r\n Registered with IP addresses from different countries around the world.\r\nWhen using AUTH_TOKEN, you will not be able to change your password or other account information!\r\nAccount format: login:password:mail:passwordmail:2FA:CT0:AUTH_TOKEN\r\nStore Rules\r\n\r\nProduct inspection is possible within 1 hour from the moment of purchase.\r\nComplaints will only be accepted if you have a video recording of your screen taken from the moment of purchase and during account verification.\r\nAccounts are checked exclusively manually.\r\nOur store uses private software and proxies for verification, which protects accounts from blocking.\r\nBy making a purchase, you agree to the store\'s terms and conditions. Ignorance of the terms and conditions does not absolve you from liability.\r\nPublic checkers and proxies for verifying purchased accounts are not allowed.\r\nUseful materials', 'Twitter Accounts', 0.010000, 1, 'auto', 'test4:testa:3834823:3434:^434333', 'products/024dfe42efd7c7e4.jpg', NULL, 'active', 114, 0.00, 0, '2025-12-22 19:55:23', '2025-12-25 08:41:53'),
(11, 8, 10, 'testnew', 'testnew', 'sdafsdafsdf', 'sdaf', 1.000000, 6, 'auto', 'fsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf\nfsdfsadfsdafsdfsdf', NULL, NULL, 'active', 5, 0.00, 0, '2025-12-24 09:25:55', '2025-12-24 09:26:24'),
(12, 6, 9, 'testtt', 'testtt', '', '', 11.000000, 6, 'auto', 'test@test.com:obama123\r\ntest1@test.com:obama123554\r\ntest2@test.com:obama1235\r\ntest3@test.com:obama12337\r\ntes4t@test.com:obama1237\r\ntest5@test.com:obama1239', NULL, NULL, 'active', 0, 0.00, 0, '2025-12-24 21:46:18', '2025-12-25 01:24:31');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `status` enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `shop_slug` varchar(100) NOT NULL,
  `shop_description` text DEFAULT NULL,
  `shop_logo` varchar(255) DEFAULT NULL,
  `shop_banner` varchar(255) DEFAULT NULL,
  `verification_status` enum('unverified','pending','verified') NOT NULL DEFAULT 'unverified',
  `verified_at` timestamp NULL DEFAULT NULL,
  `total_sales` int(10) UNSIGNED DEFAULT 0,
  `total_earnings` decimal(18,6) DEFAULT 0.000000,
  `rating_average` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(10) UNSIGNED DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `user_id`, `shop_name`, `shop_slug`, `shop_description`, `shop_logo`, `shop_banner`, `verification_status`, `verified_at`, `total_sales`, `total_earnings`, `rating_average`, `rating_count`, `created_at`, `updated_at`) VALUES
(6, 11, 'seller\'s Shop', 'seller-shop', NULL, NULL, NULL, 'unverified', NULL, 0, 0.000000, 0.00, 0, '2025-12-20 21:36:26', '2025-12-20 21:36:26'),
(7, 12, 'Seller1', 'seller1', 'Selling X accounts', NULL, NULL, 'verified', '2025-12-22 19:58:16', 114, 1.117200, 5.00, 1, '2025-12-22 19:46:10', '2025-12-25 08:41:53'),
(8, 13, 'safasdf', 'safasdf', 'sdafasdf', NULL, NULL, 'unverified', NULL, 10, 14.700000, 0.00, 0, '2025-12-24 09:12:14', '2025-12-24 09:26:24');

-- --------------------------------------------------------

--
-- Table structure for table `seller_reviews`
--

CREATE TABLE `seller_reviews` (
  `id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `buyer_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `status` enum('active','hidden','deleted') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `seller_reviews`
--

INSERT INTO `seller_reviews` (`id`, `seller_id`, `buyer_id`, `order_id`, `rating`, `comment`, `status`, `created_at`, `updated_at`) VALUES
(2, 7, 10, 8, 5, 'Great Support', 'active', '2025-12-22 20:19:40', '2025-12-22 20:19:40');

-- --------------------------------------------------------

--
-- Table structure for table `seller_verifications`
--

CREATE TABLE `seller_verifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `seller_id` int(10) UNSIGNED NOT NULL,
  `tron_wallet_address` varchar(100) NOT NULL,
  `amount_required` decimal(18,6) NOT NULL DEFAULT 100.000000,
  `amount_received` decimal(18,6) DEFAULT 0.000000,
  `tx_hash` varchar(100) DEFAULT NULL,
  `sender_wallet` varchar(100) DEFAULT NULL,
  `status` enum('pending','confirmed','failed','expired','manual_approved','manual_rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') NOT NULL DEFAULT 'string',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'site_name', 'ABC Demo Store', 'string', 'Website name', '2025-12-17 19:17:57'),
(2, 'site_description', 'Multi-Vendor Digital Marketplace', 'string', 'Website description', '2025-12-12 16:44:08'),
(3, 'commission_rate', '2', 'number', 'Platform commission percentage', '2025-12-22 20:13:55'),
(4, 'verification_fee', '100', 'number', 'Seller verification fee in USDT', '2025-12-12 16:44:08'),
(5, 'min_withdrawal', '50', 'number', 'Minimum withdrawal amount in USDT', '2025-12-22 20:13:55'),
(6, 'payment_expiry_hours', '24', 'number', 'Hours before payment expires', '2025-12-12 16:44:08'),
(7, 'tron_api_key', '', 'string', 'TronGrid API Key', '2025-12-12 16:44:08'),
(8, 'usdt_contract', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', 'string', 'USDT TRC20 Contract Address', '2025-12-12 16:44:08'),
(9, 'maintenance_mode', '0', 'boolean', 'Enable maintenance mode', '2025-12-12 16:44:08'),
(10, 'auto_approve_products', '0', 'boolean', 'Auto approve new products', '2025-12-12 16:44:08');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('general','payment','order','technical','seller','report','other') DEFAULT 'general',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','waiting','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_messages`
--

CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tron_payments`
--

CREATE TABLE `tron_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `wallet_address` varchar(100) NOT NULL,
  `private_key_encrypted` text NOT NULL,
  `payment_type` enum('deposit','verification','withdrawal') NOT NULL,
  `expected_amount` decimal(18,6) NOT NULL,
  `received_amount` decimal(18,6) DEFAULT 0.000000,
  `tx_hash` varchar(100) DEFAULT NULL,
  `sender_wallet` varchar(100) DEFAULT NULL,
  `confirmations` int(11) DEFAULT 0,
  `status` enum('pending','confirming','confirmed','failed','expired') NOT NULL DEFAULT 'pending',
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','seller','buyer') NOT NULL DEFAULT 'buyer',
  `status` enum('active','suspended','banned') NOT NULL DEFAULT 'active',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `avatar`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'demoadmin', 'admin@test.com', '$2y$12$0myfzC2cLJk0oNNJZtgkr.ihG3sOz8o1/fvVfBkr5uwmaqjdiy32e', 'admin', 'active', NULL, '2025-12-12 16:44:08', '2025-12-25 08:39:09', '2025-12-25 08:39:09'),
(10, 'test', 'test@test.com', '$2y$12$j7ad52KBt565OpgFYELQu.Jh6A/JWY9b86L4xsJONrV/1Z3G2CH7C', 'buyer', 'active', NULL, '2025-12-17 21:28:40', '2025-12-22 19:55:40', '2025-12-22 19:55:40'),
(11, 'seller', 'seller@seller.com', '$2y$12$QRgPk5OgVhfEBopyT.Du4ufqli1FBDR30gPWWGP7bZ4c9Td48jVS2', 'seller', 'active', NULL, '2025-12-20 21:35:57', '2025-12-24 21:43:28', '2025-12-24 21:43:28'),
(12, 'seller1', 'seller1@test.com', '$2y$12$evkH1HjIBaOZnEgyBzldZee53B2F3NwCnv0rTCFLaKa.Ossm2G31q', 'seller', 'active', NULL, '2025-12-22 19:45:37', '2025-12-25 08:38:15', '2025-12-25 08:38:15'),
(13, 'test1', 'sdafasdfasdfasdft@test.com', '$2y$12$bY3DfkZGl3OAQneoxIvile1RH3ojLq4udcxeufgy4JmS7zyrrrW6.', 'seller', 'active', NULL, '2025-12-24 09:12:04', '2025-12-24 09:12:14', NULL),
(14, 'buyer111', 'sdafsdf@asdfsdf.com', '$2y$12$9yZmQmb4Jq41AxaCS96GCOudB0PbU3CArs8J1aHpNOiaXf1NjfRcK', 'buyer', 'active', NULL, '2025-12-24 09:13:27', '2025-12-24 09:13:27', NULL),
(15, 'buyer', 'buyer@test.com', '$2y$12$SQdNkXPEWimKO.bJf1E8RuvbDV/rzZ0sEnHMaZdaalD34ht1LIMzy', 'buyer', 'active', NULL, '2025-12-25 08:41:03', '2025-12-25 08:41:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

CREATE TABLE `wallets` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `balance` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `pending_balance` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `total_deposited` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `total_withdrawn` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `total_spent` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `total_earned` decimal(18,6) NOT NULL DEFAULT 0.000000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `balance`, `pending_balance`, `total_deposited`, `total_withdrawn`, `total_spent`, `total_earned`, `created_at`, `updated_at`) VALUES
(1, 1, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, 0.000000, '2025-12-12 16:44:08', '2025-12-12 16:44:08'),
(10, 10, 14.870000, 0.000000, 16.000000, 0.000000, 1.130000, 0.000000, '2025-12-17 21:28:40', '2025-12-22 20:24:57'),
(11, 11, 10.000000, 0.000000, 10.000000, 0.000000, 0.000000, 0.000000, '2025-12-20 21:35:57', '2025-12-22 20:43:08'),
(12, 12, 1.117200, 0.000000, 0.000000, 0.000000, 0.000000, 1.117200, '2025-12-22 19:45:37', '2025-12-25 08:41:53'),
(13, 13, 14.700000, 0.000000, 0.000000, 0.000000, 0.000000, 14.700000, '2025-12-24 09:12:04', '2025-12-24 09:26:24'),
(14, 14, 35.000000, 0.000000, 50.000000, 0.000000, 15.000000, 0.000000, '2025-12-24 09:13:27', '2025-12-24 09:26:24'),
(15, 15, 9.990000, 0.000000, 10.000000, 0.000000, 0.010000, 0.000000, '2025-12-25 08:41:03', '2025-12-25 08:41:53');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `wallet_id` int(10) UNSIGNED NOT NULL,
  `type` enum('deposit','withdrawal','purchase','sale','refund','commission','verification_fee') NOT NULL,
  `amount` decimal(18,6) NOT NULL,
  `balance_before` decimal(18,6) NOT NULL,
  `balance_after` decimal(18,6) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`id`, `wallet_id`, `type`, `amount`, `balance_before`, `balance_after`, `reference_type`, `reference_id`, `description`, `status`, `created_at`) VALUES
(14, 10, 'deposit', 1.000000, 0.000000, 0.000000, NULL, NULL, 'Admin Credit: ', 'completed', '2025-12-22 20:15:36'),
(15, 10, 'purchase', -0.020000, 1.000000, 0.980000, 'order', 7, 'Purchase: Twitter Accounts', 'completed', '2025-12-22 20:15:43'),
(16, 12, 'sale', 0.019600, 0.000000, 0.019600, 'order', 7, 'Sale: Twitter Accounts', 'completed', '2025-12-22 20:15:43'),
(17, 10, 'purchase', -0.020000, 0.980000, 0.960000, 'order', 8, 'Purchase: Twitter Accounts', 'completed', '2025-12-22 20:18:37'),
(18, 12, 'sale', 0.019600, 0.019600, 0.039200, 'order', 8, 'Sale: Twitter Accounts', 'completed', '2025-12-22 20:18:37'),
(19, 10, 'purchase', -0.030000, 0.960000, 0.930000, 'order', 9, 'Purchase: Twitter Accounts', 'completed', '2025-12-22 20:20:04'),
(20, 12, 'sale', 0.029400, 0.039200, 0.068600, 'order', 9, 'Sale: Twitter Accounts', 'completed', '2025-12-22 20:20:04'),
(21, 10, 'purchase', -0.160000, 0.930000, 0.770000, 'order', 10, 'Purchase: Twitter Accounts', 'completed', '2025-12-22 20:22:08'),
(22, 12, 'sale', 0.156800, 0.068600, 0.225400, 'order', 10, 'Sale: Twitter Accounts', 'completed', '2025-12-22 20:22:08'),
(23, 10, 'deposit', 15.000000, 0.000000, 0.000000, NULL, NULL, 'Admin Credit: ', 'completed', '2025-12-22 20:24:07'),
(24, 10, 'purchase', -0.010000, 15.770000, 15.760000, 'order', 11, 'Purchase: Twitter Accounts', 'completed', '2025-12-22 20:24:09'),
(25, 12, 'sale', 0.009800, 0.225400, 0.235200, 'order', 11, 'Sale: Twitter Accounts', 'completed', '2025-12-22 20:24:09'),
(26, 10, 'purchase', -0.890000, 15.760000, 14.870000, 'order', 12, 'Purchase: Twitter Accounts', 'completed', '2025-12-22 20:24:57'),
(27, 12, 'sale', 0.872200, 0.235200, 1.107400, 'order', 12, 'Sale: Twitter Accounts', 'completed', '2025-12-22 20:24:57'),
(28, 11, 'deposit', 10.000000, 0.000000, 0.000000, NULL, NULL, 'Admin Credit: test', 'completed', '2025-12-22 20:43:08'),
(29, 14, 'deposit', 50.000000, 0.000000, 0.000000, NULL, NULL, 'Admin Credit: ', 'completed', '2025-12-24 09:13:38'),
(30, 14, 'purchase', -10.000000, 50.000000, 40.000000, 'order', 13, 'Purchase: teststst', 'completed', '2025-12-24 09:13:47'),
(31, 13, 'sale', 9.800000, 0.000000, 9.800000, 'order', 13, 'Sale: teststst', 'completed', '2025-12-24 09:13:47'),
(32, 14, 'purchase', -5.000000, 40.000000, 35.000000, 'order', 14, 'Purchase: testnew', 'completed', '2025-12-24 09:26:24'),
(33, 13, 'sale', 4.900000, 9.800000, 14.700000, 'order', 14, 'Sale: testnew', 'completed', '2025-12-24 09:26:24'),
(34, 15, 'deposit', 10.000000, 0.000000, 0.000000, NULL, NULL, 'Admin Credit: test', 'completed', '2025-12-25 08:41:23'),
(35, 15, 'purchase', -0.010000, 10.000000, 9.990000, 'order', 15, 'Purchase: Twitter Accounts', 'completed', '2025-12-25 08:41:53'),
(36, 12, 'sale', 0.009800, 1.107400, 1.117200, 'order', 15, 'Sale: Twitter Accounts', 'completed', '2025-12-25 08:41:53');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(18,6) NOT NULL,
  `wallet_address` varchar(100) NOT NULL,
  `tx_hash` varchar(100) DEFAULT NULL,
  `status` enum('pending','processing','completed','rejected') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `processed_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indexes for table `crypto_payments`
--
ALTER TABLE `crypto_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `disputes`
--
ALTER TABLE `disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `initiated_by` (`initiated_by`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `esim_countries`
--
ALTER TABLE `esim_countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `frozen_payments`
--
ALTER TABLE `frozen_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_release_at` (`release_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_buyer` (`buyer_id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_order_number` (`order_number`);

--
-- Indexes for table `order_messages`
--
ALTER TABLE `order_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_order_review` (`product_id`,`order_id`),
  ADD KEY `buyer_id` (`buyer_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `shop_slug` (`shop_slug`),
  ADD KEY `idx_verification` (`verification_status`),
  ADD KEY `idx_shop_slug` (`shop_slug`);

--
-- Indexes for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_review` (`order_id`),
  ADD KEY `idx_seller` (`seller_id`),
  ADD KEY `idx_buyer` (`buyer_id`);

--
-- Indexes for table `seller_verifications`
--
ALTER TABLE `seller_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_wallet` (`tron_wallet_address`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_last_activity` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ticket_id` (`ticket_id`);

--
-- Indexes for table `tron_payments`
--
ALTER TABLE `tron_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet` (`wallet_address`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_tx_hash` (`tx_hash`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wallet` (`wallet_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `crypto_payments`
--
ALTER TABLE `crypto_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `disputes`
--
ALTER TABLE `disputes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `esim_countries`
--
ALTER TABLE `esim_countries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `frozen_payments`
--
ALTER TABLE `frozen_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `order_messages`
--
ALTER TABLE `order_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `seller_verifications`
--
ALTER TABLE `seller_verifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ticket_messages`
--
ALTER TABLE `ticket_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tron_payments`
--
ALTER TABLE `tron_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `disputes`
--
ALTER TABLE `disputes`
  ADD CONSTRAINT `disputes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_ibfk_2` FOREIGN KEY (`initiated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disputes_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_messages`
--
ALTER TABLE `order_messages`
  ADD CONSTRAINT `order_messages_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sellers`
--
ALTER TABLE `sellers`
  ADD CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD CONSTRAINT `seller_reviews_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_reviews_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_verifications`
--
ALTER TABLE `seller_verifications`
  ADD CONSTRAINT `seller_verifications_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tron_payments`
--
ALTER TABLE `tron_payments`
  ADD CONSTRAINT `tron_payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

