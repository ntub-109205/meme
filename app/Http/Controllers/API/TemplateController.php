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
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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
            'category_id' => ['required', Rule::In(['1', '2'])]
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        // search data
        try {
            $category = Category::find($request->category_id);
            $path = url('/images/templates/');
            $template = DB::select(
                "
                SELECT m.`template_id`, CONCAT('$path/', '$category->name/', t.`filelink`) AS `filelink`, t.`name`, COUNT(m.`template_id`) AS `count`
                FROM `meme` m
                INNER JOIN `templates` t
                ON m.`template_id` = t.`id`
                WHERE t.`category_id` = :category_id
                AND t.`share` = 1
                GROUP BY m.`template_id`, `filelink`, t.`name`
                ORDER BY COUNT(m.`template_id`) DESC
                ", ['category_id' => $request->category_id]
            );
        } catch(\Throwable $e) {
            return json_encode(['fail' => $e->getMessage()]);
        }

        return json_encode(['templates' => $template]);
    }

    public function store(Request $request)
    {
        // validate data
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', Rule::In(['1', '2'])],
            'name' => 'required|max:255',
            'share' => 'required|boolean',
            'image' => 'required|image'
        ]);

        if ($validator->fails()) {
            return json_encode(['failed' => 'post validation failed']);
        }

        // post data
        try {
            $template = new Template;
            $template->user_id = Auth::guard('api')->user()->id;
            $template->category_id = $request->category_id;
            $template->name = $request->name;
            $template->share = $request->share;
                // save image
            $image = $request->file('image');
            $filename = time().'.'.$image->extension();
            $template->filelink = $filename;
            $template->save();
            $category = Category::find($request->category_id);
            $location = public_path('images/templates/'.$category->name.'/'.$filename);
            Image::make($image)->save($location);
        } catch(\Throwable $e) {
            return json_encode(['fail' => $e->getMessage()]);
        }

        return json_encode(['success' => 'your posts has been successfully saved!']);
    }
}
