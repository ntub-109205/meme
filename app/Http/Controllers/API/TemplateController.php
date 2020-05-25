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

class TemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function show(Request $request)
    {
        // validate data
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['1', '2'])],
            'time' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        if (!isset($request->time)) {
            try {
                $category = Category::find($request->category_id);
                $path = url('/images/templates/');
                $template = DB::select(
                    "
                    SELECT t.`id`, CONCAT('$path/', '$category->name/', t.`filelink`) AS `filelink`, t.`name`, u.`name` AS `author`, COUNT(m.`template_id`) AS `count`
                    FROM `templates` t
                    INNER JOIN `users` u
                    ON t.`user_id` = u.`id`
                    LEFT JOIN `meme` m
                    ON t.`id` = m.`template_id`
                    WHERE t.`category_id` = :category_id
                    AND t.`share` = 1
                    GROUP BY t.`id`, `filelink`, t.`name`, u.`name`
                    ORDER BY COUNT(m.`template_id`) DESC
                    ", ['category_id' => $request->category_id]
                );
            } catch(\Throwable $e) {
                return json_encode(['fail' => $e->getMessage()]);
            }
        } else {
            try {
                $category = Category::find($request->category_id);
                $path = url('/images/templates/');
                $template = DB::select(
                    "
                    SELECT t.`id`, CONCAT('$path/', '$category->name/', t.`filelink`) AS `filelink`, t.`name`, u.`name` AS `author`, COUNT(m.`template_id`) AS `count`, t.`created_at`
                    FROM `templates` t
                    INNER JOIN `users` u
                    ON t.`user_id` = u.`id`
                    LEFT JOIN `meme` m
                    ON t.`id` = m.`template_id`
                    WHERE t.`category_id` = :category_id
                    AND t.`share` = 1
                    GROUP BY t.`id`, `filelink`, t.`name`, u.`name`, t.`created_at`
                    ORDER BY t.`created_at` DESC
                    ", ['category_id' => $request->category_id]
                );
            } catch(\Throwable $e) {
                return json_encode(['fail' => $e->getMessage()]);
            }
        }
        return json_encode(['templates' => $template]);
    }

    public function store(Request $request)
    {
        // validate data
        $validator = Validator::make($request->all(), [
            'image' => 'required|image'
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        if ($temp = Temp::where('user_id', Auth::guard('api')->user()->id)->count() == 0) {
            return json_encode(['failed' => 'there has no template data']);
        }

        // post data
        $temp = Temp::select('data')->where('user_id', Auth::guard('api')->user()->id)->first();
        $data = json_decode($temp->data);
        DB::beginTransaction();
        try {
            $template = new Template;
            $template->user_id = Auth::guard('api')->user()->id;
            $template->category_id = $data->category_id;
            $template->name = $data->name;
            $template->share = $data->share;
                // save image
            $image = $request->file('image');
            $filename = time().'.'.$image->extension();
            $template->filelink = $filename;
            $template->save();
            $category = Category::find($data->category_id);
            $location = public_path('images/templates/'.$category->name.'/'.$filename);
            Image::make($image)->save($location);

                // delete temp data 
            $deletedTemp = Temp::where('user_id', Auth::guard('api')->user()->id)->delete();
            DB::commit();
        } catch(\Throwable $e) {
            DB::rollback();
            // delete temp data 
            $deletedTemp = Temp::where('user_id', Auth::guard('api')->user()->id)->delete();
            return json_encode(['failed' => $e->getMessage()]);
        }

        return json_encode(['success' => 'your posts has been successfully saved!']);
    }

    public function savedStatus(Request $request) {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|numeric',
        ]);
        
        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        try {
            if (Template::find($request->template_id)->count() != 0) {
                $saved = json_decode(Auth::guard('api')->user()->saved, true);
                if (Arr::exists($saved['templates'], $request->template_id)) {
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
            'template_id' => 'required|numeric',
        ]);
        
        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        $user = Auth::guard('api')->user();
        $saved = json_decode($user->saved, true);

        // 驗證狀態
        $status = 0;
        try {
            if (Template::find($request->template_id)->count() != 0) {
                $saved = json_decode(Auth::guard('api')->user()->saved, true);
                if (Arr::exists($saved['templates'], $request->template_id)) {
                    $status = 1;  
                }
                if ($status) {
                    try {
                        unset($saved['templates'][$request->template_id]);
                        $user->saved = json_encode($saved);
                        $user->save();
                        return json_encode(['saved' => '0']);
                    } catch(\Throwable $e) {
                        return json_encode(['failed' => $e->getMessage()]);
                    }
                } else {
                    $saved['templates'] = Arr::add($saved['templates'], $request->template_id, '1');
                    $user->saved = json_encode($saved);
                    $user->save();
                    return json_encode(['saved' => '1']);      
                }
            }  
        } catch(\Throwable $e) {
            return json_encode(['failed' => $e->getMessage()]);
        }
        
    }

    public function ref(Request $request) {

    }

}
