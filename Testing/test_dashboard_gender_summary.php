<?php
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Report.php';

$db = (new Database())->connect();
$report = new Report($db);
$stats = $report->getSummaryStats(date('Y-m-d'));

$male = isset($stats['male_employees']) ? (int) $stats['male_employees'] : null;
$female = isset($stats['female_employees']) ? (int) $stats['female_employees'] : null;
$expectedMale = (int) $db->query("SELECT COUNT(*) FROM employees WHERE gender='Male'")->fetchColumn();
$expectedFemale = (int) $db->query("SELECT COUNT(*) FROM employees WHERE gender='Female'")->fetchColumn();

echo "male_employees={$male} expected={$expectedMale}\n";
echo "female_employees={$female} expected={$expectedFemale}\n";

exit(($male === $expectedMale && $female === $expectedFemale) ? 0 : 1);
