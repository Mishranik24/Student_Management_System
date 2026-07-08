<?php

function jsonResponse($statusCode, $status, $message, $data = [])
{
    http_response_code($statusCode);

    echo json_encode([
        "status" => $status,
        "statusCode" => $statusCode,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

function successResponse($message, $data = [])
{
    jsonResponse(200, "S", $message, $data);
}

function errorResponse($statusCode, $message)
{
    jsonResponse($statusCode, "F", $message);
}