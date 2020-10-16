<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Validator;
use Auth;
use App\Temp;
use App\Tag;

class TxtController extends Controller
{
	public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'tags' => 'sometimes|string'
        ]);

    	if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

    	if ($temp = Temp::where('user_id', Auth::guard('api')->user()->id)->count() == 0) {
        	// post data
	        try {
	            $temp = new Temp;
	            $temp->user_id = Auth::guard('api')->user()->id;
	            $data = [];
	            foreach ($request->all() as $key => $value) {      	
	            	$data[$key] = $value;
	            }

	            // proc tags
	            if (isset($request->tags)) {
        			$tag_id = [];
        			$tags = array_filter(explode("#", $request->tags));
        			foreach ($tags as $value) {
        				$value = trim($value);
        				if ($value == '') {
        					continue;
        				}

        				$tag = Tag::select('id')->where('name', $value)->first();
        				if ($tag == "") {
		                    $tag = new Tag;
		                    $tag->name = $value;
		                    $tag->save();
		                }
		                array_push($tag_id, $tag->id);
        			}
        			$data['tags'] = $tag_id;
				}

	            $temp->data = json_encode($data);
	            $temp->save();
	   			return json_encode(['success' => 'your posts has been successfully saved!']);
	        } catch(\Throwable $e) {
	            return json_encode(['fail' => $e->getMessage()]);
	        }
        } else {
        	// delete temp data
        	$deletedTemp = Temp::where('user_id', Auth::guard('api')->user()->id)->delete();
        	return json_encode(['failed' => 'this user still has a temperate  data, system will automatically remove temperate data']);
        }
    }
}