<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, PUT");
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
    | GET PROFILE
    |--------------------------------------------------------------------------
    */

    if ($_SERVER['REQUEST_METHOD'] == "GET") {

        $stmt = $conn->prepare("
            SELECT

                id,
                name,
                email,
                mobile,
                role

            FROM users

            WHERE id = ?

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
            $loggedUser['id']
        );

        $stmt->execute();

        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        $stmt->close();

        $conn->close();

        successResponse(
            "Profile Loaded Successfully.",
            [
                "user" => $user
            ]
        );

    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE
    |--------------------------------------------------------------------------
    */

    if ($_SERVER['REQUEST_METHOD'] == "PUT") {

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

        $name = sanitize(trim($data['name'] ?? ''));

        $email = sanitize(trim($data['email'] ?? ''));

        if ($name == "") {

            $conn->close();

            errorResponse(
                422,
                "Name is Required."
            );

        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $conn->close();

            errorResponse(
                422,
                "Valid Email is Required."
            );

        }

        /*
        |--------------------------------------------------------------------------
        | Check Duplicate Email
        |--------------------------------------------------------------------------
        */

        $check = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
            AND id != ?
            LIMIT 1
        ");

        $check->bind_param(
            "si",
            $email,
            $loggedUser['id']
        );

        $check->execute();

        $duplicate = $check->get_result();

        if ($duplicate->num_rows > 0) {

            $check->close();

            $conn->close();

            errorResponse(
                409,
                "Email Already Exists."
            );

        }

        $check->close();

        /*
        |--------------------------------------------------------------------------
        | Update Profile
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare("
            UPDATE users

            SET

                name = ?,
                email = ?

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
            "ssi",
            $name,
            $email,
            $loggedUser['id']
        );

        if (!$stmt->execute()) {

            $stmt->close();

            $conn->close();

            errorResponse(
                500,
                "Unable to Update Profile."
            );

        }

        $stmt->close();

        $conn->close();

        successResponse(
            "Profile Updated Successfully."
        );

    }

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