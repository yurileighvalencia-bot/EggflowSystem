<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\Traits\ApiResponses;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests, ApiResponses;
}
