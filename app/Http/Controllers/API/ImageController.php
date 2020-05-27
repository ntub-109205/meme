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
            // $meme->tags()->sync($data->tags, false);
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

    public function show(Request $request) {

    }
}
