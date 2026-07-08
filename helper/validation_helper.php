<?php

function validateEmail($email)
{
    return filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    );
}

function validateMobile($mobile)
{
    return preg_match(
        "/^[6-9][0-9]{9}$/",
        $mobile
    );
}

function validatePassword($password)
{

    return strlen($password) >= 6;

}