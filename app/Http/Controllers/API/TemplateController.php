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
use App\User;

class TemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function show(Request $request, $category_id)
    {
        $request->merge([
            'category_id' => $category_id,
        ]);

        // validate data
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['0', '1', '2'])],
            'time' => 'sometimes|boolean',
            'user' => 'sometimes|boolean',
            'limit' => 'sometimes|numeric',
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        if ($request->category_id == '0') {
            $request->category_id = '%';
        }

        try {
            $param = ['category_id' => $request->category_id];
            $path = url('/');
            $query = "
                SELECT t.`id`, CONCAT('$path/', t.`filelink`) AS `filelink`, t.`name`, u.`name` AS `author`, COUNT(m.`template_id`) AS `count`, t.`created_at`, t.`share`
                FROM `templates` t
                INNER JOIN `users` u
                ON t.`user_id` = u.`id`
                LEFT JOIN `meme` m
                ON t.`id` = m.`template_id`
                WHERE t.`category_id` LIKE :category_id
                ";
            if ($request->user) {   
                $query .= "AND u.`id` = :user ";
                $param['user'] = Auth::guard('api')->user()->id;
            } else {
                $query .= "AND t.`share` = 1 ";
            } 
            $query .= "GROUP BY t.`id`, t.`filelink`, t.`name`, u.`name`, t.`created_at`, t.`share` ";
            $request->time ? $query .= "ORDER BY t.`created_at` DESC " : $query .= "ORDER BY COUNT(m.`template_id`) DESC ";
            if ($request->limit) {
                $query .= "LIMIT :limit ";
                $param['limit'] = $request->limit;
            }

            $template = DB::select($query, $param);
        } catch(\Throwable $e) {
            return json_encode(['fail' => $e->getMessage()]);
        }
        return json_encode(['templates' => $template]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('api')->user()->id;

        if ($temp = Temp::where('user_id', $user)->count() == 0) {
            return json_encode(['failed' => 'there has no template data']);
        }

        $temp = Temp::select('data')->where('user_id', $user)->first();
        $data = json_decode($temp->data);
        if (! (isset($data->category_id) && isset($data->name) && isset($data->share))) {
            Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => 'not enough data for template store, make sure definition `category_id`, `name` and `share` field']);
        }

        $request->merge([
            'category_id' => $data->category_id,
            'name' => $data->name,
            'share' => $data->share
        ]);

        // validate data
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['1', '2'])],
            'name' => 'required|max:255',
            'share' => 'required|boolean',
            'image' => 'required|image'
        ]);

        if ($validator->fails()) {
            Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => $validator->errors()]);
        }

        // post data
        DB::beginTransaction();
        try {
            $template = new Template;
            $template->user_id = $user;
            $template->category_id = $request->category_id;
            $template->name = $request->name;
            $template->share = $request->share;
                // save image
            $image = $request->file('image');
            $filename = time().'.'.strtolower($image->extension());
            $category = Category::find($request->category_id);
            $path = 'images/templates/'.$category->name.'/'.$filename;
            $location = public_path($path);
            $template->filelink = $path;
            Image::make($image)->save($location);
            $template->save();

                // delete temp data 
            $deletedTemp = Temp::where('user_id', $user)->delete();
            DB::commit();
            return json_encode([
                'success' => 'your posts has been successfully saved!',
                'template_id' => $template->id
            ]);
        } catch(\Throwable $e) {
            DB::rollback();
            // delete temp data 
            $deletedTemp = Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => $e->getMessage()]);
        }
    }

    public function savedStatus($template_id)
    {
        try {
            if (Template::find($template_id)->count() != 0) {
                $saved = json_decode(Auth::guard('api')->user()->saved, true);
                if (Arr::exists($saved['templates'], $template_id)) {
                    return json_encode(['saved' => '1']);   
                }
                return json_encode(['saved' => '0']);
            }  
        } catch(\Throwable $e) {
            return json_encode(['failed' => $e->getMessage()]);
        } 
    }

    public function saved(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|numeric',
        ]);
        
        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
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
                    $saved['templates'] = Arr::add($saved['templates'], $request->template_id, date("Y-m-d H:i:s"));
                    $user->saved = json_encode($saved);
                    $user->save();
                    return json_encode(['saved' => '1']);      
                }
            }  
        } catch(\Throwable $e) {
            return json_encode(['failed' => $e->getMessage()]);
        }
        
    }

    public function meme(Request $request, $template_id)
    {
        $validator = Validator::make($request->all(), [
            'exclude' => 'sometimes|numeric' //搜尋不含此meme_id
        ]);
        
        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        try {
            $path = url('/');

            // count該圖的讚數、thumb使用者有沒有按讚
            $query = "
                SELECT m.`id`, CONCAT('$path/', m.`filelink`) AS `filelink`, u.`name` AS `author`, 
                (SELECT COUNT(*) FROM `meme_user` mu WHERE mu.`meme_id` = m.`id`) AS `count`, 
                (SELECT COUNT(*) FROM `meme_user` mu WHERE mu.`user_id` = :user_id AND mu.`meme_id` = m.`id`) AS `thumb`
                FROM `meme` m
                INNER JOIN `templates` t
                ON m.`template_id` = t.`id`
                INNER JOIN `users` u
                ON m.`user_id` = u.`id`
                WHERE m.`template_id` = :template_id
                AND m.`share` = 1
                AND t.`share` = 1
                ";
            $param = [
                'user_id' => Auth::guard('api')->user()->id,
                'template_id' => $request->template_id
            ];

            if (isset($request->exclude)) {
                $query .= "AND m.`id` != :meme_id ORDER BY `count` DESC";
                $param['meme_id'] = $request->exclude;  
            } else {
                $query .= "ORDER BY `count` DESC";
            }

            $meme = DB::select($query, $param);

            // add tags
            foreach ($meme as $key => $value) {
                $tags = Meme::find($value->id)->tags()->pluck('name');
                $meme[$key] = Arr::add((array)$meme[$key], 'tags', $tags);
            }
            
            return json_encode(['meme' => $meme]);
        } catch(\Throwable $e) {
            return json_encode(['fail' => $e->getMessage()]);
        } 
    }
}
