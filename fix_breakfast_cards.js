// Fix for breakfast cards not showing in current meal section
// This script can be added to the dashboard to debug and fix the issue

console.log('Breakfast Cards Fix Script Loaded');

// Override the addBreakfastFood function with enhanced debugging
function addBreakfastFoodFixed(foodKey, amount, unit) {
    console.log('🔍 addBreakfastFoodFixed called:', { foodKey, amount, unit });
    
    // Ensure we're on breakfast meal type
    const breakfastRadio = document.getElementById('breakfast');
    if (breakfastRadio) {
        breakfastRadio.checked = true;
        console.log('✅ Breakfast radio button checked');
    } else {
        console.log('❌ Breakfast radio button not found');
    }
    
    currentMealType = 'breakfast';
    console.log('✅ Current meal type set to:', currentMealType);
    
    // Try to find food in local database first
    const food = foodDatabase[foodKey];
    if (food) {
        console.log('✅ Found in local database:', food);
        addBreakfastFoodFromLocalFixed(food, amount, unit);
        return;
    }
    
    console.log('🔍 Not found locally, searching API for:', foodKey);
    
    // If not found locally, search in database API
    fetch(`php/food_api.php?action=search&q=${encodeURIComponent(foodKey)}&limit=1`)
        .then(response => {
            console.log('📡 API response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('📡 API raw response:', text);
            try {
                const data = JSON.parse(text);
                if (data.error || !data.foods || data.foods.length === 0) {
                    console.log('❌ API error or no foods found:', data);
                    showNotification('Food not found in database', 'warning');
                    return;
                }
                
                const apiFood = data.foods[0];
                console.log('✅ Found in API:', apiFood);
                addBreakfastFoodFromAPIFixed(apiFood, amount, unit);
            } catch (e) {
                console.error('❌ JSON parse error:', e);
                showNotification('Error parsing API response', 'error');
            }
        })
        .catch(error => {
            console.error('❌ API fetch error:', error);
            showNotification('Error fetching food data', 'error');
        });
}

function addBreakfastFoodFromLocalFixed(food, amount, unit) {
    console.log('🔍 addBreakfastFoodFromLocalFixed called:', food.name);
    
    // Convert amount to grams for calculation
    const convertedAmount = convertPortionToGrams(parseFloat(amount), unit, food.name);
    console.log('📊 Converted amount to grams:', convertedAmount);
    
    // Calculate nutrition
    const calories = (food.caloriesPer100g * convertedAmount) / 100;
    const protein = (food.protein * convertedAmount) / 100;
    const carbs = (food.carbs * convertedAmount) / 100;
    const fats = (food.fats * convertedAmount) / 100;
    
    console.log('📊 Calculated nutrition:', { calories, protein, carbs, fats });
    
    // Create food data
    const foodData = {
        id: Date.now() + Math.random(),
        name: food.name,
        amount: `${amount} ${unit}`,
        calories: Math.round(calories),
        protein: Math.round(protein),
        carbs: Math.round(carbs),
        fats: Math.round(fats),
        fiber: Math.round((food.fiber * convertedAmount) / 100),
        sugar: Math.round((food.sugar * convertedAmount) / 100)
    };
    
    console.log('📦 Created food data:', foodData);
    
    // Add to daily meals
    dailyMeals[currentMealType].push(foodData);
    console.log('📝 Added to dailyMeals[' + currentMealType + ']:', dailyMeals[currentMealType]);
    
    // Update display
    console.log('🔄 Updating display...');
    updateCurrentMealDisplayFixed();
    updateDailyTotals();
    
    // Show success notification
    showNotification(`${food.name} added to breakfast!`, 'success');
}

function addBreakfastFoodFromAPIFixed(apiFood, amount, unit) {
    console.log('🔍 addBreakfastFoodFromAPIFixed called:', apiFood.name);
    
    // Convert amount to grams for calculation
    const convertedAmount = convertPortionToGrams(parseFloat(amount), unit, apiFood.name);
    console.log('📊 Converted amount to grams:', convertedAmount);
    
    // Calculate nutrition
    const calories = (apiFood.calories_per_100g * convertedAmount) / 100;
    const protein = (apiFood.protein * convertedAmount) / 100;
    const carbs = (apiFood.carbs * convertedAmount) / 100;
    const fats = (apiFood.fats * convertedAmount) / 100;
    
    console.log('📊 Calculated nutrition:', { calories, protein, carbs, fats });
    
    // Create food data
    const foodData = {
        id: Date.now() + Math.random(),
        name: apiFood.name,
        amount: `${amount} ${unit}`,
        calories: Math.round(calories),
        protein: Math.round(protein),
        carbs: Math.round(carbs),
        fats: Math.round(fats),
        fiber: Math.round((apiFood.fiber * convertedAmount) / 100),
        sugar: Math.round((apiFood.sugar * convertedAmount) / 100),
        category: apiFood.category,
        foodId: apiFood.id
    };
    
    console.log('📦 Created food data:', foodData);
    
    // Add to daily meals
    dailyMeals[currentMealType].push(foodData);
    console.log('📝 Added to dailyMeals[' + currentMealType + ']:', dailyMeals[currentMealType]);
    
    // Update display
    console.log('🔄 Updating display...');
    updateCurrentMealDisplayFixed();
    updateDailyTotals();
    
    // Show success notification
    showNotification(`${apiFood.name} added to breakfast!`, 'success');
}

function updateCurrentMealDisplayFixed() {
    console.log('🔄 updateCurrentMealDisplayFixed called');
    const foodList = document.getElementById('foodList');
    
    if (!foodList) {
        console.log('❌ foodList element not found!');
        return;
    }
    
    const currentMealFoods = dailyMeals[currentMealType];
    console.log('📊 Current meal foods:', currentMealFoods);
    
    if (currentMealFoods.length === 0) {
        foodList.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-utensils fa-2x mb-2"></i><br>No foods added to this meal yet. Start by searching for a food above!</div>';
        console.log('📝 No foods, showing empty message');
        return;
    }
    
    console.log('📝 Rendering', currentMealFoods.length, 'food items');
    foodList.innerHTML = currentMealFoods.map(food => `
        <div class="food-item">
            <div class="food-info">
                <h6 class="mb-1">${food.name}</h6>
                <small class="text-muted">${food.amount}</small>
                <div class="nutrition-details mt-2">
                    <div class="nutrition-row">
                        <span class="nutrition-label">Calories:</span>
                        <span class="nutrition-value">${food.calories}</span>
                    </div>
                    <div class="nutrition-row">
                        <span class="nutrition-label">Protein:</span>
                        <span class="nutrition-value">${food.protein}g</span>
                    </div>
                    <div class="nutrition-row">
                        <span class="nutrition-label">Carbs:</span>
                        <span class="nutrition-value">${food.carbs}g</span>
                    </div>
                    <div class="nutrition-row">
                        <span class="nutrition-label">Fats:</span>
                        <span class="nutrition-value">${food.fats}g</span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="food-calories">${food.calories} cal</span>
                <button class="remove-food-btn" onclick="removeFoodFromMeal('${food.id}', '${currentMealType}')" title="Remove food">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `).join('');
    
    console.log('✅ Food list HTML updated');
}

// Override the original addBreakfastFood function
if (typeof addBreakfastFood !== 'undefined') {
    console.log('🔄 Overriding addBreakfastFood function');
    addBreakfastFood = addBreakfastFoodFixed;
} else {
    console.log('❌ addBreakfastFood function not found');
}

// Override the original updateCurrentMealDisplay function
if (typeof updateCurrentMealDisplay !== 'undefined') {
    console.log('🔄 Overriding updateCurrentMealDisplay function');
    updateCurrentMealDisplay = updateCurrentMealDisplayFixed;
} else {
    console.log('❌ updateCurrentMealDisplay function not found');
}

console.log('✅ Breakfast Cards Fix Script Complete');
