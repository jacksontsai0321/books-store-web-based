-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2025-12-26 10:13:35
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `bookstore_db`
--

-- --------------------------------------------------------

--
-- 表的结构 `admin`
--

CREATE TABLE `admin` (
  `admin_id` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `admin`
--

INSERT INTO `admin` (`admin_id`, `email`, `username`, `password`, `full_name`, `created_at`) VALUES
('A0001', 'jackson@gmail.com', 'Jackson', '$2y$10$gqMGRmJLOUGaOqxJ4aMj3eXkkyIayVKRy5wPbOQgZaw/RXfXXLytq', 'Jackson', '2025-12-01 17:56:27'),
('A0002', 'staff1@gmail.com', 'staff1', '$2y$10$v9wrHhTgn0Z.UnWTRhDciODbeVMUtc8voKZjGuWrsKCBgUCVAaO/G', 'staff1', '2025-12-01 18:10:54'),
('A0003', 'cws@gmail.com', 'chuah dih shen', '$2y$10$GC14/hHUnRZLWk.Mu1XgMeBasWKp7rYNQzF7Gz.I41nPESDnkFtEi', 'chuah dih shen', '2025-12-07 23:35:37');

-- --------------------------------------------------------

--
-- 表的结构 `favorite`
--

CREATE TABLE `favorite` (
  `favorite_id` varchar(32) NOT NULL,
  `member_id` varchar(32) NOT NULL,
  `product_id` varchar(32) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `favorite`
--

INSERT INTO `favorite` (`favorite_id`, `member_id`, `product_id`, `created_at`) VALUES
('F0001', 'M0031', 'P0025', '2025-12-26 16:41:22'),
('F0002', 'M0031', 'P0024', '2025-12-26 16:41:24');

-- --------------------------------------------------------

--
-- 表的结构 `member`
--

CREATE TABLE `member` (
  `member_id` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `photo` varchar(200) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `member`
--

INSERT INTO `member` (`member_id`, `email`, `password`, `full_name`, `phone`, `address`, `photo`, `is_verified`, `created_at`) VALUES
('M0001', 'amelia.ng@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Amelia Ng', '012-3001001', '12 Lakeview Ave, KL', '', 1, '2025-12-01 17:56:27'),
('M0002', 'ben.tan@example.com', '$2y$10$zEQ20M32XZBKgs/HryqgRuEYYficaFv3OSTJ8edjol.ODyu0e67.y', 'Ben Tan', '012-3001002', '88 Garden Rd, KL', '/uploads/profile/member_M0002_1765190031.jpeg', 1, '2025-12-01 17:56:27'),
('M0003', 'chloe.lim@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Chloe Lim', '012-3001003', '5 Horizon Park, PJ', '', 1, '2025-12-01 17:56:27'),
('M0004', 'daniel.ong@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Daniel Ong', '012-3001004', '27 Maple Lane, PJ', '', 1, '2025-12-01 17:56:27'),
('M0005', 'elena.chan@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Elena Chan', '012-3001005', '46 Palm Grove, Subang', '', 1, '2025-12-01 17:56:27'),
('M0006', 'farid.hassan@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Farid Hassan', '012-3001006', '19 Seaside Dr, Klang', '', 1, '2025-12-01 17:56:27'),
('M0007', 'grace.leong@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Grace Leong', '012-3001007', '4 Pine Crest, KL', '', 1, '2025-12-01 17:56:27'),
('M0008', 'harris.lau@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Harris Lau', '012-3001008', '73 Riverbend, Shah Alam', '', 1, '2025-12-01 17:56:27'),
('M0009', 'isabelle.foo@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Isabelle Foo', '012-3001009', '101 Hilltop, KL', '', 1, '2025-12-01 17:56:27'),
('M0010', 'jayden.koh@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Jayden Koh', '012-3001010', '55 City Walk, KL', '', 1, '2025-12-01 17:56:27'),
('M0011', 'kylie.yap@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Kylie Yap', '012-3001011', '84 Orchid Way, PJ', '', 1, '2025-12-01 17:56:27'),
('M0012', 'liam.goh@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Liam Goh', '012-3001012', '32 Coral St, Bangsar', '', 1, '2025-12-01 17:56:27'),
('M0013', 'mia.teh@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Mia Teh', '012-3001013', '7 Ivy Court, KL', '', 1, '2025-12-01 17:56:27'),
('M0014', 'noah.ong@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Noah Ong', '012-3001014', '63 Brookside, Putrajaya', '', 1, '2025-12-01 17:56:27'),
('M0015', 'olivia.tan@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Olivia Tan', '012-3001015', '24 Sunset Blvd, KL', '', 1, '2025-12-01 17:56:27'),
('M0016', 'peter.chew@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Peter Chew', '012-3001016', '90 Elm Ridge, Serdang', '', 0, '2025-12-01 17:56:27'),
('M0017', 'qi.en.lim@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Qi En Lim', '012-3001017', '37 Pearl Ave, Cheras', '', 0, '2025-12-01 17:56:27'),
('M0018', 'ryan.ho@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Ryan Ho', '012-3001018', '28 Cedar Dr, KL', '', 0, '2025-12-01 17:56:27'),
('M0019', 'sophia.tay@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Sophia Tay', '012-3001019', '40 Melati Rd, KL', '', 0, '2025-12-01 17:56:27'),
('M0020', 'tristan.yong@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Tristan Yong', '012-3001020', '52 Dahlia Pl, PJ', '', 0, '2025-12-01 17:56:27'),
('M0021', 'uma.dev@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Uma Dev', '012-3001021', '11 Jasmine Ct, KL', '', 0, '2025-12-01 17:56:27'),
('M0022', 'vincent.low@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Vincent Low', '012-3001022', '75 Willow Way, KL', '', 0, '2025-12-01 17:56:27'),
('M0023', 'wen.yi.tan@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Wen Yi Tan', '012-3001023', '9 Laman Seri, Shah Alam', '', 0, '2025-12-01 17:56:27'),
('M0024', 'xavier.ling@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Xavier Ling', '012-3001024', '66 Clover Rise, PJ', '', 0, '2025-12-01 17:56:27'),
('M0025', 'yara.azmi@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Yara Azmi', '012-3001025', '18 Sapphire Sq, KL', '', 0, '2025-12-01 17:56:27'),
('M0026', 'zach.lee@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Zach Lee', '012-3001026', '21 Forest Run, KL', '', 0, '2025-12-01 17:56:27'),
('M0027', 'adriana.khoo@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Adriana Khoo', '012-3001027', '38 Lotus Terr, KL', '', 0, '2025-12-01 17:56:27'),
('M0028', 'brandon.teo@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Brandon Teo', '012-3001028', '44 Marina Bay, KL', '', 0, '2025-12-01 17:56:27'),
('M0029', 'celeste.gan@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Celeste Gan', '012-3001029', '13 Valley View, KL', '', 0, '2025-12-01 17:56:27'),
('M0030', 'darren.liew@example.com', '$2y$10$2OTV0qDoBW2mx03.Et.bBOFu7i9/yH877sk87cG.L1NLkVUHbP34K', 'Darren Liew', '012-3001030', '59 Summit Rd, KL', '', 0, '2025-12-01 17:56:27'),
('M0031', 'hacknews0123@gmail.com', '$2y$10$VAWbkkYG7L1T6Yy5XB/0vOm5uMqUOz7eewvHbqKbt9ohU2N5gz.Eq', 'test 3', '0123450001', 'jakdsolsaihsaisaoi', '/uploads/profile/member_M0031_1765867828.png', 1, '2025-12-14 15:51:54'),
('M0032', 'jktower123456@gmail.com', '$2y$10$7cOvq7YjJAKaiKRQRS8Y.uSadEYsJbRRBd7P6XqIpHQAfOLOkcSXC', 'jkowir', '11111111111111', '111', NULL, 1, '2025-12-16 15:39:12');

-- --------------------------------------------------------

--
-- 表的结构 `order_detail`
--

CREATE TABLE `order_detail` (
  `detail_id` varchar(10) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order_detail`
--

INSERT INTO `order_detail` (`detail_id`, `order_id`, `product_id`, `price`, `quantity`) VALUES
('D0001', 'O0001', 'P0001', 38.90, 1),
('D0002', 'O0001', 'P0006', 4.90, 3),
('D0003', 'O0001', 'P0014', 3.20, 5),
('D0004', 'O0002', 'P0002', 42.50, 1),
('D0005', 'O0002', 'P0015', 12.90, 2),
('D0006', 'O0002', 'P0018', 5.80, 6),
('D0007', 'O0003', 'P0011', 6.90, 4),
('D0008', 'O0003', 'P0017', 4.50, 6),
('D0009', 'O0003', 'P0024', 7.50, 2),
('D0010', 'O0004', 'P0003', 33.90, 2),
('D0011', 'O0004', 'P0022', 16.50, 4),
('D0012', 'O0005', 'P0007', 3.50, 4),
('D0013', 'O0005', 'P0008', 4.20, 5),
('D0014', 'O0005', 'P0021', 18.90, 1),
('D0015', 'O0006', 'P0009', 1.80, 10),
('D0016', 'O0006', 'P0023', 14.90, 4),
('D0017', 'O0006', 'P0025', 9.90, 3),
('D0018', 'O0007', 'P0016', 4.90, 5),
('D0019', 'O0007', 'P0020', 39.40, 1),
('D0020', 'O0007', 'P0012', 5.50, 3),
('D0021', 'O0008', 'P0004', 29.50, 2),
('D0022', 'O0008', 'P0024', 7.50, 6),
('D0023', 'O0008', 'P0025', 9.90, 5),
('D0024', 'O0009', 'P0006', 4.90, 4),
('D0025', 'O0009', 'P0019', 8.90, 3),
('D0026', 'O0010', 'P0005', 39.90, 2),
('D0027', 'O0010', 'P0023', 14.90, 5),
('D0028', 'O0010', 'P0021', 18.90, 2),
('D0029', 'O0011', 'P0013', 8.00, 4),
('D0030', 'O0011', 'P0020', 39.40, 1),
('D0031', 'O0011', 'P0018', 5.80, 8),
('D0032', 'O0012', 'P0003', 33.90, 2),
('D0033', 'O0012', 'P0015', 12.90, 5),
('D0034', 'O0013', 'P0014', 3.20, 7),
('D0035', 'O0013', 'P0021', 18.90, 2),
('D0036', 'O0014', 'P0002', 42.50, 2),
('D0037', 'O0014', 'P0025', 9.90, 7),
('D0038', 'O0015', 'P0011', 6.90, 4),
('D0039', 'O0015', 'P0016', 4.90, 7),
('D0040', 'O0016', 'P0017', 4.50, 10),
('D0041', 'O0016', 'P0024', 7.50, 6),
('D0042', 'O0017', 'P0007', 3.50, 9),
('D0043', 'O0017', 'P0019', 8.90, 5),
('D0044', 'O0018', 'P0001', 38.90, 1),
('D0045', 'O0018', 'P0022', 16.50, 5),
('D0046', 'O0019', 'P0014', 3.20, 5),
('D0047', 'O0019', 'P0025', 9.90, 3),
('D0048', 'O0020', 'P0012', 5.50, 6),
('D0049', 'O0020', 'P0004', 29.50, 4),
('D0050', 'O0021', 'P0007', 3.50, 4),
('D0051', 'O0021', 'P0014', 3.20, 7),
('D0052', 'O0021', 'P0001', 38.90, 1),
('D0053', 'O0022', 'P0002', 42.50, 1),
('D0054', 'O0022', 'P0015', 12.90, 3),
('D0055', 'O0022', 'P0018', 5.80, 6),
('D0056', 'O0023', 'P0011', 6.90, 4),
('D0057', 'O0023', 'P0017', 4.50, 5),
('D0058', 'O0023', 'P0024', 7.50, 3),
('D0059', 'O0024', 'P0003', 33.90, 2),
('D0060', 'O0024', 'P0022', 16.50, 4),
('D0061', 'O0024', 'P0019', 8.90, 4),
('D0062', 'O0025', 'P0008', 4.20, 5),
('D0063', 'O0025', 'P0021', 18.90, 2),
('D0064', 'O0025', 'P0006', 4.90, 3),
('D0065', 'O0026', 'P0005', 39.90, 2),
('D0066', 'O0026', 'P0023', 14.90, 3),
('D0067', 'O0026', 'P0025', 9.90, 5),
('D0068', 'O0027', 'P0016', 4.90, 6),
('D0069', 'O0027', 'P0012', 5.50, 3),
('D0070', 'O0027', 'P0020', 39.40, 1),
('D0071', 'O0028', 'P0004', 29.50, 3),
('D0072', 'O0028', 'P0024', 7.50, 8),
('D0073', 'O0028', 'P0025', 9.90, 4),
('D0074', 'O0029', 'P0019', 8.90, 3),
('D0075', 'O0029', 'P0016', 4.90, 6),
('D0076', 'O0030', 'P0005', 39.90, 2),
('D0077', 'O0030', 'P0023', 14.90, 5),
('D0078', 'O0030', 'P0021', 18.90, 2),
('D0079', 'O0030', 'P0014', 3.20, 10),
('D0080', 'O0031', 'P0013', 8.00, 3),
('D0081', 'O0031', 'P0020', 39.40, 1),
('D0082', 'O0031', 'P0018', 5.80, 8),
('D0083', 'O0032', 'P0015', 12.90, 6),
('D0084', 'O0032', 'P0003', 33.90, 2),
('D0085', 'O0033', 'P0011', 6.90, 4),
('D0086', 'O0033', 'P0016', 4.90, 5),
('D0087', 'O0034', 'P0002', 42.50, 2),
('D0088', 'O0034', 'P0025', 9.90, 7),
('D0089', 'O0035', 'P0007', 3.50, 10),
('D0090', 'O0035', 'P0019', 8.90, 5),
('D0091', 'O0036', 'P0017', 4.50, 10),
('D0092', 'O0036', 'P0024', 7.50, 6),
('D0093', 'O0036', 'P0006', 4.90, 5),
('D0094', 'O0037', 'P0008', 4.20, 6),
('D0095', 'O0037', 'P0021', 18.90, 2),
('D0096', 'O0038', 'P0022', 16.50, 5),
('D0097', 'O0038', 'P0003', 33.90, 1),
('D0098', 'O0038', 'P0012', 5.50, 6),
('D0099', 'O0039', 'P0014', 3.20, 6),
('D0100', 'O0039', 'P0025', 9.90, 4),
('D0101', 'O0040', 'P0004', 29.50, 4),
('D0102', 'O0040', 'P0023', 14.90, 5),
('D0103', 'O0041', 'P0013', 8.00, 4),
('D0104', 'O0041', 'P0018', 5.80, 8),
('D0105', 'O0042', 'P0001', 38.90, 2),
('D0106', 'O0042', 'P0024', 7.50, 8),
('D0107', 'O0043', 'P0016', 4.90, 5),
('D0108', 'O0043', 'P0011', 6.90, 4),
('D0109', 'O0044', 'P0020', 39.40, 2),
('D0110', 'O0044', 'P0005', 39.90, 2),
('D0111', 'O0044', 'P0021', 18.90, 2),
('D0112', 'O0045', 'P0007', 3.50, 6),
('D0113', 'O0045', 'P0008', 4.20, 5),
('D0114', 'O0046', 'P0003', 33.90, 2),
('D0115', 'O0046', 'P0015', 12.90, 6),
('D0116', 'O0047', 'P0019', 8.90, 4),
('D0117', 'O0047', 'P0024', 7.50, 5),
('D0118', 'O0048', 'P0004', 29.50, 3),
('D0119', 'O0048', 'P0025', 9.90, 5),
('D0120', 'O0049', 'P0023', 14.90, 5),
('D0121', 'O0049', 'P0005', 39.90, 2),
('D0122', 'O0049', 'P0001', 38.90, 1),
('D0123', 'O0050', 'P0006', 4.90, 6),
('D0124', 'O0050', 'P0014', 3.20, 8),
('D0125', 'O0050', 'P0007', 3.50, 6),
('DTL0001', 'O0051', 'P0023', 14.90, 1),
('DTL0002', 'O0052', 'P0023', 14.90, 1),
('DTL0003', 'O0053', 'P0014', 3.20, 1),
('DTL0004', 'O0054', 'P0025', 9.90, 2),
('DTL0005', 'O0055', 'P0014', 3.20, 1),
('DTL0006', 'O0056', 'P0024', 7.50, 1),
('DTL0007', 'O0057', 'P0001', 38.90, 1),
('DTL0008', 'O0059', 'P0025', 9.90, 1);

-- --------------------------------------------------------

--
-- 表的结构 `order_header`
--

CREATE TABLE `order_header` (
  `order_id` varchar(20) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(20) NOT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `updated_by` varchar(10) DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order_header`
--

INSERT INTO `order_header` (`order_id`, `member_id`, `order_date`, `total_amount`, `payment_method`, `status`, `updated_by`, `shipped_at`) VALUES
('O0001', 'M0001', '2025-02-10 10:15:22', 92.50, 'FPX', 'Pending', 'A0001', NULL),
('O0002', 'M0003', '2025-02-10 15:42:10', 115.60, 'Credit/Debit Card', 'Completed', 'A0001', NULL),
('O0003', 'M0004', '2025-02-11 09:30:55', 68.40, 'FPX', 'Paid', 'A0001', NULL),
('O0004', 'M0006', '2025-02-11 14:12:00', 142.20, 'FPX', 'Completed', 'A0001', NULL),
('O0005', 'M0008', '2025-02-12 11:07:33', 53.30, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0006', 'M0010', '2025-02-12 16:55:49', 130.90, 'FPX', 'Completed', 'A0001', NULL),
('O0007', 'M0002', '2025-02-13 13:21:18', 74.20, 'Credit/Debit Card', 'Paid', 'A0001', NULL),
('O0008', 'M0011', '2025-02-13 18:49:11', 167.40, 'FPX', 'Completed', 'A0001', NULL),
('O0009', 'M0014', '2025-02-14 10:23:41', 59.30, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0010', 'M0015', '2025-02-14 20:33:10', 188.70, 'Credit/Debit Card', 'Completed', 'A0001', NULL),
('O0011', 'M0003', '2025-02-15 08:57:55', 106.40, 'FPX', 'Completed', 'A0001', NULL),
('O0012', 'M0007', '2025-02-15 14:21:19', 147.50, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0013', 'M0009', '2025-02-16 12:05:47', 72.40, 'FPX', 'Completed', 'A0001', NULL),
('O0014', 'M0012', '2025-02-16 17:40:03', 157.10, 'Credit/Debit Card', 'Paid', 'A0001', NULL),
('O0015', 'M0005', '2025-02-17 11:19:52', 91.60, 'FPX', 'Completed', 'A0001', NULL),
('O0016', 'M0013', '2025-02-17 19:25:27', 118.50, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0017', 'M0001', '2025-02-18 09:43:30', 85.30, 'Credit/Debit Card', 'Completed', 'A0001', NULL),
('O0018', 'M0010', '2025-02-18 16:10:21', 143.20, 'FPX', 'Completed', 'A0001', NULL),
('O0019', 'M0015', '2025-02-19 13:56:45', 63.70, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0020', 'M0014', '2025-02-19 20:14:33', 176.40, 'FPX', 'Completed', 'A0001', NULL),
('O0021', 'M0001', '2025-11-01 10:13:22', 96.40, 'FPX', 'Paid', 'A0001', NULL),
('O0022', 'M0003', '2025-11-01 15:40:09', 121.60, 'Credit/Debit Card', 'Completed', 'A0001', NULL),
('O0023', 'M0005', '2025-11-02 09:31:55', 69.10, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0024', 'M0004', '2025-11-02 18:14:22', 141.20, 'FPX', 'Completed', 'A0001', NULL),
('O0025', 'M0008', '2025-11-03 11:02:40', 58.30, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0026', 'M0011', '2025-11-03 20:12:19', 153.50, 'FPX', 'Completed', 'A0001', NULL),
('O0027', 'M0002', '2025-11-04 13:50:35', 81.90, 'Credit/Debit Card', 'Paid', 'A0001', NULL),
('O0028', 'M0010', '2025-11-04 19:24:55', 169.80, 'FPX', 'Completed', 'A0001', NULL),
('O0029', 'M0014', '2025-11-05 10:44:11', 63.30, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0030', 'M0015', '2025-11-05 23:12:12', 192.40, 'Credit/Debit Card', 'Completed', 'A0001', NULL),
('O0031', 'M0003', '2025-11-06 08:43:51', 111.90, 'FPX', 'Completed', 'A0001', NULL),
('O0032', 'M0007', '2025-11-06 14:55:28', 148.20, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0033', 'M0006', '2025-11-07 09:22:03', 75.20, 'Credit/Debit Card', 'Completed', 'A0001', NULL),
('O0034', 'M0012', '2025-11-07 19:40:30', 158.80, 'FPX', 'Paid', 'A0001', NULL),
('O0035', 'M0009', '2025-11-08 11:14:44', 89.60, 'FPX', 'Completed', 'A0001', NULL),
('O0036', 'M0013', '2025-11-08 17:29:20', 121.90, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0037', 'M0001', '2025-11-09 10:00:51', 86.80, 'Credit/Debit Card', 'Completed', 'A0001', NULL),
('O0038', 'M0010', '2025-11-09 20:21:17', 144.50, 'FPX', 'Completed', 'A0001', NULL),
('O0039', 'M0015', '2025-11-10 13:59:40', 66.20, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0040', 'M0014', '2025-11-10 21:10:32', 172.70, 'FPX', 'Completed', 'A0001', NULL),
('O0041', 'M0002', '2025-11-12 11:22:14', 102.80, 'Credit/Debit Card', 'Paid', 'A0001', NULL),
('O0042', 'M0004', '2025-11-12 18:33:09', 136.40, 'FPX', 'Completed', 'A0001', NULL),
('O0043', 'M0005', '2025-11-13 12:11:48', 74.30, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0044', 'M0011', '2025-11-13 19:55:08', 166.80, 'FPX', 'Completed', 'A0001', NULL),
('O0045', 'M0008', '2025-11-15 09:31:35', 57.20, 'Credit/Debit Card', 'Paid', 'A0001', NULL),
('O0046', 'M0012', '2025-11-15 14:22:55', 152.90, 'FPX', 'Completed', 'A0001', NULL),
('O0047', 'M0003', '2025-11-17 20:10:44', 93.40, 'TNG eWallet', 'Paid', 'A0001', NULL),
('O0048', 'M0014', '2025-11-24 17:45:01', 139.80, 'FPX', 'Completed', 'A0001', NULL),
('O0049', 'M0015', '2025-11-27 22:14:31', 189.50, 'Credit/Debit Card', 'Completed', 'A0001', NULL),
('O0050', 'M0009', '2025-11-30 10:09:10', 84.90, 'FPX', 'Paid', 'A0001', NULL),
('O0051', 'M0002', '2025-12-08 18:31:35', 14.90, 'E-Wallet', 'Pending', NULL, NULL),
('O0052', 'M0002', '2025-12-08 18:32:23', 14.90, 'E-Wallet', 'Pending', NULL, NULL),
('O0053', 'M0031', '2025-12-14 16:06:06', 3.20, 'E-Wallet', 'Completed', NULL, NULL),
('O0054', 'M0002', '2025-12-14 16:41:13', 19.80, 'E-Wallet', 'Processing', NULL, NULL),
('O0055', 'M0002', '2025-12-14 16:46:52', 3.20, 'E-Wallet', 'Completed', NULL, NULL),
('O0056', 'M0031', '2025-12-14 18:00:12', 7.50, 'E-Wallet', 'Processing', NULL, NULL),
('O0057', 'M0031', '2025-12-16 14:39:25', 38.90, 'Credit Card', 'Refunded', NULL, '2025-12-26 17:07:35'),
('O0058', 'M0032', '2025-12-16 15:40:24', 1.00, 'E-Wallet', 'Completed', NULL, NULL),
('O0059', 'M0031', '2025-12-26 16:39:28', 9.90, 'PayPal', 'Refund Rejected', NULL, '2025-12-26 16:58:28');

-- --------------------------------------------------------

--
-- 表的结构 `order_payment`
--

CREATE TABLE `order_payment` (
  `payment_id` varchar(20) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `method` varchar(20) NOT NULL,
  `payment_status` varchar(20) NOT NULL,
  `fake_transaction_ref` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_by` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order_payment`
--

INSERT INTO `order_payment` (`payment_id`, `order_id`, `amount`, `method`, `payment_status`, `fake_transaction_ref`, `payment_date`, `created_by`) VALUES
('P0026', 'O0053', 3.20, 'E-Wallet', 'Completed', 'TXN693E7377791601765700471', '2025-12-14 16:21:11', NULL),
('P0027', 'O0054', 19.80, 'Credit Card', 'Completed', 'TXN693E784813F7B1765701704', '2025-12-14 16:41:44', 'A0001'),
('P0028', 'O0055', 3.20, 'E-Wallet', 'Completed', 'TXN693E798538BCA1765702021', '2025-12-14 16:47:01', 'A0001'),
('P0029', 'O0056', 7.50, 'E-Wallet', 'Completed', 'TXN693E8AADBFAD31765706413', '2025-12-14 18:00:13', 'A0001'),
('P0030', 'O0057', 38.90, 'Credit Card', 'Completed', 'TXN6940FEC85FBD21765867208', '2025-12-16 14:40:08', 'A0001'),
('P0031', 'O0058', 1.00, 'E-Wallet', 'Completed', 'TXN69410CE9E88261765870825', '2025-12-16 15:40:25', 'A0001'),
('P0032', 'O0059', 9.90, 'PayPal', 'Completed', 'paypal_capture:8A186414WN944125K', '2025-12-26 16:41:02', NULL),
('PAY0001', 'O0001', 92.50, 'Debit Card', 'Paid', 'FAKE-TXN-O0001', '2025-11-02 00:00:00', 'A0001'),
('PAY0002', 'O0002', 115.60, 'E-Wallet', 'Paid', 'FAKE-TXN-O0002', '2025-11-03 00:00:00', 'A0001'),
('PAY0003', 'O0003', 68.40, 'Credit Card', 'Paid', 'FAKE-TXN-O0003', '2025-11-04 00:00:00', 'A0001'),
('PAY0004', 'O0004', 142.20, 'Debit Card', 'Paid', 'FAKE-TXN-O0004', '2025-11-05 00:00:00', 'A0001'),
('PAY0005', 'O0005', 53.30, 'E-Wallet', 'Paid', 'FAKE-TXN-O0005', '2025-11-06 00:00:00', 'A0001'),
('PAY0006', 'O0006', 130.90, 'Credit Card', 'Paid', 'FAKE-TXN-O0006', '2025-11-07 00:00:00', 'A0001'),
('PAY0007', 'O0007', 74.20, 'Debit Card', 'Paid', 'FAKE-TXN-O0007', '2025-11-08 00:00:00', 'A0001'),
('PAY0008', 'O0008', 167.40, 'E-Wallet', 'Paid', 'FAKE-TXN-O0008', '2025-11-09 00:00:00', 'A0001'),
('PAY0009', 'O0009', 59.30, 'Credit Card', 'Paid', 'FAKE-TXN-O0009', '2025-11-10 00:00:00', 'A0001'),
('PAY0010', 'O0010', 188.70, 'Debit Card', 'Paid', 'FAKE-TXN-O0010', '2025-11-11 00:00:00', 'A0001'),
('PAY0011', 'O0011', 106.40, 'E-Wallet', 'Paid', 'FAKE-TXN-O0011', '2025-11-12 00:00:00', 'A0001'),
('PAY0012', 'O0012', 147.50, 'Credit Card', 'Paid', 'FAKE-TXN-O0012', '2025-11-13 00:00:00', 'A0001'),
('PAY0013', 'O0013', 72.40, 'Debit Card', 'Paid', 'FAKE-TXN-O0013', '2025-11-14 00:00:00', 'A0001'),
('PAY0014', 'O0014', 157.10, 'E-Wallet', 'Paid', 'FAKE-TXN-O0014', '2025-11-15 00:00:00', 'A0001'),
('PAY0015', 'O0015', 91.60, 'Credit Card', 'Paid', 'FAKE-TXN-O0015', '2025-11-16 00:00:00', 'A0001'),
('PAY0016', 'O0016', 118.50, 'Debit Card', 'Paid', 'FAKE-TXN-O0016', '2025-11-17 00:00:00', 'A0001'),
('PAY0017', 'O0017', 85.30, 'E-Wallet', 'Paid', 'FAKE-TXN-O0017', '2025-11-18 00:00:00', 'A0001'),
('PAY0018', 'O0018', 143.20, 'Credit Card', 'Paid', 'FAKE-TXN-O0018', '2025-11-19 00:00:00', 'A0001'),
('PAY0019', 'O0019', 63.70, 'Debit Card', 'Paid', 'FAKE-TXN-O0019', '2025-11-20 00:00:00', 'A0001'),
('PAY0020', 'O0020', 176.40, 'E-Wallet', 'Paid', 'FAKE-TXN-O0020', '2025-11-21 00:00:00', 'A0001'),
('PAY0021', 'O0021', 96.40, 'Credit Card', 'Paid', 'FAKE-TXN-O0021', '2025-11-22 00:00:00', 'A0001'),
('PAY0022', 'O0022', 121.60, 'Debit Card', 'Paid', 'FAKE-TXN-O0022', '2025-11-23 00:00:00', 'A0001'),
('PAY0023', 'O0023', 69.10, 'E-Wallet', 'Paid', 'FAKE-TXN-O0023', '2025-11-24 00:00:00', 'A0001'),
('PAY0024', 'O0024', 141.20, 'Credit Card', 'Paid', 'FAKE-TXN-O0024', '2025-11-25 00:00:00', 'A0001'),
('PAY0025', 'O0025', 58.30, 'Debit Card', 'Paid', 'FAKE-TXN-O0025', '2025-11-26 00:00:00', 'A0001');

-- --------------------------------------------------------

--
-- 表的结构 `order_refund`
--

CREATE TABLE `order_refund` (
  `refund_id` varchar(64) NOT NULL,
  `order_id` varchar(64) NOT NULL,
  `member_id` varchar(64) NOT NULL,
  `reason` text DEFAULT NULL,
  `evidence` varchar(255) DEFAULT NULL,
  `status` varchar(32) DEFAULT 'Pending',
  `admin_note` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL,
  `admin_id` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order_refund`
--

INSERT INTO `order_refund` (`refund_id`, `order_id`, `member_id`, `reason`, `evidence`, `status`, `admin_note`, `created_at`, `processed_at`, `admin_id`) VALUES
('R0001', 'O0059', 'M0031', 'not good , i think i deserve better', NULL, 'Rejected', NULL, '2025-12-26 16:56:22', '2025-12-26 16:59:13', 'A0001'),
('R0002', 'O0059', 'M0031', 'hahahahahahahahahaahahaa', NULL, 'Rejected', NULL, '2025-12-26 16:58:42', '2025-12-26 16:59:14', 'A0001'),
('R0003', 'O0057', 'M0031', 'walao ei walao ei walao ei', NULL, 'Approved', NULL, '2025-12-26 17:07:54', '2025-12-26 17:08:19', 'A0001');

-- --------------------------------------------------------

--
-- 表的结构 `password_reset`
--

CREATE TABLE `password_reset` (
  `reset_id` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expiry` datetime NOT NULL,
  `role` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `password_reset`
--

INSERT INTO `password_reset` (`reset_id`, `email`, `token`, `expiry`, `role`) VALUES
('R000699243', 'hacknews0123@gmail.com', '901555', '2025-12-16 16:37:10', 'member');

-- --------------------------------------------------------

--
-- 表的结构 `product`
--

CREATE TABLE `product` (
  `product_id` varchar(10) NOT NULL,
  `category_id` varchar(10) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `created_by` varchar(10) DEFAULT NULL,
  `updated_by` varchar(10) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product`
--

INSERT INTO `product` (`product_id`, `category_id`, `name`, `description`, `price`, `stock`, `created_by`, `updated_by`, `created_at`, `is_hidden`) VALUES
('P0001', 'C0001', 'Atomic Habits', 'A habit-building guide to improve daily life and behavior.', 38.90, 24, 'A0001', 'A0001', '2025-12-06 19:58:31', 0),
('P0002', 'C0001', 'The Psychology of Money', 'Learn how people think, behave and succeed with money.', 42.50, 20, 'A0001', 'A0001', '2025-12-06 20:38:00', 0),
('P0003', 'C0001', 'Rich Dad Poor Dad', 'Personal finance classic book about assets vs liabilities.', 33.90, 30, 'A0001', NULL, '2025-12-06 20:38:37', 0),
('P0004', 'C0001', 'Harry Potter and The Philosopher Stone', 'Fantasy adventure for young readers.', 29.50, 15, 'A0001', NULL, '2025-12-06 20:39:11', 0),
('P0005', 'C0001', 'Deep Work', 'Productivity technique for focused work and learning.', 39.90, 22, 'A0001', 'A0001', '2025-12-06 20:39:38', 0),
('P0006', 'C0002', 'Stapler No.10', 'Compact mini stapler for student use.', 4.90, 80, 'A0001', NULL, '2025-12-06 20:40:29', 0),
('P0007', 'C0002', 'UHU Glue Stick 8g', 'Strong adhesion glue stick for papers and crafts.', 3.50, 120, 'A0001', 'A0001', '2025-12-06 20:40:53', 0),
('P0008', 'C0002', 'Correction Tape 30m', 'Smooth writing, no smudge correction tape.', 4.20, 75, 'A0001', NULL, '2025-12-06 20:41:27', 0),
('P0009', 'C0002', 'A4 Transparent File', 'Clear file for document organization.', 1.80, 200, 'A0001', NULL, '2025-12-06 20:41:49', 0),
('P0010', 'C0002', 'Paper Clips (100pcs)', 'Colorful coated clips for paper bundling.', 2.50, 140, 'A0001', NULL, '2025-12-06 20:42:21', 0),
('P0011', 'C0003', 'Campus A5 Lined Notebook', '80gsm high-quality pages, smooth writing.', 6.90, 100, 'A0001', NULL, '2025-12-06 20:43:14', 0),
('P0012', 'C0003', 'Spiral Kraft Notebook A5', 'Eco-style kraft cover notebook 150 pages.', 5.50, 65, 'A0001', NULL, '2025-12-06 20:43:36', 0),
('P0013', 'C0003', 'Cute Korean Study Notebook', 'Student-friendly design, aesthetic cover.', 8.00, 45, 'A0001', NULL, '2025-12-06 20:43:52', 0),
('P0014', 'C0003', 'Math Grid Exercise Book', '5mm grid pages for calculations and graphs.', 3.20, 83, 'A0001', 'A0001', '2025-12-06 20:44:11', 0),
('P0015', 'C0003', 'Bullet Journal Dotted', 'Dotted journal for aesthetic planners.', 12.90, 35, 'A0001', NULL, '2025-12-06 20:44:28', 0),
('P0016', 'C0004', 'Pilot G2 Gel Pen Black', 'Smooth gel ink writing experience.', 4.90, 110, 'A0001', NULL, '2025-12-06 20:44:51', 0),
('P0017', 'C0004', 'MUJI Gel Pen 0.5mm', 'Minimal clean ink flow, bestseller pen', 4.50, 120, 'A0001', NULL, '2025-12-06 20:45:08', 0),
('P0018', 'C0004', 'Faber Castell 2B Pencil (12pcs)', 'Ideal for drawing/sketching.', 5.80, 90, 'A0001', NULL, '2025-12-06 20:45:26', 0),
('P0019', 'C0004', 'Artline Calligraphy Pen', 'For hand lettering and calligraphy.', 8.90, 58, 'A0001', 'A0001', '2025-12-06 20:45:48', 0),
('P0020', 'C0004', 'Highlighter Pastel Set (6pcs)', 'Cute pastel colours for study notes.', 9.50, 75, 'A0001', NULL, '2025-12-06 20:46:08', 0),
('P0021', 'C0005', 'Watercolor 24 Colors Set', 'High pigment portable watercolor palette.', 18.90, 40, 'A0001', NULL, '2025-12-06 20:46:47', 0),
('P0022', 'C0005', 'Acrylic Paint Set 12pcs', 'Strong colour tone for canvases and craft.', 16.50, 28, 'A0001', 'A0001', '2025-12-06 20:47:07', 0),
('P0023', 'C0005', 'Soft Pastel Chalk 24pcs', 'For art shading and smooth blending effects.', 14.90, 24, 'A0001', 'A0001', '2025-12-06 20:47:23', 0),
('P0024', 'C0005', 'Canvas Board A4', 'Thick textured cotton painting board.', 7.50, 45, 'A0001', NULL, '2025-12-06 20:47:38', 0),
('P0025', 'C0005', 'Brush Set (8 Sizes)', 'Nylon hair brush set for painting.', 9.90, 55, 'A0001', NULL, '2025-12-06 20:49:24', 0);

-- --------------------------------------------------------

--
-- 表的结构 `product_category`
--

CREATE TABLE `product_category` (
  `category_id` varchar(10) NOT NULL,
  `category_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product_category`
--

INSERT INTO `product_category` (`category_id`, `category_name`) VALUES
('C0005', 'Art Supplies'),
('C0001', 'Books'),
('C0003', 'Notebooks'),
('C0004', 'Pens and Writing'),
('C0002', 'Stationery');

-- --------------------------------------------------------

--
-- 表的结构 `product_photo`
--

CREATE TABLE `product_photo` (
  `photo_id` varchar(10) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `filename` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `product_photo`
--

INSERT INTO `product_photo` (`photo_id`, `product_id`, `filename`) VALUES
('PH0001', 'P0001', 'product_P0001_1765022311_1_3756.jpg'),
('PH0002', 'P0002', 'product_P0002_1765024680_0_1901.jpg'),
('PH0003', 'P0003', 'product_P0003_1765089056_0_7327.jpg'),
('PH0004', 'P0004', 'product_P0004_1765089109_0_9693.jpg'),
('PH0005', 'P0005', 'product_P0005_1765089190_0_2971.jpg'),
('PH0006', 'P0006', 'product_P0006_1765089305_0_3172.jpg'),
('PH0008', 'P0008', 'product_P0008_1765089391_1_5600.jpg'),
('PH0009', 'P0007', 'product_P0007_1765089403_0_6757.jpg'),
('PH0010', 'P0009', 'product_P0009_1765089529_0_5356.jpg'),
('PH0011', 'P0009', 'product_P0009_1765089529_1_3586.jpg'),
('PH0012', 'P0010', 'product_P0010_1765089539_0_3318.jpg'),
('PH0013', 'P0010', 'product_P0010_1765089539_1_6913.jpg'),
('PH0014', 'P0011', 'product_P0011_1765090045_0_2642.jpg'),
('PH0015', 'P0012', 'product_P0012_1765090063_0_5803.jpg'),
('PH0016', 'P0012', 'product_P0012_1765090063_1_3829.jpg'),
('PH0017', 'P0013', 'product_P0013_1765090761_0_6501.jpg'),
('PH0018', 'P0013', 'product_P0013_1765090761_1_1349.jpg'),
('PH0019', 'P0014', 'product_P0014_1765090787_0_8798.jpg'),
('PH0020', 'P0016', 'product_P0016_1765090978_0_7638.jpg'),
('PH0021', 'P0017', 'product_P0017_1765090989_0_9345.jpg'),
('PH0022', 'P0018', 'product_P0018_1765091002_0_6125.jpg'),
('PH0023', 'P0019', 'product_P0019_1765091013_0_5713.jpg'),
('PH0024', 'P0020', 'product_P0020_1765091047_0_5568.jpg'),
('PH0025', 'P0021', 'product_P0021_1765091157_0_4411.jpg'),
('PH0026', 'P0021', 'product_P0021_1765091157_1_4334.jpg'),
('PH0027', 'P0022', 'product_P0022_1765091168_0_1886.jpg'),
('PH0028', 'P0023', 'product_P0023_1765091177_0_5338.jpg'),
('PH0029', 'P0023', 'product_P0023_1765091177_1_4663.jpg'),
('PH0030', 'P0024', 'product_P0024_1765091219_0_9312.jpg'),
('PH0031', 'P0025', 'product_P0025_1765091269_0_6667.jpg'),
('PH0032', 'P0015', 'product_P0015_1765168256_0_4798.jpg');

-- --------------------------------------------------------

--
-- 表的结构 `review`
--

CREATE TABLE `review` (
  `review_id` varchar(20) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_reply` text DEFAULT NULL,
  `replied_by` varchar(20) DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `review`
--

INSERT INTO `review` (`review_id`, `product_id`, `member_id`, `rating`, `title`, `content`, `created_at`, `admin_reply`, `replied_by`, `replied_at`) VALUES
('R0001', 'P0001', 'M0001', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-01 16:00:00', 'thank u ', 'A0001', '2025-12-16 14:32:53'),
('R0002', 'P0006', 'M0001', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-01 16:00:00', NULL, NULL, NULL),
('R0003', 'P0014', 'M0001', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-01 16:00:00', NULL, NULL, NULL),
('R0004', 'P0007', 'M0001', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-17 16:00:00', NULL, NULL, NULL),
('R0005', 'P0019', 'M0001', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-17 16:00:00', NULL, NULL, NULL),
('R0009', 'P0016', 'M0002', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-07 16:00:00', NULL, NULL, NULL),
('R0010', 'P0020', 'M0002', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-07 16:00:00', NULL, NULL, NULL),
('R0011', 'P0012', 'M0002', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-07 16:00:00', NULL, NULL, NULL),
('R0012', 'P0002', 'M0003', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-02 16:00:00', NULL, NULL, NULL),
('R0013', 'P0015', 'M0003', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-02 16:00:00', NULL, NULL, NULL),
('R0014', 'P0018', 'M0003', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-02 16:00:00', NULL, NULL, NULL),
('R0015', 'P0013', 'M0003', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-11 16:00:00', NULL, NULL, NULL),
('R0016', 'P0020', 'M0003', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-11 16:00:00', NULL, NULL, NULL),
('R0021', 'P0011', 'M0004', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-03 16:00:00', NULL, NULL, NULL),
('R0022', 'P0017', 'M0004', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-03 16:00:00', NULL, NULL, NULL),
('R0023', 'P0024', 'M0004', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-03 16:00:00', NULL, NULL, NULL),
('R0025', 'P0022', 'M0004', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-24 16:00:00', NULL, NULL, NULL),
('R0026', 'P0019', 'M0004', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-24 16:00:00', NULL, NULL, NULL),
('R0027', 'P0011', 'M0005', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-15 16:00:00', NULL, NULL, NULL),
('R0028', 'P0016', 'M0005', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-15 16:00:00', NULL, NULL, NULL),
('R0030', 'P0017', 'M0005', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-23 16:00:00', NULL, NULL, NULL),
('R0031', 'P0024', 'M0005', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-23 16:00:00', NULL, NULL, NULL),
('R0032', 'P0003', 'M0006', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-04 16:00:00', NULL, NULL, NULL),
('R0033', 'P0022', 'M0006', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-04 16:00:00', NULL, NULL, NULL),
('R0034', 'P0003', 'M0007', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-12 16:00:00', NULL, NULL, NULL),
('R0035', 'P0015', 'M0007', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-12 16:00:00', NULL, NULL, NULL),
('R0036', 'P0007', 'M0008', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-05 16:00:00', NULL, NULL, NULL),
('R0037', 'P0008', 'M0008', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-05 16:00:00', NULL, NULL, NULL),
('R0038', 'P0021', 'M0008', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-05 16:00:00', NULL, NULL, NULL),
('R0041', 'P0006', 'M0008', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-25 16:00:00', 'tq for the comment.', 'A0001', '2025-12-14 17:32:38'),
('R0042', 'P0014', 'M0009', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-13 16:00:00', NULL, NULL, NULL),
('R0043', 'P0021', 'M0009', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-13 16:00:00', NULL, NULL, NULL),
('R0044', 'P0009', 'M0010', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-06 16:00:00', NULL, NULL, NULL),
('R0045', 'P0023', 'M0010', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-06 16:00:00', NULL, NULL, NULL),
('R0046', 'P0025', 'M0010', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-06 16:00:00', NULL, NULL, NULL),
('R0047', 'P0001', 'M0010', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-18 16:00:00', NULL, NULL, NULL),
('R0048', 'P0022', 'M0010', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-18 16:00:00', NULL, NULL, NULL),
('R0049', 'P0004', 'M0011', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-08 16:00:00', NULL, NULL, NULL),
('R0050', 'P0024', 'M0011', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-08 16:00:00', NULL, NULL, NULL),
('R0051', 'P0025', 'M0011', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-08 16:00:00', NULL, NULL, NULL),
('R0052', 'P0002', 'M0012', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-14 16:00:00', NULL, NULL, NULL),
('R0053', 'P0025', 'M0012', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-14 16:00:00', NULL, NULL, NULL),
('R0054', 'P0017', 'M0013', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-16 16:00:00', NULL, NULL, NULL),
('R0055', 'P0024', 'M0013', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-16 16:00:00', NULL, NULL, NULL),
('R0056', 'P0006', 'M0014', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-09 16:00:00', NULL, NULL, NULL),
('R0057', 'P0019', 'M0014', 3, 'Very satisfied', 'The product quality exceeded my expectations. Delivery was fast and packaging was good.', '2025-11-09 16:00:00', NULL, NULL, NULL),
('R0058', 'P0012', 'M0014', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-20 16:00:00', NULL, NULL, NULL),
('R0059', 'P0004', 'M0014', 5, 'Worth the price', 'Decent product for daily use. Price is reasonable and quality is acceptable.', '2025-11-20 16:00:00', NULL, NULL, NULL),
('R0060', 'P0005', 'M0015', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-10 16:00:00', NULL, NULL, NULL),
('R0061', 'P0023', 'M0015', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-10 16:00:00', NULL, NULL, NULL),
('R0062', 'P0021', 'M0015', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-10 16:00:00', NULL, NULL, NULL),
('R0063', 'P0014', 'M0015', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-19 16:00:00', NULL, NULL, NULL),
('R0064', 'P0025', 'M0015', 4, 'Good quality product', 'Product works as described and is good value for money. Would consider buying again.', '2025-11-19 16:00:00', NULL, NULL, NULL),
('R0065', 'P0014', 'M0031', 5, 'test', 'good', '2025-12-16 06:43:55', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `cart_id` varchar(10) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `shopping_cart`
--

INSERT INTO `shopping_cart` (`cart_id`, `member_id`, `product_id`, `quantity`) VALUES
('C0001', 'M0002', 'P0005', 1),
('C0002', 'M0002', 'P0014', 1),
('C0003', 'M0031', 'P0018', 1),
('C0004', 'M0031', 'P0011', 1);

-- --------------------------------------------------------

--
-- 表的结构 `verification_code`
--

CREATE TABLE `verification_code` (
  `verify_id` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `expiry` datetime NOT NULL,
  `is_used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `verification_code`
--

INSERT INTO `verification_code` (`verify_id`, `email`, `code`, `expiry`, `is_used`) VALUES
('V000274338', 'hacknews0123@gmail.com', '448029', '2025-12-14 16:51:54', 1),
('V000606232', 'jktower123456@gmail.com', '427036', '2025-12-16 16:39:12', 1),
('V000774127', 'jktower123456@gmail.com', '397287', '2025-12-16 16:15:38', 1);

--
-- 转储表的索引
--

--
-- 表的索引 `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `favorite`
--
ALTER TABLE `favorite`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `member_id` (`member_id`,`product_id`);

--
-- 表的索引 `member`
--
ALTER TABLE `member`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `order_detail`
--
ALTER TABLE `order_detail`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 表的索引 `order_header`
--
ALTER TABLE `order_header`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- 表的索引 `order_payment`
--
ALTER TABLE `order_payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `created_by` (`created_by`);

--
-- 表的索引 `order_refund`
--
ALTER TABLE `order_refund`
  ADD PRIMARY KEY (`refund_id`);

--
-- 表的索引 `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`reset_id`);

--
-- 表的索引 `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`);

--
-- 表的索引 `product_category`
--
ALTER TABLE `product_category`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- 表的索引 `product_photo`
--
ALTER TABLE `product_photo`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 表的索引 `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_review_member_product` (`member_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_review_replied_by` (`replied_by`);

--
-- 表的索引 `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `uniq_cart_member_product` (`member_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 表的索引 `verification_code`
--
ALTER TABLE `verification_code`
  ADD PRIMARY KEY (`verify_id`);

--
-- 限制导出的表
--

--
-- 限制表 `order_detail`
--
ALTER TABLE `order_detail`
  ADD CONSTRAINT `order_detail_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_header` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_detail_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- 限制表 `order_header`
--
ALTER TABLE `order_header`
  ADD CONSTRAINT `order_header_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`),
  ADD CONSTRAINT `order_header_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `admin` (`admin_id`);

--
-- 限制表 `order_payment`
--
ALTER TABLE `order_payment`
  ADD CONSTRAINT `order_payment_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order_header` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_payment_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`);

--
-- 限制表 `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_category` (`category_id`),
  ADD CONSTRAINT `product_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`),
  ADD CONSTRAINT `product_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `admin` (`admin_id`);

--
-- 限制表 `product_photo`
--
ALTER TABLE `product_photo`
  ADD CONSTRAINT `product_photo_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;

--
-- 限制表 `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `fk_review_replied_by` FOREIGN KEY (`replied_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `review_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE;

--
-- 限制表 `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `member` (`member_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
