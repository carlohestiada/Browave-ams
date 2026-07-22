<?php
$mealsPage = file_get_contents(__DIR__ . '/../public/meals.php');
$mealsJs = file_get_contents(__DIR__ . '/../public/assets/js/meals.js');

$errors = [];

if (strpos($mealsPage, 'Weekly Meal Planner') === false) {
    $errors[] = 'meals.php is missing the weekly planner heading';
}

if (strpos($mealsJs, 'renderWeeklyMealPlanner') === false) {
    $errors[] = 'meals.js is missing the weekly planner renderer';
}

if (strpos($mealsJs, 'saveSundayLunchBox') === false) {
    $errors[] = 'meals.js is missing the Sunday lunch box save handler';
}

if ($errors) {
    echo implode("\n", $errors) . "\n";
    exit(1);
}

echo "Weekly planner UI test passed.\n";
