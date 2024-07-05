<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sankhya\SankhyaController;
use Illuminate\Http\Request;

class LogoutController extends SankhyaController
{
    public function logout(Request $request)
    {
        $result = $this->deAutenthicate();

        return $result;
    }
}
