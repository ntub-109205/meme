<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use Auth;
use App\Temp;

class TxtController extends Controller
{
	public function __construct()
    {
        $this->middleware('auth:api');
    }

	public function templateStore(Request $request) {
		 // validate data
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['1', '2'])],
            'name' => 'required|max:255',
            'share' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        if ($temp = Temp::where('user_id', Auth::guard('api')->user()->id)->count() == 0) {
        	// post data
	        try {
	            $temp = new Temp;
	            $temp->user_id = Auth::guard('api')->user()->id;
	            $temp->data = json_encode(
	        		[
	            		'category_id' => $request->category_id,
	            		'name' => $request->name,
	            		'share' => $request->share
	            	]
	            );
	            $temp->save();
	   			return json_encode(['success' => 'your posts has been successfully saved!']);
	        } catch(\Throwable $e) {
	            return json_encode(['fail' => $e->getMessage()]);
	        }
        } else {
        	return json_encode(['failed' => 'this user still has a temperate  data']);
        }
	}
}