-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2025 at 07:12 PM
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
-- Database: `ecommerce_multivendor`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `status`, `shipping_address`, `created_at`) VALUES
(8, 10, 30800.00, 'delivered', 'Basti', '2025-09-14 21:28:38'),
(9, 10, 21299.00, 'delivered', 'Basti', '2025-09-15 20:13:28'),
(10, 14, 98632.00, 'shipped', 'Varanasi', '2025-09-15 20:16:18'),
(11, 14, 1000.00, 'delivered', 'Varanasi', '2025-09-15 20:17:19'),
(12, 14, 17999.00, 'delivered', 'Varanasi', '2025-09-15 20:17:36');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `vendor_id`, `quantity`, `price`) VALUES
(7, 8, 8, 11, 1, 800.00),
(8, 8, 9, 11, 1, 30000.00),
(9, 9, 25, 11, 1, 1299.00),
(10, 9, 22, 12, 1, 20000.00),
(11, 10, 12, 11, 1, 98632.00),
(12, 11, 14, 12, 1, 1000.00),
(13, 12, 20, 13, 1, 17999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `vendor_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `vendor_id`, `name`, `description`, `price`, `stock`, `image`, `category`, `created_at`, `status`) VALUES
(8, 11, 'SOLO S460', 'Bluetooth Wired & Wireless Headphones With Tf Card/Mic/Fm Support', 800.00, 9, '1757885020_68c7325c848d6.jpg', 'Electronics', '2025-09-14 21:23:40', 'active'),
(9, 11, 'Sony Alpha a7', 'Sony Alpha a7 III Mirrorless Camera', 30000.00, 14, '1757885258_68c7334a60d3e.jpg', 'Electronics', '2025-09-14 21:27:38', 'active'),
(10, 11, 'FRONTECH Gaming Keyboard', 'RGB Backlight Effects| 104 Membrane Keys | USB Plug & Play', 600.00, 5, '1757885790_68c7355eb8152.jpg', 'Electronics', '2025-09-14 21:36:30', 'active'),
(12, 11, 'HP Victus', 'HP Victus, AMD Ryzen 9-8945HS', 98632.00, 34, '1757886306_68c73762b1ae6.jpg', 'Electronics', '2025-09-14 21:45:06', 'active'),
(14, 12, 'RO 45 Jersey', 'India Cricket Jersey Test Match 2024 Rohit Sharma 45', 1000.00, 25, '1757963919_68c8668fe8d17.jpg', 'Clothing', '2025-09-15 19:18:39', 'active'),
(15, 12, 'T-Shirt', 'Blazing Wild Relaxed Fit T-Shirt', 1500.00, 29, '1757964440_68c8689811169.png', 'Clothing', '2025-09-15 19:26:57', 'active'),
(16, 12, 'Bewakoof', 'Men\'s Beige All Over Printed T-shirt', 799.00, 53, '1757964596_68c869343894f.png', 'Clothing', '2025-09-15 19:29:56', 'active'),
(17, 12, 'Polo', 'PrimeLine Accent Placket Polo', 1500.00, 32, '1757964820_68c86a14901d5.png', 'Clothing', '2025-09-15 19:33:40', 'active'),
(18, 13, 'Van Gogh', 'Van Gogh By Walker', 1500.00, 41, '1757965075_68c86b13994f8.png', 'Books', '2025-09-15 19:37:55', 'active'),
(19, 13, 'Harry Potter', '1–3 Box Set: A Magical Adventure Begins', 500.00, 5, '1757965284_68c86be41c9ab.png', 'Books', '2025-09-15 19:41:24', 'active'),
(20, 13, 'Wakefit', 'TAURUS Engineered Wood King Box Bed', 17999.00, 19, '1757965635_68c86d432d857.png', 'Home & Garden', '2025-09-15 19:47:15', 'active'),
(21, 13, 'Driver', 'TaylorMade Qi35 MAX Designer Series Silver Driver', 59000.00, 32, '1757965885_68c86e3dda8f5.png', 'Sports', '2025-09-15 19:51:25', 'active'),
(22, 12, 'Bat', 'Ceat Sport Drive Cricket Bat', 20000.00, 44, '1757966023_68c86ec7f0fc8.png', 'Sports', '2025-09-15 19:53:43', 'active'),
(23, 12, 'Hair Straightener', 'Dyson Airstrait Hair Straightener Prussian Blue Rich Copper', 32999.00, 69, '1757966117_68c86f25805a6.png', 'Beauty', '2025-09-15 19:55:17', 'active'),
(24, 12, 'Oggy', 'Simba 109356131 OggyOggy Plush Toy', 1599.00, 35, '1757966326_68c86ff6bed96.png', 'Toys', '2025-09-15 19:58:46', 'active'),
(25, 11, 'Puma Bag', 'School Bag', 1299.00, 24, '1757966489_68c87099e6ea7.png', 'Other', '2025-09-15 20:01:29', 'active'),
(26, 12, 'Sixit', 'Sixit Lite Cricket Tennis Ball (Good bounce)', 400.00, 15, '1758041611_68c9960b05fe0.png', 'Sports', '2025-09-16 16:53:31', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('buyer','vendor','admin') NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `created_at`) VALUES
(1, 'Vivek Singh', 'vivek@123', '$2y$10$JbaXmPG0a8XLsrU61kU3B.RWZefY0wV.a14OKWBOiIJMakGIwotE6', 'admin', NULL, NULL, '2025-08-18 18:58:20'),
(10, 'Priyanshu', 'priyanshu@123', '$2y$10$e2frXBMGJyDz1TL5U2VJAeVpN8Ql95PVTd3jhUGkqRRpBu5.vSjXK', 'buyer', '9638527410', 'Basti', '2025-09-14 21:18:58'),
(11, 'Pradeep', 'pradeep@123', '$2y$10$4pWX0.O1q2gm1JRgNTwqOuf/Fe3XQ.q03dhKBtJIjTyHbSk327iNi', 'vendor', '7418529631', 'Varanasi', '2025-09-14 21:20:22'),
(12, 'Rajat', 'rajat@123', '$2y$10$8Reytqc21MmQr/OWwCqJZuCEuFkY7A0Ej/kQ6i1DxHg96/KfdfiTm', 'vendor', '7412589641', 'Gorakhpur', '2025-09-15 19:13:04'),
(13, 'Aditya', 'aditya@123', '$2y$10$ZOSTpb4RgKQJvJGGtBoQw.NwSaVtmbG487dBWlj2t6TvjUFmV89h2', 'vendor', '7412589456', 'Lakhimpur', '2025-09-15 19:35:18'),
(14, 'Akshansh', 'akshansh@123', '$2y$10$95GguFZvN0.jg7yLGShSvOlM8A1/ilkyzOSagfxOo9pW4KZah1zUO', 'buyer', '8954612367', 'Varanasi', '2025-09-15 20:15:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`vendor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
