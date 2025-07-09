-- Bestellando Database Schema
-- Version 1.0

-- Disable foreign key checks temporarily for clean setup
SET FOREIGN_KEY_CHECKS=0;

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','waiter','kitchen_staff') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--
-- TODO: Add sample admin user for initial login if needed, or handle first user creation securely.
-- Example:
-- INSERT INTO `users` (`username`, `password_hash`, `role`) VALUES
-- ('admin', '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', 'admin'); -- Replace with a real hash


--
-- Table structure for table `restaurant_tables`
--
DROP TABLE IF EXISTS `restaurant_tables`;
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` varchar(10) NOT NULL,
  `capacity` int(4) DEFAULT NULL,
  `status` enum('available','occupied','reserved') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `table_number` (`table_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `restaurant_tables`
--
INSERT INTO `restaurant_tables` (`table_number`, `capacity`, `status`) VALUES
('1', 4, 'available'),
('2', 4, 'available'),
('3', 2, 'available'),
('4', 6, 'available'),
('5', 2, 'available'),
('P1', 8, 'available'), -- Patio Table
('B1', 2, 'available'); -- Bar Seat

--
-- Table structure for table `categories`
--
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--
INSERT INTO `categories` (`name`, `description`) VALUES
('Appetizers', 'Starters to whet your appetite'),
('Main Courses', 'Delicious main dishes'),
('Desserts', 'Sweet treats to finish your meal'),
('Beverages', 'Drinks and refreshments'),
('Salads', 'Fresh and healthy salads'),
('Soups', 'Warm and comforting soups');

--
-- Table structure for table `menu_items`
--
DROP TABLE IF EXISTS `menu_items`;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `is_available`) VALUES
((SELECT id from categories WHERE name = 'Appetizers'), 'Bruschetta', 'Grilled bread rubbed with garlic and topped with olive oil and salt. Variations may include toppings of tomato, vegetables, beans, cured meat, or cheese.', 8.50, 1),
((SELECT id from categories WHERE name = 'Appetizers'), 'Spring Rolls', 'Crispy fried spring rolls with vegetable filling.', 7.00, 1),
((SELECT id from categories WHERE name = 'Main Courses'), 'Spaghetti Carbonara', 'Pasta with eggs, cheese, pancetta, and black pepper.', 15.00, 1),
((SELECT id from categories WHERE name = 'Main Courses'), 'Grilled Salmon', 'Fresh salmon fillet grilled to perfection, served with seasonal vegetables.', 19.90, 1),
((SELECT id from categories WHERE name = 'Main Courses'), 'Margherita Pizza', 'Classic pizza with tomatoes, mozzarella, basil, salt, and olive oil.', 12.00, 1),
((SELECT id from categories WHERE name = 'Desserts'), 'Tiramisu', 'Coffee-flavoured Italian dessert.', 7.50, 1),
((SELECT id from categories WHERE name = 'Desserts'), 'Chocolate Lava Cake', 'Warm chocolate cake with a gooey molten center.', 8.00, 1),
((SELECT id from categories WHERE name = 'Beverages'), 'Coca-Cola', 'Classic Coke.', 2.50, 1),
((SELECT id from categories WHERE name = 'Beverages'), 'Orange Juice', 'Freshly squeezed orange juice.', 3.50, 1),
((SELECT id from categories WHERE name = 'Salads'), 'Caesar Salad', 'Romaine lettuce and croutons dressed with Parmesan cheese, lemon juice, olive oil, egg, Worcestershire sauce, garlic, and black pepper.', 10.50, 1);


--
-- Table structure for table `orders`
--
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL, -- Waiter who took the order
  `status` enum('new','in_progress','ready_for_pickup','completed','cancelled','billing') NOT NULL DEFAULT 'new',
  `total_amount` decimal(10,2) DEFAULT 0.00, -- Can be calculated or stored
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `table_id` (`table_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`),
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Table structure for table `order_items`
--
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(4) NOT NULL DEFAULT 1,
  `price_at_order` decimal(10,2) NOT NULL, -- Price of the item when ordered
  `notes` text DEFAULT NULL, -- Item specific notes
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) -- Consider ON DELETE RESTRICT or SET NULL if items shouldn't be deleted if ordered
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- Note:
-- `order_item_notes` from the plan was integrated into `order_items.notes` for simplicity.
-- If more complex note tracking is needed (e.g., notes by different users, timestamps for notes),
-- a separate `order_item_notes` table could be reinstated.
--
-- For `users` table, a secure method for creating the first admin user (e.g., via a command-line script or a setup page)
-- is recommended instead of a hardcoded password hash in this schema file.
-- The example provided is illustrative.
--
-- `orders.total_amount` can be calculated on-the-fly from `order_items` or stored for performance.
-- If stored, it needs to be updated whenever order items change.
--
-- Consider adding an `is_paid` (boolean) and `paid_at` (timestamp) to the `orders` table for bill management.
-- Current status 'completed' might imply paid, but 'billing' status is added for the process.
-- The admin marking a bill as 'Paid' would likely transition an order from 'billing' or 'completed' to a more definitive 'paid' state or update these fields.
-- For simplicity, the current enum covers basic states. This can be expanded.
