<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\User;
use Illuminate\Support\Facades\Auth; 
use Validator;
use Hash;

class UserController extends Controller
{
    /** 
     * login api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function login(Request $request)
    {
    	$validator = Validator::make($request->all(), [ 
            'email' => 'required|email', 
            'password' => 'required', 
        ]);

		if ($validator->fails()) { 
            return response()->json(['error' => $validator->errors()], 401);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) { 
            $user = Auth::user();
            $success['token'] = $user->createToken('authToken')->accessToken; 
            return response()->json(['success' => $success]);
        } else { 
            return response()->json(['error' => 'Unauthorised'], 401); 
        } 
    }

    // register
    public function register(Request $request) 
    { 
        $validator = Validator::make($request->all(), [ 
            'name' => 'required|max:100|unique:users,name', 
            'email' => 'required|email|max:255|unique:users,email', 
            'password' => 'required|min:6|max:255', 
            'c_password' => 'required|same:password',
        ]);

		if ($validator->fails()) { 
            return response()->json(['error' => $validator->errors()], 401);            
        }

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->saved = json_encode(['meme' => [], 'templates' => []]);
        $user->save();

        $success['token'] =  $user->createToken('MyApp')-> accessToken;
        $success['name'] =  $user->name;
		return response()->json(['success' => $success]); 
    }

    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'id' => 'required|string'
        ]);

        if ($validator->fails()) { 
            return response()->json(['error' => $validator->errors()], 401);            
        }

        $existingUser = User::where('email', $request->id)->first();

        if (! $existingUser) {
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->id;
            $user->password = Hash::make($request->id);
            $user->saved = json_encode(['meme' => [], 'templates' => []]);
            $user->save();
        }

        if (Auth::attempt(['email' => $request->id, 'password' => $request->id])) { 
            $user = Auth::user();
            $success['token'] = $user->createToken('authToken')->accessToken; 
            return response()->json(['success' => $success]);
        } else { 
            return response()->json(['error' => 'Unauthorised'], 401); 
        } 
    }
}
