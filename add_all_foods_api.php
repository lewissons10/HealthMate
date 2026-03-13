<?php
// Add all foods from breakfast, lunch, dinner, and snacks to the database using API

// Comprehensive food database with nutritional information
$allFoods = [
    // BREAKFAST FOODS
    'eggs' => [
        'name' => 'Eggs',
        'category' => 'protein',
        'calories_per_100g' => 155,
        'protein' => 13,
        'carbs' => 1.1,
        'fats' => 11,
        'fiber' => 0,
        'sugar' => 1.1,
        'vitamins' => ['Vitamin A' => 140, 'Vitamin D' => 2, 'Vitamin B12' => 1.1],
        'minerals' => ['Iron' => 1.2, 'Selenium' => 30.7, 'Phosphorus' => 198]
    ],
    'greek_yogurt' => [
        'name' => 'Greek Yogurt',
        'category' => 'dairy',
        'calories_per_100g' => 59,
        'protein' => 10,
        'carbs' => 3.6,
        'fats' => 0.4,
        'fiber' => 0,
        'sugar' => 3.6,
        'vitamins' => ['Vitamin B12' => 0.5, 'Riboflavin' => 0.2],
        'minerals' => ['Calcium' => 110, 'Phosphorus' => 135]
    ],
    'protein_powder' => [
        'name' => 'Protein Powder',
        'category' => 'supplement',
        'calories_per_100g' => 400,
        'protein' => 80,
        'carbs' => 5,
        'fats' => 3,
        'fiber' => 0,
        'sugar' => 2,
        'vitamins' => [],
        'minerals' => []
    ],
    'cottage_cheese' => [
        'name' => 'Cottage Cheese',
        'category' => 'dairy',
        'calories_per_100g' => 98,
        'protein' => 11,
        'carbs' => 3.4,
        'fats' => 4.3,
        'fiber' => 0,
        'sugar' => 3.4,
        'vitamins' => ['Vitamin B12' => 0.4],
        'minerals' => ['Calcium' => 83, 'Sodium' => 364]
    ],
    'oatmeal' => [
        'name' => 'Oatmeal',
        'category' => 'grain',
        'calories_per_100g' => 389,
        'protein' => 16.9,
        'carbs' => 66,
        'fats' => 6.9,
        'fiber' => 10.6,
        'sugar' => 0.99,
        'vitamins' => ['Thiamine' => 0.8, 'Folate' => 56],
        'minerals' => ['Manganese' => 4.9, 'Phosphorus' => 523, 'Magnesium' => 177]
    ],
    'whole_grain_bread' => [
        'name' => 'Whole Grain Bread',
        'category' => 'grain',
        'calories_per_100g' => 247,
        'protein' => 13.4,
        'carbs' => 41.3,
        'fats' => 4.2,
        'fiber' => 7,
        'sugar' => 4.3,
        'vitamins' => ['Thiamine' => 0.3, 'Folate' => 40],
        'minerals' => ['Iron' => 2.5, 'Magnesium' => 82]
    ],
    'banana' => [
        'name' => 'Banana',
        'category' => 'fruit',
        'calories_per_100g' => 89,
        'protein' => 1.1,
        'carbs' => 22.8,
        'fats' => 0.3,
        'fiber' => 2.6,
        'sugar' => 12.2,
        'vitamins' => ['Vitamin C' => 8.7, 'Vitamin B6' => 0.4],
        'minerals' => ['Potassium' => 358, 'Manganese' => 0.3]
    ],
    'quinoa' => [
        'name' => 'Quinoa',
        'category' => 'grain',
        'calories_per_100g' => 120,
        'protein' => 4.4,
        'carbs' => 22,
        'fats' => 1.9,
        'fiber' => 2.8,
        'sugar' => 0.9,
        'vitamins' => ['Folate' => 42],
        'minerals' => ['Iron' => 1.5, 'Magnesium' => 64, 'Phosphorus' => 152]
    ],
    'avocado' => [
        'name' => 'Avocado',
        'category' => 'fruit',
        'calories_per_100g' => 160,
        'protein' => 2,
        'carbs' => 8.5,
        'fats' => 14.7,
        'fiber' => 6.7,
        'sugar' => 0.7,
        'vitamins' => ['Vitamin K' => 21, 'Folate' => 81],
        'minerals' => ['Potassium' => 485, 'Magnesium' => 29]
    ],
    'almonds' => [
        'name' => 'Almonds',
        'category' => 'nuts',
        'calories_per_100g' => 579,
        'protein' => 21.2,
        'carbs' => 21.6,
        'fats' => 49.9,
        'fiber' => 12.5,
        'sugar' => 4.4,
        'vitamins' => ['Vitamin E' => 25.6, 'Riboflavin' => 1.1],
        'minerals' => ['Magnesium' => 270, 'Calcium' => 269, 'Iron' => 3.7]
    ],
    'chia_seeds' => [
        'name' => 'Chia Seeds',
        'category' => 'seeds',
        'calories_per_100g' => 486,
        'protein' => 16.5,
        'carbs' => 42.1,
        'fats' => 30.7,
        'fiber' => 34.4,
        'sugar' => 0,
        'vitamins' => [],
        'minerals' => ['Calcium' => 631, 'Phosphorus' => 860, 'Magnesium' => 335]
    ],
    'peanut_butter' => [
        'name' => 'Peanut Butter',
        'category' => 'nuts',
        'calories_per_100g' => 588,
        'protein' => 25.1,
        'carbs' => 20.1,
        'fats' => 50.3,
        'fiber' => 8.5,
        'sugar' => 9.2,
        'vitamins' => ['Niacin' => 13.4, 'Vitamin E' => 8.3],
        'minerals' => ['Magnesium' => 168, 'Phosphorus' => 335]
    ],
    
    // LUNCH FOODS
    'chicken_breast' => [
        'name' => 'Chicken Breast',
        'category' => 'protein',
        'calories_per_100g' => 165,
        'protein' => 31,
        'carbs' => 0,
        'fats' => 3.6,
        'fiber' => 0,
        'sugar' => 0,
        'vitamins' => ['Niacin' => 14.8, 'Vitamin B6' => 1],
        'minerals' => ['Selenium' => 27.4, 'Phosphorus' => 228]
    ],
    'salmon' => [
        'name' => 'Salmon',
        'category' => 'protein',
        'calories_per_100g' => 208,
        'protein' => 25,
        'carbs' => 0,
        'fats' => 12,
        'fiber' => 0,
        'sugar' => 0,
        'vitamins' => ['Vitamin D' => 11, 'Vitamin B12' => 3.2],
        'minerals' => ['Selenium' => 36.5, 'Phosphorus' => 252]
    ],
    'turkey_breast' => [
        'name' => 'Turkey Breast',
        'category' => 'protein',
        'calories_per_100g' => 135,
        'protein' => 30,
        'carbs' => 0,
        'fats' => 1,
        'fiber' => 0,
        'sugar' => 0,
        'vitamins' => ['Niacin' => 10.4, 'Vitamin B6' => 0.8],
        'minerals' => ['Selenium' => 24.3, 'Phosphorus' => 223]
    ],
    'tofu' => [
        'name' => 'Tofu',
        'category' => 'protein',
        'calories_per_100g' => 76,
        'protein' => 8,
        'carbs' => 1.9,
        'fats' => 4.8,
        'fiber' => 0.3,
        'sugar' => 0.6,
        'vitamins' => ['Folate' => 15],
        'minerals' => ['Calcium' => 350, 'Iron' => 5.4, 'Magnesium' => 30]
    ],
    'brown_rice' => [
        'name' => 'Brown Rice',
        'category' => 'grain',
        'calories_per_100g' => 111,
        'protein' => 2.6,
        'carbs' => 23,
        'fats' => 0.9,
        'fiber' => 1.8,
        'sugar' => 0.4,
        'vitamins' => ['Thiamine' => 0.1],
        'minerals' => ['Manganese' => 1.1, 'Magnesium' => 43]
    ],
    'sweet_potato' => [
        'name' => 'Sweet Potato',
        'category' => 'vegetable',
        'calories_per_100g' => 86,
        'protein' => 1.6,
        'carbs' => 20.1,
        'fats' => 0.1,
        'fiber' => 3,
        'sugar' => 4.2,
        'vitamins' => ['Vitamin A' => 14187, 'Vitamin C' => 2.4],
        'minerals' => ['Potassium' => 337, 'Manganese' => 0.3]
    ],
    'whole_grain_pasta' => [
        'name' => 'Whole Grain Pasta',
        'category' => 'grain',
        'calories_per_100g' => 124,
        'protein' => 5,
        'carbs' => 25,
        'fats' => 1.1,
        'fiber' => 3.2,
        'sugar' => 0.6,
        'vitamins' => ['Folate' => 18],
        'minerals' => ['Iron' => 1.4, 'Magnesium' => 32]
    ],
    'broccoli' => [
        'name' => 'Broccoli',
        'category' => 'vegetable',
        'calories_per_100g' => 34,
        'protein' => 2.8,
        'carbs' => 7,
        'fats' => 0.4,
        'fiber' => 2.6,
        'sugar' => 1.5,
        'vitamins' => ['Vitamin C' => 89.2, 'Vitamin K' => 101.6],
        'minerals' => ['Potassium' => 316, 'Folate' => 63]
    ],
    'spinach' => [
        'name' => 'Spinach',
        'category' => 'vegetable',
        'calories_per_100g' => 23,
        'protein' => 2.9,
        'carbs' => 3.6,
        'fats' => 0.4,
        'fiber' => 2.2,
        'sugar' => 0.4,
        'vitamins' => ['Vitamin K' => 483, 'Folate' => 194],
        'minerals' => ['Iron' => 2.7, 'Magnesium' => 79]
    ],
    'bell_peppers' => [
        'name' => 'Bell Peppers',
        'category' => 'vegetable',
        'calories_per_100g' => 31,
        'protein' => 1,
        'carbs' => 7.3,
        'fats' => 0.3,
        'fiber' => 2.5,
        'sugar' => 4.2,
        'vitamins' => ['Vitamin C' => 127.7, 'Vitamin A' => 3131],
        'minerals' => ['Potassium' => 211]
    ],
    'tomatoes' => [
        'name' => 'Tomatoes',
        'category' => 'vegetable',
        'calories_per_100g' => 18,
        'protein' => 0.9,
        'carbs' => 3.9,
        'fats' => 0.2,
        'fiber' => 1.2,
        'sugar' => 2.6,
        'vitamins' => ['Vitamin C' => 13.7, 'Vitamin K' => 7.9],
        'minerals' => ['Potassium' => 237]
    ],
    'olive_oil' => [
        'name' => 'Olive Oil',
        'category' => 'fat',
        'calories_per_100g' => 884,
        'protein' => 0,
        'carbs' => 0,
        'fats' => 100,
        'fiber' => 0,
        'sugar' => 0,
        'vitamins' => ['Vitamin E' => 14.4, 'Vitamin K' => 60.2],
        'minerals' => []
    ],
    'walnuts' => [
        'name' => 'Walnuts',
        'category' => 'nuts',
        'calories_per_100g' => 654,
        'protein' => 15.2,
        'carbs' => 13.7,
        'fats' => 65.2,
        'fiber' => 6.7,
        'sugar' => 2.6,
        'vitamins' => ['Vitamin E' => 0.7],
        'minerals' => ['Manganese' => 3.4, 'Magnesium' => 158]
    ],
    'mixed_greens' => [
        'name' => 'Mixed Greens',
        'category' => 'vegetable',
        'calories_per_100g' => 20,
        'protein' => 2,
        'carbs' => 4,
        'fats' => 0.2,
        'fiber' => 2,
        'sugar' => 1,
        'vitamins' => ['Vitamin K' => 200, 'Folate' => 50],
        'minerals' => ['Iron' => 1.5, 'Potassium' => 200]
    ],
    'arugula' => [
        'name' => 'Arugula',
        'category' => 'vegetable',
        'calories_per_100g' => 25,
        'protein' => 2.6,
        'carbs' => 3.7,
        'fats' => 0.7,
        'fiber' => 1.6,
        'sugar' => 2.1,
        'vitamins' => ['Vitamin K' => 108.6, 'Folate' => 97],
        'minerals' => ['Calcium' => 160, 'Iron' => 1.5]
    ],
    'kale' => [
        'name' => 'Kale',
        'category' => 'vegetable',
        'calories_per_100g' => 49,
        'protein' => 4.3,
        'carbs' => 8.8,
        'fats' => 0.9,
        'fiber' => 3.6,
        'sugar' => 2.3,
        'vitamins' => ['Vitamin K' => 817, 'Vitamin C' => 120],
        'minerals' => ['Calcium' => 150, 'Iron' => 1.5]
    ],
    'cucumber' => [
        'name' => 'Cucumber',
        'category' => 'vegetable',
        'calories_per_100g' => 16,
        'protein' => 0.7,
        'carbs' => 4,
        'fats' => 0.1,
        'fiber' => 0.5,
        'sugar' => 1.7,
        'vitamins' => ['Vitamin K' => 16.4],
        'minerals' => ['Potassium' => 147]
    ],
    'whole_grain_tortilla' => [
        'name' => 'Whole Grain Tortilla',
        'category' => 'grain',
        'calories_per_100g' => 218,
        'protein' => 6,
        'carbs' => 44,
        'fats' => 2.5,
        'fiber' => 6,
        'sugar' => 1,
        'vitamins' => ['Folate' => 20],
        'minerals' => ['Iron' => 2, 'Magnesium' => 50]
    ],
    'lettuce_wrap' => [
        'name' => 'Lettuce Wrap',
        'category' => 'vegetable',
        'calories_per_100g' => 15,
        'protein' => 1.4,
        'carbs' => 2.9,
        'fats' => 0.2,
        'fiber' => 1.3,
        'sugar' => 0.8,
        'vitamins' => ['Vitamin K' => 126.3, 'Folate' => 38],
        'minerals' => ['Iron' => 0.9]
    ],
    'pita_bread' => [
        'name' => 'Pita Bread',
        'category' => 'grain',
        'calories_per_100g' => 275,
        'protein' => 9,
        'carbs' => 56,
        'fats' => 1.2,
        'fiber' => 2.2,
        'sugar' => 0.8,
        'vitamins' => ['Thiamine' => 0.3],
        'minerals' => ['Iron' => 2.6, 'Magnesium' => 26]
    ],
    'lean_beef' => [
        'name' => 'Lean Beef',
        'category' => 'protein',
        'calories_per_100g' => 250,
        'protein' => 26,
        'carbs' => 0,
        'fats' => 17,
        'fiber' => 0,
        'sugar' => 0,
        'vitamins' => ['Vitamin B12' => 2.4, 'Niacin' => 4.5],
        'minerals' => ['Iron' => 2.6, 'Zinc' => 4.3]
    ],
    'white_fish' => [
        'name' => 'White Fish',
        'category' => 'protein',
        'calories_per_100g' => 144,
        'protein' => 26,
        'carbs' => 0,
        'fats' => 3.2,
        'fiber' => 0,
        'sugar' => 0,
        'vitamins' => ['Vitamin B12' => 2.1],
        'minerals' => ['Selenium' => 36.5, 'Phosphorus' => 256]
    ],
    'lentils' => [
        'name' => 'Lentils',
        'category' => 'legume',
        'calories_per_100g' => 116,
        'protein' => 9,
        'carbs' => 20,
        'fats' => 0.4,
        'fiber' => 7.9,
        'sugar' => 1.8,
        'vitamins' => ['Folate' => 181],
        'minerals' => ['Iron' => 3.3, 'Magnesium' => 36]
    ],
    'black_beans' => [
        'name' => 'Black Beans',
        'category' => 'legume',
        'calories_per_100g' => 132,
        'protein' => 8.9,
        'carbs' => 24,
        'fats' => 0.5,
        'fiber' => 8.7,
        'sugar' => 0.3,
        'vitamins' => ['Folate' => 149],
        'minerals' => ['Iron' => 2.1, 'Magnesium' => 60]
    ],
    
    // DINNER FOODS
    'chicken_thigh' => [
        'name' => 'Chicken Thigh',
        'category' => 'protein',
        'calories_per_100g' => 209,
        'protein' => 18,
        'carbs' => 0,
        'fats' => 15,
        'fiber' => 0,
        'sugar' => 0,
        'vitamins' => ['Niacin' => 6.3, 'Vitamin B6' => 0.4],
        'minerals' => ['Selenium' => 18.2, 'Phosphorus' => 158]
    ],
    'wild_rice' => [
        'name' => 'Wild Rice',
        'category' => 'grain',
        'calories_per_100g' => 101,
        'protein' => 4,
        'carbs' => 21,
        'fats' => 0.3,
        'fiber' => 1.8,
        'sugar' => 0.7,
        'vitamins' => ['Thiamine' => 0.1],
        'minerals' => ['Manganese' => 0.3, 'Magnesium' => 32]
    ],
    'roasted_potatoes' => [
        'name' => 'Roasted Potatoes',
        'category' => 'vegetable',
        'calories_per_100g' => 87,
        'protein' => 2,
        'carbs' => 20,
        'fats' => 0.1,
        'fiber' => 2.2,
        'sugar' => 0.8,
        'vitamins' => ['Vitamin C' => 19.7, 'Vitamin B6' => 0.3],
        'minerals' => ['Potassium' => 421, 'Manganese' => 0.2]
    ],
    'buckwheat' => [
        'name' => 'Buckwheat',
        'category' => 'grain',
        'calories_per_100g' => 343,
        'protein' => 13.3,
        'carbs' => 71.5,
        'fats' => 3.4,
        'fiber' => 10,
        'sugar' => 0,
        'vitamins' => ['Thiamine' => 0.1],
        'minerals' => ['Manganese' => 1.3, 'Magnesium' => 231]
    ],
    'flax_seeds' => [
        'name' => 'Flax Seeds',
        'category' => 'seeds',
        'calories_per_100g' => 534,
        'protein' => 18.3,
        'carbs' => 28.9,
        'fats' => 42.2,
        'fiber' => 27.3,
        'sugar' => 1.6,
        'vitamins' => ['Thiamine' => 1.6],
        'minerals' => ['Manganese' => 2.5, 'Magnesium' => 392]
    ],
    
    // SNACKS
    'mixed_berries' => [
        'name' => 'Mixed Berries',
        'category' => 'fruit',
        'calories_per_100g' => 57,
        'protein' => 0.7,
        'carbs' => 14,
        'fats' => 0.3,
        'fiber' => 2.4,
        'sugar' => 10,
        'vitamins' => ['Vitamin C' => 58.8],
        'minerals' => ['Manganese' => 0.3]
    ],
    'hummus' => [
        'name' => 'Hummus',
        'category' => 'legume',
        'calories_per_100g' => 166,
        'protein' => 8,
        'carbs' => 14,
        'fats' => 9.6,
        'fiber' => 6,
        'sugar' => 0.3,
        'vitamins' => ['Folate' => 18],
        'minerals' => ['Iron' => 2.4, 'Magnesium' => 48]
    ],
    'trail_mix' => [
        'name' => 'Trail Mix',
        'category' => 'nuts',
        'calories_per_100g' => 462,
        'protein' => 13.8,
        'carbs' => 44.9,
        'fats' => 29.8,
        'fiber' => 5,
        'sugar' => 30.6,
        'vitamins' => ['Vitamin E' => 7.4],
        'minerals' => ['Magnesium' => 158, 'Iron' => 2.6]
    ],
    'dark_chocolate' => [
        'name' => 'Dark Chocolate',
        'category' => 'sweet',
        'calories_per_100g' => 546,
        'protein' => 7.8,
        'carbs' => 45.9,
        'fats' => 31.3,
        'fiber' => 10.9,
        'sugar' => 24.2,
        'vitamins' => ['Iron' => 11.9],
        'minerals' => ['Magnesium' => 228, 'Copper' => 1.8]
    ],
    'rice_cakes' => [
        'name' => 'Rice Cakes',
        'category' => 'grain',
        'calories_per_100g' => 387,
        'protein' => 8.2,
        'carbs' => 81.5,
        'fats' => 2.8,
        'fiber' => 2.8,
        'sugar' => 0.8,
        'vitamins' => [],
        'minerals' => ['Manganese' => 0.6]
    ],
    'apple_slices' => [
        'name' => 'Apple Slices',
        'category' => 'fruit',
        'calories_per_100g' => 52,
        'protein' => 0.3,
        'carbs' => 13.8,
        'fats' => 0.2,
        'fiber' => 2.4,
        'sugar' => 10.4,
        'vitamins' => ['Vitamin C' => 4.6],
        'minerals' => ['Potassium' => 107]
    ],
    'carrot_sticks' => [
        'name' => 'Carrot Sticks',
        'category' => 'vegetable',
        'calories_per_100g' => 41,
        'protein' => 0.9,
        'carbs' => 9.6,
        'fats' => 0.2,
        'fiber' => 2.8,
        'sugar' => 4.7,
        'vitamins' => ['Vitamin A' => 16706, 'Vitamin K' => 13.2],
        'minerals' => ['Potassium' => 320]
    ],
    'celery_sticks' => [
        'name' => 'Celery Sticks',
        'category' => 'vegetable',
        'calories_per_100g' => 16,
        'protein' => 0.7,
        'carbs' => 3,
        'fats' => 0.2,
        'fiber' => 1.6,
        'sugar' => 1.3,
        'vitamins' => ['Vitamin K' => 29.3],
        'minerals' => ['Potassium' => 260]
    ],
    'nuts_mix' => [
        'name' => 'Nuts Mix',
        'category' => 'nuts',
        'calories_per_100g' => 607,
        'protein' => 20.2,
        'carbs' => 21.6,
        'fats' => 54.4,
        'fiber' => 7.1,
        'sugar' => 4.2,
        'vitamins' => ['Vitamin E' => 8.3],
        'minerals' => ['Magnesium' => 201, 'Manganese' => 2.2]
    ],
    'cheese_cubes' => [
        'name' => 'Cheese Cubes',
        'category' => 'dairy',
        'calories_per_100g' => 403,
        'protein' => 25,
        'carbs' => 1.3,
        'fats' => 33,
        'fiber' => 0,
        'sugar' => 0.5,
        'vitamins' => ['Vitamin A' => 1002, 'Vitamin B12' => 1.1],
        'minerals' => ['Calcium' => 721, 'Sodium' => 621]
    ]
];

// Function to add food to database using API
function addFoodToDatabase($foodKey, $foodData) {
    $url = 'http://localhost/fitness-app/php/food_api.php?action=add_food';
    
    $postData = [
        'food_key' => $foodKey,
        'name' => $foodData['name'],
        'category' => $foodData['category'],
        'calories_per_100g' => $foodData['calories_per_100g'],
        'protein' => $foodData['protein'],
        'carbs' => $foodData['carbs'],
        'fats' => $foodData['fats'],
        'fiber' => $foodData['fiber'],
        'sugar' => $foodData['sugar'],
        'unit' => 'g' // Default unit
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/json\r\n",
            'method' => 'POST',
            'content' => json_encode($postData)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        return ['error' => 'Failed to connect to API'];
    }
    
    $decoded = json_decode($result, true);
    if ($decoded === null) {
        return ['error' => 'Invalid JSON response: ' . $result];
    }
    
    return $decoded;
}

// Function to check if food exists
function checkFoodExists($foodName) {
    $url = 'http://localhost/fitness-app/php/food_api.php?action=search&q=' . urlencode($foodName) . '&limit=1';
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    return ($data && isset($data['foods']) && count($data['foods']) > 0);
}

echo "<h2>Adding All Foods to Database</h2>";
echo "<p>This script will add all foods from breakfast, lunch, dinner, and snacks to the database.</p>";

$addedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($allFoods as $foodKey => $foodData) {
    echo "<h3>Processing: {$foodData['name']}</h3>";
    
    // Check if food already exists
    if (checkFoodExists($foodData['name'])) {
        echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>⚠️ Already exists:</strong> {$foodData['name']}";
        echo "</div>";
        $skippedCount++;
        continue;
    }
    
    // Add food to database
    $result = addFoodToDatabase($foodKey, $foodData);
    
    if ($result && !isset($result['error'])) {
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ Added successfully:</strong> {$foodData['name']}<br>";
        echo "Calories/100g: {$foodData['calories_per_100g']}, Protein: {$foodData['protein']}g, Carbs: {$foodData['carbs']}g, Fats: {$foodData['fats']}g";
        echo "</div>";
        $addedCount++;
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>❌ Error adding:</strong> {$foodData['name']}<br>";
        if ($result && isset($result['error'])) {
            echo "Error: {$result['error']}";
        } else {
            echo "Unknown error occurred<br>";
            echo "Raw result: " . print_r($result, true);
        }
        echo "</div>";
        $errorCount++;
    }
    
    echo "<hr>";
}

echo "<h2>Summary</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
echo "<strong>Total Foods Processed:</strong> " . count($allFoods) . "<br>";
echo "<strong>Successfully Added:</strong> $addedCount<br>";
echo "<strong>Already Existed:</strong> $skippedCount<br>";
echo "<strong>Errors:</strong> $errorCount<br>";
echo "</div>";

echo "<h3>Next Steps</h3>";
echo "<p>1. <a href='test_breakfast_foods.php'>Test Breakfast Foods</a> - Verify foods are in database</p>";
echo "<p>2. <a href='pages/dashboard.php'>Go to Dashboard</a> - Test breakfast food cards</p>";
echo "<p>3. <a href='debug_breakfast_api.html'>Debug API Integration</a> - Test API calls</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2 { color: #333; }
h3 { color: #666; margin-top: 30px; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
hr { margin: 20px 0; }
</style>
