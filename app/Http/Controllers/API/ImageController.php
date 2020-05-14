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

class ImageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {     
        // 存在回傳 1(true)
        if (isset($request->template_id)) {
            // validate data
            $validator = Validator::make($request->all(), [
                'template_id' => 'numeric|required',
                // 缺tag
                'meme_image' => 'required|image',
                'meme_share' => 'required|boolean'
            ]);
            if ($validator->fails()) {
                return json_encode(['failed' => 'post validation failed']);
            }

            // post meme data
            $meme = new Meme;
            $meme->user_id = Auth::guard('api')->user()->id;
            $meme->template_id = $request->template_id;
            $meme->share = $request->meme_share;
                // save image
            $image = $request->file('meme_image');
            $filename = time().'.'.$image->extension();
            $meme->filelink = $filename;
            try {
                $meme->save();
                $template = Template::find($request->template_id);
                $category = Category::find($template->category_id);
                $location = public_path('images/meme/'.$category->name.'/'.$filename);
                Image::make($image)->save($location);
            } catch(\Throwable $e) {
                return json_encode(['fail' => $e->getMessage()]);
            }

            return json_encode(['success' => 'your posts has been successfully saved!']);
        } else {
            $validator = Validator::make($request->all(), [
                // 缺tag
                'meme_image' => 'required|image',
                'meme_share' => 'required|boolean',
                'category_id' => ['required', Rule::In(['1', '2'])],
                'template_name' => 'required|max:255',
                'template_share' => 'required|boolean',
                'template_image' => 'required|image',
                'tags' => 'required|array'
            ]);
            if ($validator->fails()) {
                return json_encode(['failed' => 'post validation failed']);
            }
            
            /*DB::beginTransaction();
            try {
                
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
                */
                // post tags data
                for ($i = 0; $i < count($request->tags); $i++) {                 
                    DB::statement("
                        INSERT INTO `tags`(`name`) SELECT :name FROM `tags`
                        WHERE NOT EXISTS(
                            SELECT `name` FROM `tags`
                            WHERE `name` = ':name'
                        )", ['name' => $request->tags[$i]]
                    );
                }
                
                echo "hi";
                //$tags = Tag::select('id')->where('name', $request->tags);
                //print_r($tags);

                /*
                $post->tags()->sync($request->tags, false);
                $meme->tags()->sync()

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollback();
                return json_encode(['fail' => $e->getMessage()]);
            }

            $category = Category::find($request->category_id);
            $location = public_path('images/templates/'.$category->name.'/'.$filename);
            Image::make($template_image)->save($location);
            $location = public_path('images/meme/'.$category->name.'/'.$filename);
            Image::make($meme_image)->save($location);

            return json_encode(['success' => 'your posts has been successfully saved!']);
            */
            
        }
    }
}
