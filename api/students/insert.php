<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/database.php";
require_once "../../config/response.php";
require_once "../../helper/common_helper.php";
require_once "../../helper/auth_helper.php";

try {

    /*
    |--------------------------------------------------------------------------
    | Authenticate User
    |--------------------------------------------------------------------------
    */

    $loggedUser = authenticateAccessToken();

    /*
    |--------------------------------------------------------------------------
    | Database Connection
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
    | Read JSON Request
    |--------------------------------------------------------------------------
    */

    $data = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (json_last_error() !== JSON_ERROR_NONE) {

        errorResponse(
            400,
            "Invalid JSON Request."
        );

    }

    if (empty($data)) {

        errorResponse(
            400,
            "Request Body Required."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Read Input
    |--------------------------------------------------------------------------
    */

    $studentName = sanitize(trim($data['student_name'] ?? ''));

    $age = (int)($data['age'] ?? 0);

    $city = sanitize(trim($data['city'] ?? ''));
    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (empty($studentName)) {

        errorResponse(
            422,
            "Student Name is Required."
        );

    }

    if (strlen($studentName) < 3) {

        errorResponse(
            422,
            "Student Name must be at least 3 characters."
        );

    }

    if ($age <= 0) {

        errorResponse(
            422,
            "Valid Age is Required."
        );

    }

    if ($age < 1 || $age > 120) {

        errorResponse(
            422,
            "Age must be between 1 and 120."
        );

    }

    if (empty($city)) {

        errorResponse(
            422,
            "City is Required."
        );

    }

    if (strlen($city) < 2) {

        errorResponse(
            422,
            "Invalid City Name."
        );

    }
        /*
    |--------------------------------------------------------------------------
    | Check Duplicate Student
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT id
        FROM students
        WHERE student_name = ?
        AND city = ?
        LIMIT 1
    ");

    if (!$stmt) {

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare Statement."
        );

    }

    $stmt->bind_param(
        "ss",
        $studentName,
        $city
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {

        $stmt->close();
        $conn->close();

        errorResponse(
            409,
            "Student Already Exists."
        );

    }

    $stmt->close();
        /*
    |--------------------------------------------------------------------------
    | Insert Student
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO students
        (
            student_name,
            age,
            city,
            created_at,
            updated_at
        )
        VALUES
        (
            ?,?,?,NOW(),NOW()
        )
    ");

    if (!$stmt) {

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare Insert Statement."
        );

    }

    $stmt->bind_param(
        "sis",
        $studentName,
        $age,
        $city
    );

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->close();

        errorResponse(
            500,
            "Unable to Insert Student."
        );

    }

    $studentId = $stmt->insert_id;

    $stmt->close();

        /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    $response = [

        "student_id" => $studentId,

        "student_name" => $studentName,

        "age" => $age,

        "city" => $city

    ];

    $conn->close();

    successResponse(
        "Student Added Successfully.",
        $response
    );

}
catch (Exception $e) {

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