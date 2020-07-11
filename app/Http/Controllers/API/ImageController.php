<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Image;
use Storage;
use Auth;
use App\Template;
use App\Category;
use App\Meme;
use App\Tag;
use App\Temp;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {    
        if ($temp = Temp::where('user_id', Auth::guard('api')->user()->id)->count() == 0) {
            return json_encode(['failed' => 'there has no template data']);
        }
        $temp = Temp::select('data')->where('user_id', Auth::guard('api')->user()->id)->first();
        $data = json_decode($temp->data);
        // validate data
        $validator = Validator::make($request->all(), [
            'meme_image' => 'required|image',
        ]);
        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        DB::beginTransaction();
        try {
            // post meme data
            $meme = new Meme;
            $meme->user_id = Auth::guard('api')->user()->id;
            $meme->template_id = $data->template_id;
            $meme->share = $data->meme_share;
                // save image
            $image = $request->file('meme_image');
            $filename = time().'.'.$image->extension();
            $meme->filelink = $filename;
            $meme->save();
                // many to many
            $meme->tags()->sync($data->tags, false);
            $template = Template::find($data->template_id);
            $category = Category::find($template->category_id);
            $location = public_path('images/meme/'.$category->name.'/'.$filename);
            Image::make($image)->save($location);

            // delete temp data 
            $deletedTemp = Temp::where('user_id', Auth::guard('api')->user()->id)->delete();
            DB::commit();
            return json_encode(['success' => 'your posts has been successfully saved!']);
        } catch(\Throwable $e) {
            DB::rollback();
            // delete temp data 
            $deletedTemp = Temp::where('user_id', Auth::guard('api')->user()->id)->delete();
            return json_encode(['failed' => $e->getMessage()]);
        }
    }

    public function info(Request $request) {
    	$validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['1', '2'])],
            'time' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        try {
            $category = Category::find($request->category_id);
            $path = url('/images/meme/');
            $info = DB::select(
                "
                SELECT m.`id` AS `meme_id`, CONCAT('$path/', '$category->name/', m.`filelink`) AS `filelink`, u.`name` AS `author`, m.`template_id` AS `template_id`
                FROM `meme` m
				INNER JOIN `templates` t
				ON m.`template_id` = t.`id`
				INNER JOIN `category` c
				ON t.`category_id` = c.`id`
				INNER JOIN `users` u
				ON m.`user_id` = u.`id`
				WHERE c.`id` = :category_id
				AND m.`share` = 1
				AND t.`share` = 1
				ORDER BY m.`created_at` DESC
                ", ['category_id' => $request->category_id]
            );
            return json_encode(['info' => $info]);
        } catch(\Throwable $e) {
            return json_encode(['fail' => $e->getMessage()]);
        }
    }

    public function savedStatus(Request $request) {
        $validator = Validator::make($request->all(), [
            'meme_id' => 'required|numeric'
        ]);
        
        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        try {
            if (Meme::find($request->meme_id)->count() != 0) {
                $saved = json_decode(Auth::guard('api')->user()->saved, true);
                if (Arr::exists($saved['meme'], $request->meme_id)) {
                    return json_encode(['saved' => '1']);   
                }
                return json_encode(['saved' => '0']);
            }  
        } catch(\Throwable $e) {
            return json_encode(['failed' => $e->getMessage()]);
        } 
    }

    public function saved(Request $request) { 
        $validator = Validator::make($request->all(), [
            'meme_id' => 'required|numeric',
        ]);
        
        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        $user = Auth::guard('api')->user();
        $saved = json_decode($user->saved, true);

        // 驗證狀態
        $status = 0;
        try {
            if (Meme::find($request->meme_id)->count() != 0) {
                $saved = json_decode(Auth::guard('api')->user()->saved, true);
                if (Arr::exists($saved['meme'], $request->meme_id)) {
                    $status = 1;  
                }
                if ($status) {
                    try {
                        unset($saved['meme'][$request->meme_id]);
                        $user->saved = json_encode($saved);
                        $user->save();
                        return json_encode(['saved' => '0']);
                    } catch(\Throwable $e) {
                        return json_encode(['failed' => $e->getMessage()]);
                    }
                } else {
                    $saved['meme'] = Arr::add($saved['meme'], $request->meme_id, '1');
                    $user->saved = json_encode($saved);
                    $user->save();
                    return json_encode(['saved' => '1']);      
                }
            }  
        } catch(\Throwable $e) {
            return json_encode(['failed' => $e->getMessage()]);
        }   
    }
}
