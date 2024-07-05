<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sankhya\SankhyaController;
use Illuminate\Http\Request;

class AuthorizationController extends SankhyaController
{
    public function AuthorizationUser(Request $request)
    {
        $params = $request->only(['user', 'password']);
        $result = $this->autenthicate($params);

        return response()->json($result);
    }
}
