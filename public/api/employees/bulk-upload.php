<?php
require_once __DIR__ . '/../../../app/config/api_auth.php';
// Bulk upload for employees
header('Content-Type: application/json');

require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/models/Employee.php';
require_once __DIR__ . '/../../../app/models/Department.php';

$response = ['success' => false, 'message' => '', 'results' => []];

function normalizeCsvLookupValue($value)
{
    return strtolower(preg_replace('/\s+/', ' ', trim($value)));
}

function normalizeCsvHeader($value)
{
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    return normalizeCsvLookupValue($value);
}

function removeUtf8Bom($value)
{
    if (!is_string($value)) {
        return $value;
    }

    return strpos($value, "\xEF\xBB\xBF") === 0 ? substr($value, 3) : $value;
}

function convertCsvTextToUtf8(string $content): string
{
    $content = removeUtf8Bom($content);

    if (mb_check_encoding($content, 'UTF-8')) {
        return $content;
    }

    $encodings = ['BIG5', 'CP950', 'GBK', 'CP936'];
    $detected = mb_detect_encoding($content, $encodings, true);
    if ($detected !== false) {
        $converted = @mb_convert_encoding($content, 'UTF-8', $detected);
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }
    }

    foreach ($encodings as $encoding) {
        $converted = @mb_convert_encoding($content, 'UTF-8', $encoding);
        if ($converted !== false && mb_check_encoding($converted, 'UTF-8')) {
            return $converted;
        }
    }

    throw new Exception('Invalid file encoding. Please save the CSV as UTF-8 before uploading.');
}

function normalizeCsvField($value)
{
    return trim(removeUtf8Bom((string) $value));
}

function findCsvColumn($headers, $acceptedNames)
{
    foreach ($acceptedNames as $name) {
        $index = array_search($name, $headers, true);
        if ($index !== false) {
            return $index;
        }
    }

    return false;
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or file upload error.');
    }

    $file = $_FILES['file'];
    
    // Validate file type
    if ($file['type'] !== 'text/csv' && !preg_match('/\.csv$/i', $file['name'])) {
        throw new Exception('Invalid file type. Please upload a CSV file.');
    }

    // Read and parse CSV
    $rawContent = file_get_contents($file['tmp_name']);
    if ($rawContent === false) {
        throw new Exception('Unable to read uploaded file.');
    }

    $csvContent = convertCsvTextToUtf8($rawContent);
    $handle = fopen('php://memory', 'r+');
    if (!$handle) {
        throw new Exception('Unable to open temporary buffer.');
    }
    fwrite($handle, $csvContent);
    rewind($handle);

    // Initialize models
    $db = (new Database())->connect();
    $employee = new Employee($db);
    $department = new Department($db);

    // Get all departments for lookup
    $allDepartments = $department->getAll();
    $deptMap = [];
    foreach ($allDepartments as $dept) {
        $deptMap[normalizeCsvLookupValue($dept['department_name'])] = $dept['id'];
    }

    $results = [
        'total' => 0,
        'success' => 0,
        'errors' => []
    ];

    $rowNum = 0;
    $headers = null;
    $columnIndexes = null;

    while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        $rowNum++;

        // First row is header
        if ($rowNum === 1) {
            $headers = array_map('normalizeCsvHeader', $row);
            $columnIndexes = [
                'employee_code' => findCsvColumn($headers, ['employee id', 'employee code', 'employee_code', 'emp id', 'emp code']),
                'english_name' => findCsvColumn($headers, ['english name', 'english_name', 'employee name', 'name', 'full name', 'full_name']),
                'chinese_name' => findCsvColumn($headers, ['chinese name', 'chinese_name', 'chinese']),
                'gender' => findCsvColumn($headers, ['gender', 'sex']),
                'department' => findCsvColumn($headers, ['department', 'department name', 'department_name']),
            ];

            if (
                $columnIndexes['employee_code'] === false ||
                $columnIndexes['english_name'] === false ||
                $columnIndexes['department'] === false
            ) {
                throw new Exception(
                    'CSV headers missing or incorrect. First row must include: Employee ID, English Name, and Department. Gender is optional.'
                );
            }

            continue;
        }

        $results['total']++;

        try {
            $empCode = normalizeCsvField($row[$columnIndexes['employee_code']] ?? '');
            $englishName = normalizeCsvField($row[$columnIndexes['english_name']] ?? '');
            $chineseName = '';
            if ($columnIndexes['chinese_name'] !== false && isset($row[$columnIndexes['chinese_name']])) {
                $chineseName = normalizeCsvField($row[$columnIndexes['chinese_name']]);
            }
            $gender = '';
            if ($columnIndexes['gender'] !== false && isset($row[$columnIndexes['gender']])) {
                $gender = normalizeCsvField($row[$columnIndexes['gender']]);
            }
            $deptName = normalizeCsvField($row[$columnIndexes['department']] ?? '');

            // Validation
            if (!$empCode) throw new Exception('Employee ID is required.');
            if (!$deptName) throw new Exception('Department is required.');

            // Normalize gender values and default blank/missing to Others
            $gender = normalizeCsvLookupValue($gender);
            if ($gender === '' || $gender === null) {
                $gender = 'Other';
            } else {
                $gender = ucfirst(strtolower($gender));
                if (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
                    throw new Exception("Invalid gender: {$gender}. Must be Male, Female, or Other.");
                }
            }

            // Find department ID
            $deptId = $deptMap[normalizeCsvLookupValue($deptName)] ?? null;
            if (!$deptId) {
                throw new Exception("Department '{$deptName}' not found.");
            }

            // Prepare data
            $data = [
                'employee_code' => $empCode,
                'english_name' => $englishName,
                'chinese_name' => $chineseName,
                'gender' => $gender,
                'department_id' => $deptId,
                'status' => 'Active'  // Default status
            ];

            // Check if employee exists
            $existingStmt = $db->prepare(
                "SELECT id FROM employees WHERE employee_code = ?"
            );
            $existingStmt->execute([$empCode]);
            $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing employee
                $employee->update($existing['id'], $data);
                $results['success']++;
                $results['errors'][] = [
                    'row' => $rowNum,
                    'employee_code' => $empCode,
                    'status' => 'updated'
                ];
            } else {
                // Insert new employee
                $employee->create($data);
                $results['success']++;
                $results['errors'][] = [
                    'row' => $rowNum,
                    'employee_code' => $empCode,
                    'status' => 'created'
                ];
            }
        } catch (Exception $e) {
            $results['errors'][] = [
                'row' => $rowNum,
                'error' => $e->getMessage()
            ];
        }
    }

    fclose($handle);

    $response['success'] = true;
    $response['results'] = $results;
    $response['message'] = "Bulk upload completed. {$results['success']} of {$results['total']} records processed.";

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
