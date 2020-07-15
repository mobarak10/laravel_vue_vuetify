<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\User;

class UserController extends Controller
{
    public function login(Request $request){
    	$userName = $request->email;
    	$password = $request->password;

    	$user = User::where('email', $userName)->where('password', $password)->first();

    	if ($user) {
    		$token = Hash::make($password);
	    	$user->api_token = $token;
	    	$user->save();
	    	return response()->json(['token' => $token], 200);
    	}

    	return response()->json(['status' => 'password or user-name is incorrect'], 403);
    }
}
