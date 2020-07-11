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

	public function templateStore(Request $request) {
		 // validate data
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['1', '2'])],
            'name' => 'required|max:255',
            'share' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
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
        	// delete temp data
        	$deletedTemp = Temp::where('user_id', Auth::guard('api')->user()->id)->delete();
        	return json_encode(['failed' => 'this user still has a temperate  data, system will automatically remove temperate data']);

        }
	}

	public function memeStore(Request $request) {
		if ($temp = Temp::where('user_id', Auth::guard('api')->user()->id)->count() == 0) {
			$validator = Validator::make($request->all(), [
	            'template_id' => 'numeric|required',
	            'meme_share' => 'required|boolean',
	            'tags' => 'required|array'
	        ]);
	        if ($validator->fails()) {
            	return json_encode(['failed' => $validator->errors()]);
        	}

        	DB::beginTransaction();
        	try {
        		$tag_id = [];
	            for ($i = 0; $i < count($request->tags); $i++) {
	                $tag = Tag::select('id')->where('name', $request->tags[$i])->first();
	                if ($tag == "") {
	                    $tag = new Tag;
	                    $tag->name = $request->tags[$i];
	                    $tag->save();
	                }
	                array_push($tag_id, $tag->id);
	            }

	            $temp = new Temp;
	            $temp->user_id = Auth::guard('api')->user()->id;
	            $temp->data = json_encode(
	        		[
	            		'template_id' => $request->template_id,
	            		'meme_share' => $request->meme_share,
	            		'tags' => $tag_id
	            	]
	            );
	            $temp->save();
        		DB::commit();
        		return json_encode(['success' => 'your posts has been successfully saved!']);
        	} catch (\Throwable $e) {
				DB::rollback();
				return json_encode(['failed' => $e->getMessage()]);
        	}
	    } else {
	    	// delete temp data
        	$deletedTemp = Temp::where('user_id', Auth::guard('api')->user()->id)->delete();
	    	return json_encode(['failed' => 'this user still has a temperate  data, system will automatically remove temperate data']);
	    }
	}
}