<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/database.php";
require_once "../../config/response.php";
require_once "../../helper/auth_helper.php";

try {

    /*
    |--------------------------------------------------------------------------
    | Authenticate User
    |--------------------------------------------------------------------------
    */

    $user = authenticateAccessToken();

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    $db = new Database();
    $conn = $db->connect();

    if (!$conn) {
        errorResponse(
            500,
            "Database Connection Failed."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Students
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            student_name,
            age,
            city,
            created_at,
            updated_at
        FROM students
        WHERE status = 1
        ORDER BY id DESC
    ");

    if (!$stmt) {

        $conn->close();

        errorResponse(
            500,
            "Unable to prepare statement."
        );

    }

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->close();

        errorResponse(
            500,
            "Unable to fetch students."
        );

    }

    $result = $stmt->get_result();

    $students = [];

    while ($row = $result->fetch_assoc()) {

        $students[] = $row;

    }

    $response = [

        "total_records" => count($students),

        "logged_in_user" => [

            "id" => $user['id'],
            "name" => $user['name'],
            "role" => $user['role']

        ],

        "students" => $students

    ];

    $stmt->close();
    $conn->close();

    successResponse(
        "Students fetched successfully.",
        $response
    );

} catch (Exception $e) {

    error_log($e->getMessage());

    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }

    errorResponse(
        500,
        "Something went wrong. Please try again later."
    );

}

?>