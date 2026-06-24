<?php
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
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        throw new Exception('Unable to open file.');
    }

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
                'full_name' => findCsvColumn($headers, ['full name', 'name', 'employee name', 'full_name']),
                'gender' => findCsvColumn($headers, ['gender', 'sex']),
                'department' => findCsvColumn($headers, ['department', 'department name', 'department_name']),
            ];

            if (
                $columnIndexes['employee_code'] === false ||
                $columnIndexes['full_name'] === false ||
                $columnIndexes['gender'] === false ||
                $columnIndexes['department'] === false
            ) {
                throw new Exception(
                    'CSV headers missing or incorrect. First row must include: Employee ID, Full Name, Gender, Department.'
                );
            }

            continue;
        }

        $results['total']++;

        try {
            $empCode = trim($row[$columnIndexes['employee_code']] ?? '');
            $fullName = trim($row[$columnIndexes['full_name']] ?? '');
            $gender = trim($row[$columnIndexes['gender']] ?? '');
            $deptName = trim($row[$columnIndexes['department']] ?? '');

            // Validation
            if (!$empCode) throw new Exception('Employee ID is required.');
            if (!$fullName) throw new Exception('Full Name is required.');
            if (!$gender) throw new Exception('Gender is required.');
            if (!$deptName) throw new Exception('Department is required.');

            // Validate gender
            if (!in_array($gender, ['Male', 'Female'])) {
                throw new Exception("Invalid gender: {$gender}. Must be Male or Female.");
            }

            // Find department ID
            $deptId = $deptMap[normalizeCsvLookupValue($deptName)] ?? null;
            if (!$deptId) {
                throw new Exception("Department '{$deptName}' not found.");
            }

            // Prepare data
            $data = [
                'employee_code' => $empCode,
                'full_name' => $fullName,
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
