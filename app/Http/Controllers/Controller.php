<?php

namespace App\Http\Controllers;
use Tymon\JWTAuth\JWTGuard;

abstract class Controller
{
    /** @var JWTGuard */
    protected $auth;
    /** @var User */
    protected $user;

    public function __construct()
    {
        $this->auth = auth();
        $this->user = $this->auth->user();
    }
}
