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
require_once "../../config/constants.php";
require_once "../../config/jwt.php";
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

    /*
    |--------------------------------------------------------------------------
    | Read Fields
    |--------------------------------------------------------------------------
    */

    $oldPassword = trim($data['old_password'] ?? '');

    $newPassword = trim($data['new_password'] ?? '');

    $confirmPassword = trim($data['confirm_password'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        empty($oldPassword) ||
        empty($newPassword) ||
        empty($confirmPassword)
    ) {

        $conn->close();

        errorResponse(
            422,
            "All Fields are Required."
        );

    }

    if (strlen($newPassword) < 6) {

        $conn->close();

        errorResponse(
            422,
            "Password must be at least 6 characters."
        );

    }

    if ($newPassword != $confirmPassword) {

        $conn->close();

        errorResponse(
            422,
            "Confirm Password does not match."
        );

    }

    if ($oldPassword == $newPassword) {

        $conn->close();

        errorResponse(
            422,
            "New Password must be different from Old Password."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Fetch Current Password
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            password,
            name
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

    if ($result->num_rows == 0) {

        $stmt->close();

        $conn->close();

        errorResponse(
            404,
            "User Not Found."
        );

    }

    $user = $result->fetch_assoc();

    /*
    |--------------------------------------------------------------------------
    | Verify Old Password
    |--------------------------------------------------------------------------
    */

    if (!password_verify($oldPassword, $user['password'])) {

        $stmt->close();

        $conn->close();

        errorResponse(
            401,
            "Old Password is Incorrect."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Encrypt New Password
    |--------------------------------------------------------------------------
    */

    $newHash = password_hash(
        $newPassword,
        PASSWORD_DEFAULT
    );

    /*
    |--------------------------------------------------------------------------
    | Update Password
    |--------------------------------------------------------------------------
    */

    $update = $conn->prepare("
        UPDATE users
        SET
            password = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    if (!$update) {

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare Update Statement."
        );

    }

    $update->bind_param(
        "si",
        $newHash,
        $loggedUser['id']
    );

    if (!$update->execute()) {

        $update->close();

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Change Password."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Logout From All Devices
    |--------------------------------------------------------------------------
    */

    $logout = $conn->prepare("
        UPDATE api_tokens
        SET
            is_active = 0
        WHERE user_id = ?
    ");

    if ($logout) {

        $logout->bind_param(
            "i",
            $loggedUser['id']
        );

        $logout->execute();

        $logout->close();

    }

    $update->close();

    $stmt->close();

    $conn->close();

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    successResponse(
        "Password Changed Successfully. Please Login Again."
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
```
