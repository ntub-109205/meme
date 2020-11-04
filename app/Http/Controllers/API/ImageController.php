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
        $user = Auth::guard('api')->user()->id;

        if ($temp = Temp::where('user_id', $user)->count() == 0) {
            return json_encode(['failed' => 'there has no template data']);
        }

        // validate data
        $validator = Validator::make($request->all(), [
            'image' => 'required|image',
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        $image = $request->file('image');
        $temp = Temp::select('data')->where('user_id', $user)->first();
        $data = json_decode($temp->data);
        
        if (strtolower($image->extension()) == 'gif') {
            return $this->storeGif($request, $data, $user);
        }

        if (! (isset($data->template_id) && isset($data->share))) {
            Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => 'not enough data for meme store, make sure definition `template_id` and `share` field']);
        }

        $request->merge([
            'template_id' => $data->template_id,
            'share' => $data->share,
            'tags' => $data->tags ?? [],
        ]);

        $validator = Validator::make($request->all(), [
            'template_id' => 'required|numeric',
            'share' => 'required|boolean',
            'tags' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => $validator->errors()]);
        }

        DB::beginTransaction();
        try {
            // post meme data
            $meme = new Meme;
            $meme->user_id = $user;
            $meme->template_id = $request->template_id;
            $meme->share = $request->share;
                // save image
            $filename = time().'.'.strtolower($image->extension());
            $template = Template::find($request->template_id);
            $category = Category::find($template->category_id);
            $path = 'images/meme/'.$category->name.'/'.$filename;
            $location = public_path($path);
            Image::make($image)->save($location);
            $meme->filelink = $path;
            $meme->save();
                // many to many
            if (! empty($request->tags)) {
                $meme->tags()->sync($request->tags, false);
            }
            // delete temp data 
            $deletedTemp = Temp::where('user_id', $user)->delete();
            DB::commit();
            return json_encode(['success' => 'your posts has been successfully saved!']);
        } catch(\Throwable $e) {
            DB::rollback();
            // delete temp data 
            $deletedTemp = Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => $e->getMessage()]);
        }
    }

    public function storeGif(Request $request, $data, $user)
    {
        if (! isset($data->share)) {
            Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => 'not enough data for gif store, make sure definition `share` field']);
        }

        $request->merge([
            'share' => $data->share,
            'tags' => $data->tags ?? [],
        ]);

        $validator = Validator::make($request->all(), [
            'share' => 'required|boolean',
            'tags' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => $validator->errors()]);
        }

        $image = $request->file('image');
        DB::beginTransaction();
        try {
            // post gif template data
            $template = new Template;
            $template->user_id = $user;
            $category = Category::where('name', strtolower($image->extension()))->first();
            $template->category_id = $category->id;
            // -- store gif image --
            $filename = time().'.'.$image->extension();
            $path = 'images/meme/'.$category->name.'/';
            $image->move(public_path($path), $filename);
            $path .= $filename;
            // ---------------------
            $template->filelink = $path;
            $template->name = 'gif template';
            $template->share = 0;
            $template->save();

            // post meme data
            $meme = new Meme;
            $meme->user_id = $user;
            $meme->template_id = $template->id;
            $meme->share = $request->share;
            $meme->filelink = $path;
            $meme->save();
                // many to many
            if (! empty($request->tags)) {
                $meme->tags()->sync($request->tags, false);
            }
            // delete temp data 
            $deletedTemp = Temp::where('user_id', $user)->delete();
            DB::commit();
            return json_encode(['success' => 'your posts has been successfully saved!']);
        } catch(\Throwable $e) {
            DB::rollback();
            // delete temp data 
            $deletedTemp = Temp::where('user_id', $user)->delete();
            return json_encode(['failed' => $e->getMessage()]);
        }
    }

    public function show(Request $request, $category_id)
    {
        $request->merge([
            'category_id' => $category_id,
        ]);

    	$validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['1', '2', '3'])],
            'time' => 'sometimes|boolean',
            'profile' => ['sometimes', Rule::In(['saved', 'myWork'])],
            'tag_name' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        try {
            $path = url('/');
            $user = Auth::guard('api')->user();
            $param = [
                'user_id' => $user->id,
                'category_id' => $request->category_id
            ];

            $query = "
                SELECT DISTINCT m.`id` AS `meme_id`, CONCAT('$path/', m.`filelink`) AS `filelink`, u.`name` AS `author`, m.`template_id`, t.`share` AS `template_share`, m.`share` AS `meme_share`,
                (SELECT COUNT(*) FROM `meme_user` mu WHERE mu.`meme_id` = m.`id`) AS `count`, 
                (SELECT COUNT(*) FROM `meme_user` mu WHERE mu.`user_id` = :user_id AND mu.`meme_id` = m.`id`) AS `thumb`, m.`created_at`
                FROM `meme` m
                INNER JOIN `templates` t
                ON m.`template_id` = t.`id`
                INNER JOIN `category` c
                ON t.`category_id` = c.`id`
                INNER JOIN `users` u
                ON m.`user_id` = u.`id`
                LEFT JOIN `meme_tag` mt
                ON m.`id` = mt.`meme_id`
                LEFT JOIN `tags` ta
                ON ta.`id` = mt.`tag_id`
                WHERE c.`id` = :category_id 
                ";
            
            // profile
            if ($request->profile == 'myWork') {   
                $query .= "AND u.`id` = :profile ";
                $param['profile'] = $user->id;
            } else if ($request->profile == 'saved') {
                $data = $user->saved;
                $data = json_decode($data, 1); //array
                $saved = [];
                foreach ($data['meme'] as $id => $timestamp) {
                    array_push($saved, $id);
                }
                $saved = implode(',', $saved);
                if (empty($saved)) {
                    return json_encode(['meme' => []]);
                }
                $query .= "AND m.`id` IN (".$saved.") AND m.`share` = 1 ";
            } else {
                $query .= "AND m.`share` = 1 ";
            }

            // tag name
            if (isset($request->tag_name)) {
                $query .= "AND ta.`name` LIKE :name ";
                $param['name'] = "%".$request->tag_name."%";
            }

            // time 
            $request->time ? $query .= "ORDER BY m.`created_at` DESC" : $query .= "ORDER BY `count` DESC";
            $info = DB::select($query, $param);

            // add tags
            foreach ($info as $key => $value) {
                $tags = Meme::find($value->meme_id)->tags()->pluck('name');
                $info[$key] = Arr::add((array)$info[$key], 'tags', $tags);
            }

            return json_encode(['meme' => $info]);
        } catch(\Throwable $e) {
            return json_encode(['fail' => $e->getMessage()]);
        }
    }

    public function savedStatus($id)
    {
        try {
            if (Meme::find($id)->count() != 0) {
                $saved = json_decode(Auth::guard('api')->user()->saved, true);
                if (Arr::exists($saved['meme'], $id)) {
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
                    $saved['meme'] = Arr::add($saved['meme'], $request->meme_id, date("Y-m-d H:i:s"));
                    $user->saved = json_encode($saved);
                    $user->save();
                    return json_encode(['saved' => '1']);      
                }
            }  
        } catch(\Throwable $e) {
            return json_encode(['failed' => $e->getMessage()]);
        }   
    }

    public function thumb(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'meme_id' => 'required|numeric',
        ]);
        
        if ($validator->fails()) {
            return json_encode(['failed' => $validator->errors()]);
        }

        try {
            $thumb = Auth::guard('api')->user()->thumb_meme();
            if ($thumb->where('meme_id', $request->meme_id)->count()) {
                $thumb->detach($request->meme_id);
                return json_encode(['thumb' => '0']);
            } else {
                $thumb->sync($request->meme_id, false);
                return json_encode(['thumb' => '1']);
            }
            
        } catch(\Throwable $e) {
            return json_encode(['failed' => $e->getMessage()]);
        }
    }
}
