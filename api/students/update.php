<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
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

        $conn->close();

        errorResponse(
            400,
            "Invalid JSON Request."
        );

    }

    if (empty($data)) {

        $conn->close();

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

    $studentId = (int)($data['id'] ?? 0);

    $studentName = sanitize(trim($data['student_name'] ?? ''));

    $age = (int)($data['age'] ?? 0);

    $city = sanitize(trim($data['city'] ?? ''));

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($studentId <= 0) {

        $conn->close();

        errorResponse(
            422,
            "Valid Student ID is Required."
        );

    }

    if (empty($studentName)) {

        $conn->close();

        errorResponse(
            422,
            "Student Name is Required."
        );

    }

    if ($age <= 0) {

        $conn->close();

        errorResponse(
            422,
            "Valid Age is Required."
        );

    }

    if (empty($city)) {

        $conn->close();

        errorResponse(
            422,
            "City is Required."
        );

    }
        /*
    |--------------------------------------------------------------------------
    | Check Student Exists
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT id
        FROM students
        WHERE id = ?
        AND status = 1
        LIMIT 1
    ");

    $stmt->bind_param(
        "i",
        $studentId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {

        $stmt->close();

        $conn->close();

        errorResponse(
            404,
            "Student Not Found."
        );

    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | Duplicate Check
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT id
        FROM students
        WHERE student_name = ?
        AND city = ?
        AND id != ?
        LIMIT 1
    ");

    $stmt->bind_param(
        "ssi",
        $studentName,
        $city,
        $studentId
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
    | Update Student
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE students
        SET
            student_name = ?,
            age = ?,
            city = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->bind_param(
        "sisi",
        $studentName,
        $age,
        $city,
        $studentId
    );

    if (!$stmt->execute()) {

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Update Student."
        );

    }

    $stmt->close();

    $conn->close();

    successResponse(
        "Student Updated Successfully.",
        [
            "id" => $studentId,
            "student_name" => $studentName,
            "age" => $age,
            "city" => $city,
            "updated_by" => [
                "id" => $loggedUser['id'],
                "name" => $loggedUser['name']
            ]
        ]
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