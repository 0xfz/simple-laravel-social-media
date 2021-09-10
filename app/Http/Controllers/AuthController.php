<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    public function login(Request $request){
        /*
         *
         * Method for login a user
         * return Response
         * 
         */
        $validator = Validator::make($request->all(), [
            "e_username" => 'required',
            "password" => 'required'
        ]);
        if($validator->fails()){
            return response()->json(["error" => true,"msg" => $validator->messages()], 401);
        }
        $e_username = $request->e_username;
        $password = $request->password;
        if(filter_var($e_username, FILTER_VALIDATE_EMAIL)) {
            $token = Auth::attempt(['email' => $e_username, 'password' => $password]);
        } else {
            $token = Auth::attempt(['username' => $e_username, 'password' => $password]);
        }
        if(Auth::check()){
            return response()->json(["error" => false,"token" => $token], 200);
        }
        return response()->json(["error" => true,"msg" => "Can't Login"], 403);
    }
    public function register(Request $request){
        /*
         *
         * Method for register a user
         * return Response
         * 
         */
        $validator = Validator::make($request->all(), [
                "username" => 'required|unique:users|max:100',
                "email" => 'required|unique:users',
                "display_name" => "required",
                "password" => 'required',
                "c_password" => 'required'
        ]);
        if($validator->fails()){
            return response()->json(["error" => true,"msg" => $validator->messages()], 401);
        }
        $user = new User;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->display_name = $request->display_name;
        if(!empty($request->bio)){
            $user->bio = $request->bio;
        }
        if(strcmp($request->c_password, $request->password) == 0){
            $user->password = Hash::make($request->password);
            $user->save();
            return response()->json(
                ["error" => false, "msg" => "User successfully created"],200
            );
        }else{
            return response()->json(
                ["error" => true,"msg" => "Confirm password and password are not match"],401
            );
        }
    }
}
