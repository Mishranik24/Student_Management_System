<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/database.php";
require_once "../../config/response.php";
require_once "../../helper/common_helper.php";

try {

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
    | Read JSON
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

    /*
    |--------------------------------------------------------------------------
    | Read Email
    |--------------------------------------------------------------------------
    */

    $email = strtolower(
        sanitize(
            trim($data['email'] ?? '')
        )
    );

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (empty($email)) {

        $conn->close();

        errorResponse(
            422,
            "Email is Required."
        );

    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $conn->close();

        errorResponse(
            422,
            "Invalid Email Address."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Check User
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            name,
            email
        FROM users
        WHERE email=?
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
        "s",
        $email
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {

        $stmt->close();

        $conn->close();

        errorResponse(
            404,
            "Email is not Registered."
        );

    }

    $user = $result->fetch_assoc();

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | Delete Previous OTP
    |--------------------------------------------------------------------------
    */

    $delete = $conn->prepare("
        DELETE FROM password_resets
        WHERE email=?
    ");

    $delete->bind_param(
        "s",
        $email
    );

    $delete->execute();

    $delete->close();

    /*
    |--------------------------------------------------------------------------
    | Generate OTP
    |--------------------------------------------------------------------------
    */

    $otp = random_int(100000,999999);

    $expiresAt = date(
        "Y-m-d H:i:s",
        strtotime("+10 minutes")
    );

    /*
    |--------------------------------------------------------------------------
    | Save OTP
    |--------------------------------------------------------------------------
    */

    $insert = $conn->prepare("
        INSERT INTO password_resets
        (
            user_id,
            email,
            otp,
            expires_at
        )
        VALUES
        (
            ?, ?, ?, ?
        )
    ");

    if (!$insert) {

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare OTP Statement."
        );

    }

    $insert->bind_param(
        "isss",
        $user['id'],
        $email,
        $otp,
        $expiresAt
    );

    if (!$insert->execute()) {

        $insert->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Generate OTP."
        );

    }

    $insert->close();

    $conn->close();

    /*
    |--------------------------------------------------------------------------
    | Success
    |--------------------------------------------------------------------------
    */

    successResponse(
        "OTP Sent Successfully.",
        [
            "otp"=>$otp
        ]
    );

}
catch(Exception $e){

    error_log($e->getMessage());

    if(isset($conn) && $conn instanceof mysqli){
        $conn->close();
    }

    errorResponse(
        500,
        "Something went wrong."
    );

}