<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE");
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

    $studentId = (int)($data['id'] ?? 0);

    if ($studentId <= 0) {

        $conn->close();

        errorResponse(
            422,
            "Valid Student ID is Required."
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
    | Soft Delete Student
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE students
        SET
            status = 0,
            updated_at = NOW()
        WHERE id = ?
    ");

    if (!$stmt) {

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare Statement."
        );

    }

    $stmt->bind_param(
        "i",
        $studentId
    );

    if (!$stmt->execute()) {

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Delete Student."
        );

    }

    $stmt->close();

    $conn->close();

    successResponse(
        "Student Deleted Successfully.",
        [
            "student_id" => $studentId,
            "deleted_by" => [
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