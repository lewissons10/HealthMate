-- Food Database Schema for HealthMate
-- This file contains the complete food database structure and data

USE healthmate_db;

-- Create foods table
CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_key VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    calories_per_100g DECIMAL(8,2) NOT NULL,
    protein DECIMAL(6,2) NOT NULL,
    carbs DECIMAL(6,2) NOT NULL,
    fats DECIMAL(6,2) NOT NULL,
    fiber DECIMAL(6,2) DEFAULT 0,
    sugar DECIMAL(6,2) DEFAULT 0,
    unit VARCHAR(20) NOT NULL DEFAULT 'g',
    category VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_name (name),
    INDEX idx_food_key (food_key)
);

-- Create food_vitamins table
CREATE TABLE IF NOT EXISTS food_vitamins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT NOT NULL,
    vitamin_name VARCHAR(100) NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'mg',
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    INDEX idx_food_id (food_id),
    INDEX idx_vitamin (vitamin_name)
);

-- Create food_minerals table
CREATE TABLE IF NOT EXISTS food_minerals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT NOT NULL,
    mineral_name VARCHAR(100) NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    unit VARCHAR(20) DEFAULT 'mg',
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    INDEX idx_food_id (food_id),
    INDEX idx_mineral (mineral_name)
);

-- Insert food data
INSERT INTO foods (food_key, name, calories_per_100g, protein, carbs, fats, fiber, sugar, unit, category) VALUES
-- Proteins
('chicken_breast', 'Chicken Breast', 165, 31, 0, 3.6, 0, 0, 'g', 'protein'),
('salmon', 'Salmon', 208, 25, 0, 12, 0, 0, 'g', 'protein'),
('eggs', 'Eggs', 155, 13, 1.1, 11, 0, 1.1, 'g', 'protein'),
('greek_yogurt', 'Greek Yogurt', 59, 10, 3.6, 0.4, 0, 3.6, 'g', 'dairy'),
('cottage_cheese', 'Cottage Cheese', 98, 11, 3.4, 4.3, 0, 3.4, 'g', 'dairy'),
('protein_powder', 'Protein Powder', 370, 80, 5, 3, 0, 2, 'g', 'supplement'),
('tofu', 'Tofu', 76, 8, 1.9, 4.8, 0.3, 0.6, 'g', 'protein'),
('lean_beef', 'Lean Beef', 250, 26, 0, 15, 0, 0, 'g', 'protein'),
('white_fish', 'White Fish', 111, 24, 0, 1.2, 0, 0, 'g', 'protein'),
('lentils', 'Lentils', 116, 9, 20, 0.4, 7.9, 1.8, 'g', 'protein'),
('black_beans', 'Black Beans', 132, 8.9, 24, 0.5, 7.5, 0.3, 'g', 'protein'),
('paneer', 'Paneer', 265, 18, 1.2, 20, 0, 1.2, 'g', 'dairy'),
('turkey_breast', 'Turkey Breast', 135, 30, 0, 1, 0, 0, 'g', 'protein'),

-- Carbohydrates
('brown_rice', 'Brown Rice', 111, 2.6, 23, 0.9, 1.8, 0.4, 'g', 'grain'),
('sweet_potato', 'Sweet Potato', 86, 1.6, 20, 0.1, 3, 4.2, 'g', 'vegetable'),
('quinoa', 'Quinoa', 120, 4.4, 22, 1.9, 2.8, 0.9, 'g', 'grain'),
('oatmeal', 'Oatmeal', 389, 16.9, 66, 6.9, 10.6, 0.99, 'g', 'grain'),
('whole_grain_bread', 'Whole Grain Bread', 247, 13, 41, 4.2, 7, 5.67, 'g', 'grain'),
('banana', 'Banana', 89, 1.1, 23, 0.3, 2.6, 12.2, 'g', 'fruit'),
('pasta', 'Pasta (cooked)', 131, 5, 25, 1.1, 1.8, 0.6, 'g', 'grain'),
('basmati_rice', 'Basmati Rice', 130, 2.7, 28, 0.3, 0.4, 0.1, 'g', 'grain'),
('potatoes', 'Potatoes (boiled)', 87, 1.9, 20.1, 0.1, 1.8, 0.9, 'g', 'vegetable'),

-- Vegetables
('broccoli', 'Broccoli', 34, 2.8, 7, 0.4, 2.6, 1.5, 'g', 'vegetable'),
('spinach', 'Spinach', 23, 2.9, 3.6, 0.4, 2.2, 0.4, 'g', 'vegetable'),
('bell_peppers', 'Bell Peppers', 31, 1, 7.3, 0.3, 2.5, 4.2, 'g', 'vegetable'),
('tomatoes', 'Tomatoes', 18, 0.9, 3.9, 0.2, 1.2, 2.6, 'g', 'vegetable'),
('carrots', 'Carrots', 41, 0.9, 9.6, 0.2, 2.8, 4.7, 'g', 'vegetable'),
('mixed_greens', 'Mixed Greens', 20, 2, 4, 0.2, 2, 1, 'g', 'vegetable'),
('arugula', 'Arugula', 25, 2.6, 3.7, 0.7, 1.6, 2.1, 'g', 'vegetable'),
('kale', 'Kale', 49, 4.3, 8.8, 0.9, 3.6, 2.3, 'g', 'vegetable'),
('cucumber', 'Cucumber', 16, 0.7, 4, 0.1, 0.5, 1.7, 'g', 'vegetable'),
('asparagus', 'Asparagus', 20, 2.2, 3.9, 0.1, 2.1, 1.9, 'g', 'vegetable'),
('zucchini', 'Zucchini', 17, 1.2, 3.4, 0.2, 1, 2.5, 'g', 'vegetable'),
('onion', 'Onion', 40, 1.1, 9.3, 0.1, 1.7, 4.2, 'g', 'vegetable'),
('green_beans', 'Green Beans', 31, 1.8, 7, 0.1, 2.7, 3.3, 'g', 'vegetable'),
('mushrooms', 'Mushrooms', 22, 3.1, 3.3, 0.3, 1, 2, 'g', 'vegetable'),
('cherry_tomatoes', 'Cherry Tomatoes', 18, 0.9, 3.9, 0.2, 1.2, 2.6, 'g', 'vegetable'),
('peas', 'Peas', 81, 5.4, 14, 0.4, 5.1, 5.7, 'g', 'vegetable'),

-- Fruits
('apple', 'Apple', 52, 0.3, 14, 0.2, 2.4, 10.4, 'g', 'fruit'),
('avocado', 'Avocado', 160, 2, 8.5, 14.7, 6.7, 0.7, 'g', 'fruit'),
('mixed_berries', 'Mixed Berries', 57, 0.7, 14, 0.3, 2.4, 10, 'g', 'fruit'),
('orange', 'Orange', 47, 0.9, 12, 0.1, 2.4, 9.4, 'g', 'fruit'),

-- Nuts & Seeds
('almonds', 'Almonds', 579, 21.2, 21.6, 49.9, 12.5, 4.4, 'g', 'nuts'),
('walnuts', 'Walnuts', 654, 15.2, 13.7, 65.2, 6.7, 2.6, 'g', 'nuts'),
('cashews', 'Cashews', 553, 18.2, 30.2, 43.8, 3.3, 5.9, 'g', 'nuts'),
('pistachios', 'Pistachios', 560, 20.2, 27.2, 45.3, 10.6, 7.7, 'g', 'nuts'),
('chia_seeds', 'Chia Seeds', 486, 16.5, 42, 30.7, 34.4, 0, 'g', 'nuts'),
('flax_seeds', 'Flax Seeds', 534, 18.3, 28.9, 42.2, 27.3, 1.6, 'g', 'nuts'),

-- Fats & Oils
('olive_oil', 'Olive Oil', 884, 0, 0, 100, 0, 0, 'ml', 'fat'),
('coconut_oil', 'Coconut Oil', 862, 0, 0, 100, 0, 0, 'ml', 'fat'),
('butter', 'Butter', 717, 0.9, 0.1, 81, 0, 0.1, 'g', 'fat'),
('sesame_oil', 'Sesame Oil', 884, 0, 0, 100, 0, 0, 'ml', 'fat'),

-- Dairy
('milk', 'Milk', 42, 3.4, 5, 1, 0, 5, 'ml', 'dairy'),
('cheese', 'Cheddar Cheese', 403, 25, 1.3, 33, 0, 0.5, 'g', 'dairy'),
('hard_boiled_eggs', 'Hard Boiled Eggs', 155, 13, 1.1, 11, 0, 1.1, 'g', 'protein'),
('egg_whites', 'Egg Whites', 52, 11, 0.7, 0.2, 0, 0.7, 'g', 'protein'),

-- Breads & Wraps
('whole_grain_tortilla', 'Whole Grain Tortilla', 218, 6, 36, 4, 3, 1, 'g', 'grain'),
('lettuce_wrap', 'Lettuce Wrap', 5, 0.5, 1, 0.1, 0.5, 0.5, 'g', 'vegetable'),
('pita_bread', 'Pita Bread', 275, 9, 56, 1.2, 2.2, 1.3, 'g', 'grain'),

-- Supplements & Others
('protein_bar', 'Protein Bar', 200, 20, 15, 6, 2, 5, 'g', 'supplement'),
('tuna', 'Tuna', 132, 30, 0, 1, 0, 0, 'g', 'protein');

-- Insert vitamin data
INSERT INTO food_vitamins (food_id, vitamin_name, amount, unit) VALUES
-- Chicken Breast vitamins
((SELECT id FROM foods WHERE food_key = 'chicken_breast'), 'Niacin', 14.8, 'mg'),
((SELECT id FROM foods WHERE food_key = 'chicken_breast'), 'Vitamin B6', 1.0, 'mg'),
((SELECT id FROM foods WHERE food_key = 'chicken_breast'), 'Vitamin B12', 0.3, 'mcg'),

-- Salmon vitamins
((SELECT id FROM foods WHERE food_key = 'salmon'), 'Vitamin D', 11, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'salmon'), 'Vitamin B12', 3.2, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'salmon'), 'Niacin', 8.5, 'mg'),

-- Eggs vitamins
((SELECT id FROM foods WHERE food_key = 'eggs'), 'Vitamin B12', 0.6, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'eggs'), 'Vitamin A', 140, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'eggs'), 'Vitamin D', 1.1, 'mcg'),

-- Broccoli vitamins
((SELECT id FROM foods WHERE food_key = 'broccoli'), 'Vitamin C', 89.2, 'mg'),
((SELECT id FROM foods WHERE food_key = 'broccoli'), 'Vitamin K', 101.6, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'broccoli'), 'Folate', 63, 'mcg'),

-- Spinach vitamins
((SELECT id FROM foods WHERE food_key = 'spinach'), 'Vitamin K', 483, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'spinach'), 'Vitamin A', 469, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'spinach'), 'Folate', 194, 'mcg'),

-- Avocado vitamins
((SELECT id FROM foods WHERE food_key = 'avocado'), 'Vitamin K', 21, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'avocado'), 'Folate', 81, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'avocado'), 'Vitamin C', 10, 'mg'),

-- Almonds vitamins
((SELECT id FROM foods WHERE food_key = 'almonds'), 'Vitamin E', 25.6, 'mg'),
((SELECT id FROM foods WHERE food_key = 'almonds'), 'Riboflavin', 1.1, 'mg'),
((SELECT id FROM foods WHERE food_key = 'almonds'), 'Niacin', 3.6, 'mg'),

-- Olive Oil vitamins
((SELECT id FROM foods WHERE food_key = 'olive_oil'), 'Vitamin E', 14.4, 'mg'),
((SELECT id FROM foods WHERE food_key = 'olive_oil'), 'Vitamin K', 60.2, 'mcg'),

-- Cheese vitamins
((SELECT id FROM foods WHERE food_key = 'cheese'), 'Vitamin B12', 0.8, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'cheese'), 'Vitamin A', 1052, 'IU'),

-- Sweet Potato vitamins
((SELECT id FROM foods WHERE food_key = 'sweet_potato'), 'Vitamin A', 14187, 'IU'),
((SELECT id FROM foods WHERE food_key = 'sweet_potato'), 'Vitamin C', 2.4, 'mg'),

-- Bell Peppers vitamins
((SELECT id FROM foods WHERE food_key = 'bell_peppers'), 'Vitamin C', 80.4, 'mg'),
((SELECT id FROM foods WHERE food_key = 'bell_peppers'), 'Vitamin A', 157, 'mcg'),

-- Tomatoes vitamins
((SELECT id FROM foods WHERE food_key = 'tomatoes'), 'Vitamin C', 13.7, 'mg'),
((SELECT id FROM foods WHERE food_key = 'tomatoes'), 'Vitamin K', 7.9, 'mcg'),

-- Carrots vitamins
((SELECT id FROM foods WHERE food_key = 'carrots'), 'Vitamin A', 16706, 'IU'),
((SELECT id FROM foods WHERE food_key = 'carrots'), 'Vitamin K', 13.2, 'mcg'),

-- Mixed Greens vitamins
((SELECT id FROM foods WHERE food_key = 'mixed_greens'), 'Vitamin K', 24.8, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'mixed_greens'), 'Vitamin C', 40, 'mg'),

-- Sesame Oil vitamins
((SELECT id FROM foods WHERE food_key = 'sesame_oil'), 'Vitamin E', 1.4, 'mg'),
((SELECT id FROM foods WHERE food_key = 'sesame_oil'), 'Vitamin K', 13.6, 'mcg'),

-- Turkey Breast vitamins
((SELECT id FROM foods WHERE food_key = 'turkey_breast'), 'Niacin', 8.1, 'mg'),
((SELECT id FROM foods WHERE food_key = 'turkey_breast'), 'Vitamin B6', 0.8, 'mg'),

-- Potatoes vitamins
((SELECT id FROM foods WHERE food_key = 'potatoes'), 'Vitamin C', 13, 'mg'),
((SELECT id FROM foods WHERE food_key = 'potatoes'), 'Vitamin B6', 0.2, 'mg');

-- Insert mineral data
INSERT INTO food_minerals (food_id, mineral_name, amount, unit) VALUES
-- Chicken Breast minerals
((SELECT id FROM foods WHERE food_key = 'chicken_breast'), 'Selenium', 27.4, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'chicken_breast'), 'Phosphorus', 228, 'mg'),

-- Salmon minerals
((SELECT id FROM foods WHERE food_key = 'salmon'), 'Selenium', 36.5, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'salmon'), 'Phosphorus', 252, 'mg'),

-- Eggs minerals
((SELECT id FROM foods WHERE food_key = 'eggs'), 'Selenium', 30.7, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'eggs'), 'Phosphorus', 198, 'mg'),

-- Broccoli minerals
((SELECT id FROM foods WHERE food_key = 'broccoli'), 'Potassium', 316, 'mg'),
((SELECT id FROM foods WHERE food_key = 'broccoli'), 'Manganese', 0.2, 'mg'),

-- Spinach minerals
((SELECT id FROM foods WHERE food_key = 'spinach'), 'Iron', 2.7, 'mg'),
((SELECT id FROM foods WHERE food_key = 'spinach'), 'Magnesium', 79, 'mg'),

-- Avocado minerals
((SELECT id FROM foods WHERE food_key = 'avocado'), 'Potassium', 485, 'mg'),
((SELECT id FROM foods WHERE food_key = 'avocado'), 'Manganese', 0.1, 'mg'),

-- Almonds minerals
((SELECT id FROM foods WHERE food_key = 'almonds'), 'Magnesium', 270, 'mg'),
((SELECT id FROM foods WHERE food_key = 'almonds'), 'Manganese', 2.3, 'mg'),

-- Olive Oil minerals
((SELECT id FROM foods WHERE food_key = 'olive_oil'), 'Iron', 0.6, 'mg'),
((SELECT id FROM foods WHERE food_key = 'olive_oil'), 'Sodium', 2, 'mg'),

-- Cheese minerals
((SELECT id FROM foods WHERE food_key = 'cheese'), 'Calcium', 721, 'mg'),
((SELECT id FROM foods WHERE food_key = 'cheese'), 'Phosphorus', 512, 'mg'),

-- Sweet Potato minerals
((SELECT id FROM foods WHERE food_key = 'sweet_potato'), 'Potassium', 337, 'mg'),
((SELECT id FROM foods WHERE food_key = 'sweet_potato'), 'Manganese', 0.3, 'mg'),

-- Bell Peppers minerals
((SELECT id FROM foods WHERE food_key = 'bell_peppers'), 'Potassium', 211, 'mg'),
((SELECT id FROM foods WHERE food_key = 'bell_peppers'), 'Manganese', 0.1, 'mg'),

-- Tomatoes minerals
((SELECT id FROM foods WHERE food_key = 'tomatoes'), 'Potassium', 237, 'mg'),
((SELECT id FROM foods WHERE food_key = 'tomatoes'), 'Manganese', 0.1, 'mg'),

-- Carrots minerals
((SELECT id FROM foods WHERE food_key = 'carrots'), 'Potassium', 320, 'mg'),
((SELECT id FROM foods WHERE food_key = 'carrots'), 'Manganese', 0.1, 'mg'),

-- Mixed Greens minerals
((SELECT id FROM foods WHERE food_key = 'mixed_greens'), 'Manganese', 0.4, 'mg'),
((SELECT id FROM foods WHERE food_key = 'mixed_greens'), 'Phosphorus', 108, 'mg'),

-- Sesame Oil minerals
((SELECT id FROM foods WHERE food_key = 'sesame_oil'), 'Iron', 0.1, 'mg'),
((SELECT id FROM foods WHERE food_key = 'sesame_oil'), 'Sodium', 0, 'mg'),

-- Turkey Breast minerals
((SELECT id FROM foods WHERE food_key = 'turkey_breast'), 'Selenium', 24.5, 'mcg'),
((SELECT id FROM foods WHERE food_key = 'turkey_breast'), 'Phosphorus', 223, 'mg'),

-- Potatoes minerals
((SELECT id FROM foods WHERE food_key = 'potatoes'), 'Potassium', 379, 'mg'),
((SELECT id FROM foods WHERE food_key = 'potatoes'), 'Manganese', 0.1, 'mg');

-- Create indexes for better performance
CREATE INDEX idx_foods_calories ON foods(calories_per_100g);
CREATE INDEX idx_foods_protein ON foods(protein);
CREATE INDEX idx_foods_carbs ON foods(carbs);
CREATE INDEX idx_foods_fats ON foods(fats);

-- Create view for easy food search
CREATE OR REPLACE VIEW food_search_view AS
SELECT 
    f.id,
    f.food_key,
    f.name,
    f.calories_per_100g,
    f.protein,
    f.carbs,
    f.fats,
    f.fiber,
    f.sugar,
    f.unit,
    f.category,
    GROUP_CONCAT(DISTINCT CONCAT(fv.vitamin_name, ':', fv.amount, fv.unit) SEPARATOR '|') as vitamins,
    GROUP_CONCAT(DISTINCT CONCAT(fm.mineral_name, ':', fm.amount, fm.unit) SEPARATOR '|') as minerals
FROM foods f
LEFT JOIN food_vitamins fv ON f.id = fv.food_id
LEFT JOIN food_minerals fm ON f.id = fm.food_id
GROUP BY f.id, f.food_key, f.name, f.calories_per_100g, f.protein, f.carbs, f.fats, f.fiber, f.sugar, f.unit, f.category;

-- Sample queries for testing
-- Get all foods by category
-- SELECT * FROM foods WHERE category = 'protein';

-- Search foods by name
-- SELECT * FROM foods WHERE name LIKE '%chicken%';

-- Get food with vitamins and minerals
-- SELECT * FROM food_search_view WHERE food_key = 'chicken_breast';

-- Get foods by calorie range
-- SELECT * FROM foods WHERE calories_per_100g BETWEEN 100 AND 200;

-- Get high protein foods
-- SELECT * FROM foods WHERE protein > 20 ORDER BY protein DESC;
