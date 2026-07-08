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
    | Validate Student ID
    |--------------------------------------------------------------------------
    */

    if (!isset($_GET['id'])) {

        $conn->close();

        errorResponse(
            422,
            "Student ID is Required."
        );

    }

    $studentId = (int)$_GET['id'];

    if ($studentId <= 0) {

        $conn->close();

        errorResponse(
            422,
            "Invalid Student ID."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Query
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

        WHERE id=?
        AND status = 1
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
        "i",
        $studentId
    );

    if (!$stmt->execute()) {

        $stmt->close();
        $conn->close();

        errorResponse(
            500,
            "Unable to Fetch Student."
        );

    }

    $result = $stmt->get_result();
        /*
    |--------------------------------------------------------------------------
    | Check Student Exists
    |--------------------------------------------------------------------------
    */

    if ($result->num_rows == 0) {

        $stmt->close();

        $conn->close();

        errorResponse(
            404,
            "Student Not Found."
        );

    }

    $student = $result->fetch_assoc();

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    $response = [

        "student" => $student,

        "requested_by" => [

            "id" => $loggedUser['id'],

            "name" => $loggedUser['name'],

            "role" => $loggedUser['role']

        ]

    ];

    $stmt->close();

    $conn->close();

    successResponse(

        "Student Details Fetched Successfully.",

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