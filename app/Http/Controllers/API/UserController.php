<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use App\User; 
use Illuminate\Support\Facades\Auth; 
use Validator;

class UserController extends Controller
{
	/** 
     * login api 
     * 
     * @return \Illuminate\Http\Response 
     */ 
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [ 
            'email' => 'required|email', 
            'password' => 'required', 
        ]);

		if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) { 
            $user = Auth::user(); 
            $success['token'] = $user->createToken('authToken')->accessToken; 
            return response()->json(['success' => $success]);
        } else { 
            return response()->json(['error'=>'Unauthorised'], 401); 
        } 
    }
	/** 
     * Register api 
     * 
     * @return \Illuminate\Http\Response 
     */ 

	public function details() 
    { 
        $user = Auth::user(); 
        // return response()->json(['success' => $user]);
        echo $user;
    }

    // register
    public function register(Request $request) 
    { 
        $validator = Validator::make($request->all(), [ 
            'name' => 'required', 
            'email' => 'required|email', 
            'password' => 'required', 
            //'password' => 'required|confirmed' 待研究, 
            'c_password' => 'required|same:password', 
        ]);
		if ($validator->fails()) { 
            return response()->json(['error'=>$validator->errors()], 401);            
        }
		$input = $request->all(); 
        $input['password'] = bcrypt($input['password']); 
        $user = User::create($input); 
        $success['token'] =  $user->createToken('MyApp')-> accessToken; 
        $success['name'] =  $user->name;
		return response()->json(['success'=>$success], $this-> successStatus); 
    }
}
