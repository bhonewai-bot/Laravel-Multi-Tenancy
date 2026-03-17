<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Base controller for the application.
 */
abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;
}
