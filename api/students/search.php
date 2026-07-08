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

    $data = json_decode(file_get_contents("php://input"), true);

    if (json_last_error() !== JSON_ERROR_NONE) {

        $conn->close();

        errorResponse(
            400,
            "Invalid JSON Request."
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Search Parameters
    |--------------------------------------------------------------------------
    */

    $search = sanitize(trim($data['search'] ?? ''));

    $page = (int)($data['page'] ?? 1);

    $limit = (int)($data['limit'] ?? 10);

    $sortBy = "id";

    $sortOrder = "ASC";

    /*
    |--------------------------------------------------------------------------
    | Pagination Validation
    |--------------------------------------------------------------------------
    */

    if ($page < 1) {
        $page = 1;
    }

    if ($limit < 1) {
        $limit = 10;
    }

    if ($limit > 100) {
        $limit = 100;
    }

    $offset = ($page - 1) * $limit;

    /*
    |--------------------------------------------------------------------------
    | Allowed Sorting Columns
    |--------------------------------------------------------------------------
    */

    $allowedSortColumns = [

        "id",

        "student_name",

        "age",

        "city",

        "created_at"

    ];

    if (!in_array($sortBy, $allowedSortColumns)) {

        $sortBy = "id";

    }

    /*
    |--------------------------------------------------------------------------
    | Sort Order
    |--------------------------------------------------------------------------
    */

    if (!in_array($sortOrder, ["ASC", "DESC"])) {

        $sortOrder = "DESC";

    }

    /*
    |--------------------------------------------------------------------------
    | Search Keyword
    |--------------------------------------------------------------------------
    */

    $keyword = "%" . $search . "%";
        /*
    |--------------------------------------------------------------------------
    | Total Records
    |--------------------------------------------------------------------------
    */

    $countStmt = $conn->prepare("
        SELECT
            COUNT(*) AS total
        FROM students
        WHERE status = 1
        AND
        (
            student_name LIKE ?
            OR city LIKE ?
            OR CAST(age AS CHAR) LIKE ?
        )
    ");

    if (!$countStmt) {

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare Count Statement."
        );

    }

    $countStmt->bind_param(
        "sss",
        $keyword,
        $keyword,
        $keyword
    );

    if (!$countStmt->execute()) {

        $countStmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Fetch Total Records."
        );

    }

    $countResult = $countStmt->get_result();

    $totalRecords = (int)$countResult->fetch_assoc()['total'];

    $countStmt->close();

    /*
    |--------------------------------------------------------------------------
    | Total Pages
    |--------------------------------------------------------------------------
    */

    $totalPages = ($totalRecords > 0)
        ? ceil($totalRecords / $limit)
        : 0;

    /*
    |--------------------------------------------------------------------------
    | Search Students
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
        AND
        (
            student_name LIKE ?
            OR city LIKE ?
            OR CAST(age AS CHAR) LIKE ?
        )
        ORDER BY id ASC
        LIMIT ?, ?
    ");

    if (!$stmt) {

        $conn->close();

        errorResponse(
            500,
            "Unable to Prepare Search Statement."
        );

    }

    $stmt->bind_param(
        "sssii",
        $keyword,
        $keyword,
        $keyword,
        $offset,
        $limit
    );

    if (!$stmt->execute()) {

        $stmt->close();

        $conn->close();

        errorResponse(
            500,
            "Unable to Search Students."
        );

    }

    $result = $stmt->get_result();

    $students = [];

    while ($row = $result->fetch_assoc()) {

        $students[] = $row;

    }
        /*
    |--------------------------------------------------------------------------
    | No Records Found
    |--------------------------------------------------------------------------
    */

    if (count($students) == 0) {

        $stmt->close();

        $conn->close();

        successResponse(
            "No Students Found.",
            [
                "total_records" => 0,
                "page" => $page,
                "limit" => $limit,
                "total_pages" => 0,
                "sort_by" => $sortBy,
                "sort_order" => $sortOrder,
                "students" => []
            ]
        );

    }

    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    $response = [

        "total_records" => $totalRecords,

        "page" => $page,

        "limit" => $limit,

        "total_pages" => $totalPages,

        "sort_by" => $sortBy,

        "sort_order" => $sortOrder,

        "students" => $students

    ];

    $stmt->close();

    $conn->close();

    successResponse(
        "Students Found.",
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