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

        // 存在回傳 1(true)
        if (isset($data->template_id)) {
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
        } else {
            $validator = Validator::make($request->all(), [
                'meme_image' => 'required|image',
                'template_image' => 'required|image',
            ]);
            if ($validator->fails()) {
                return json_encode(['failed' => 'post validation failed']);
            }
            
            DB::beginTransaction();
            try {
                // post tags data
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
                // post template data
                $template = new Template;
                $template->user_id = Auth::guard('api')->user()->id;
                $template->category_id = $request->category_id;
                $template->name = $request->template_name;
                $template->share = $request->template_share;
                    // save image
                $template_image = $request->file('template_image');
                $filename = time().'.'.$template_image->extension();
                $template->filelink = $filename;
                $template->save();

                // post meme data
                $meme = new Meme;
                $meme->user_id = Auth::guard('api')->user()->id;
                $meme->template_id = $template->id;
                $meme->share = $request->meme_share;
                    // save image
                $meme_image = $request->file('meme_image');
                $filename = time().'.'.$meme_image->extension();
                $meme->filelink = $filename;
                $meme->save();

                $meme->tags()->sync($tag_id, false);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollback();
                return json_encode(['failed' => $e->getMessage()]);
            }

            $category = Category::find($request->category_id);
            $location = public_path('images/templates/'.$category->name.'/'.$filename);
            Image::make($template_image)->save($location);
            $location = public_path('images/meme/'.$category->name.'/'.$filename);
            Image::make($meme_image)->save($location);

            return json_encode(['success' => 'your posts has been successfully saved!']);
        }
    }
}
