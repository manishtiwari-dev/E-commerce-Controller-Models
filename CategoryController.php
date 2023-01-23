<?php
namespace Modules\Ecommerce\Http\Controllers;

use ApiHelper;
use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Ecommerce\Models\Category;
use Modules\Ecommerce\Models\CategoryDescription;
use Modules\Ecommerce\Models\SeoMeta;

class CategoryController extends Controller
{

    public $page = 'categories';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create_sub_category(Request $request)
    {
        $language = $request->language;

        $res = [];

        $cat = Category::where('parent_id', 0)->where('source',1)->get();
        $cat = $cat->map(function ($data) use ($language) {
            $cate = $data->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
            $data->category_name = ($cate == null) ? '' : $cate->categories_name;

            // getting sub category
            $sub_category = Category::where('parent_id', $data->categories_id)->get();
            if (sizeof($sub_category) > 0) {
                $sub_category = $sub_category->map(function ($sub) use ($language) {
                    $subcat = $sub->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
                    $sub->category_name = ($subcat == null) ? '' : $subcat->categories_name;
                    return $sub;
                });
            }
            $data->sub_category = $sub_category;

            return $data;
        });
        $res['category'] = $cat;

        // get only selected lang from setting
        $res['language'] = ApiHelper::allSupportLang();

        return ApiHelper::JSON_RESPONSE(true, $res, '');

    }

    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? (int) $request->perPage : ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $language = $request->language;
        $catId = $request->catId;

        $data_query = Category::with('categorydescription')->where('parent_id', $request->has('catId') ? $catId : 0)->where('source',1);

        // attaching query filter by permission(all, added,owned,both)
        $data_query = ApiHelper::attach_query_permission_filter($data_query, $api_token, $this->page, $this->pageview);
          
        // $sub_category = Category::where('parent_id',  $request->has('catId') ? $catId : 0)->get();

        // if(!empty( $sub_category)){
        if (!empty($search)) {
            $data_query = $data_query->where("categories_slug", "LIKE", "%{$search}%")
                ->orWhereHas('categorydescription', function ($data_query) use ($search) {
                    $data_query->where("categories_name", "LIKE", "%{$search}%");
                });
        }
   // }

        // $data_query = $data_query->where("name","LIKE", "%{$search}%")->orWhere("email", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $data_query = $data_query->orderBy('sort_order', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int) ($current_page - 1) * $perPage;

        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();

        if (!empty($data_list)) {
            $data_list = $data_list->map(function ($data) use ($language) {

                $cate = $data->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();

                if (!empty($cate->categories_name)) {
                    $data->category_name = $cate->categories_name;
                }

                // $data->description = ($cate == null) ? '' : $cate->categories_description;
                $data->status = ($data->status == 1) ? "active" : "deactive";
                $data->categories_image = ApiHelper::getFullImageUrl($data->categories_image);

                // getting sub category
                $sub_category = Category::where('parent_id', $data->categories_id)->get();
                if (!empty($sub_category)) {
                    if (sizeof($sub_category) > 0) {
                        $sub_category = $sub_category->map(function ($sub) use ($language) {

                            $subcat = $sub->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
                            if (!empty($subcat->categories_name)) {
                                $sub->category_name = $subcat->categories_name;
                            }

                            $sub->status = ($sub->status == 1) ? "active" : "deactive";
                            $sub->categories_image = ApiHelper::getFullImageUrl($sub->categories_image);

                            // getting sub sub category
                            $sub_sub_category = Category::where('parent_id', $sub->categories_id)->get();
                            if (sizeof($sub_sub_category) > 0) {
                                $sub_sub_category = $sub_sub_category->map(function ($sub) use ($language) {
                                    $subcat = $sub->categorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
                                    $sub->category_name = ($subcat == null) ? '' : $subcat->categories_name;
                                    $sub->status = ($sub->status == 1) ? "active" : "deactive";
                                    $sub->categories_image = ApiHelper::getFullImageUrl($sub->categories_image);
                                    return $sub;
                                });
                            }
                            $sub->sub_sub_category = $sub_sub_category;

                            return $sub;
                        });
                    }
                }
                $data->sub_category = $sub_category;

                return $data;
            });
        }

        $cName = '';

        if ($request->has('catId')) {
            //getting category Name
            $catName = CategoryDescription::where('categories_id', $request->catId)->where('languages_id', ApiHelper::getLangid($language))->first();
            $cName = !empty($catName) ? $catName->categories_name : '';
        }

        $res = [
            'data' => $data_list,
            'current_page' => $current_page,
            'total_records' => $user_count,
            'cat_name' => $cName,
            'total_page' => ceil((int) $user_count / (int) $perPage),
            'per_page' => $perPage,
        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        //     $requestAll = $request->all();

        //     // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        //     //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        //         $id = isset($requestAll['categories_description_id']) ? $requestAll['categories_description_id'] : 'NULL'; //with PHP7 it could be $id = $requestAll['id'] ?? 'NULL';
        //             $categoryId = $requestAll['categories_id']; //i don't know if it exists in your request.
        //             $rules = [
        //          'categories_name' => 'required|unique:ecm_categories_description,categories_name,'.$id.',categories_description_id,category_id,'.$categoryId,

        //             ];
        //   // return Validator::make($requestAll, $rules);

        //    //  validation check
        //     $rules = [
        //       //  'categories_name_' => 'required|unique:ecm_categories_description',

        //     ];
        //     $validator = Validator::make($request->all(), $rules);
        //     if ($validator->fails()) {
        //         return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        //     }

        //         $validator = Validator::make($request->all(),[
        //             'categories_name' => 'required|unique:ecm_categories_description,categories_name,'.$id.',categories_description_id,category_id,'.$categoryId,

        //         ],[
        //             'categories_name.required'=>'CATEGORIES_NAME_UNIQUE_REQUIRED',

        //         ]);

        // if ($validator->fails())
        //     return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());



            //validation

            foreach ( ApiHelper::allSupportLang() as $key => $value) {

                                    
                //validation check 
                $validator = Validator::make($request->all(),[
                    'categories_name_'. $value->languages_id=> 'required',
                  
                ],
                [
                    
                'categories_name_'.$value->languages_id.'.required'=>$value->languages_name.'_'.'CATEGORY_NAME_REQUIRED',

                ]



            );
                if ($validator->fails())
                    return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
                
            }







        // store category
        $saveData = $request->only([ 'parent_id', 'categories_image']);
        // $saveData['parent_id'] = ($request->category_type == 'parent') ? $request->main_category : $request->sub_category;

        if ($saveData['categories_image'] != '') {
            ApiHelper::image_upload_with_crop($api_token, $saveData['categories_image'], 3, '', '', false);
        }


        $max_sortOrder =  Category::max('sort_order');
        $sort_order=$max_sortOrder+1;
           
          //  return ApiHelper::JSON_RESPONSE(true,   $sort_order,'SUCCESS_STATUS_UPDATE');
        $saveData['sort_order'] =$sort_order;

        $saveData['categories_slug'] = "";

        // $cat = Category::create($saveData);
        //   $CA=$cat->categories_id;
        //   return ApiHelper::JSON_RESPONSE(true,$cat,'');
        // store cat details
        foreach (ApiHelper::allSupportLang() as $key => $value) {

            $cat_name = "categories_name_" . $value->languages_id;

            $desc = "categories_description_" . $value->languages_id;
            $title = "categories_title_" . $value->languages_id;
            $categories_meta_desc = "categories_meta_desc_" . $value->languages_id;
            //   $categories_menu_name = "categories_menu_name_".$value->languages_id;

            // if($value->languages_code == 'en'){
            //     $category = Category::find($cat->categories_id);
            //     $category->categories_slug = Str::slug($request->$cat_name);
            //     $category->save();
            // }

            $cats = CategoryDescription::where('categories_name', $request->$cat_name)->first();

            //  return ApiHelper::JSON_RESPONSE(true,$cats->categories_name,'');
            if (empty($cats)) {
                $cat = Category::create($saveData);

                $catDesc = CategoryDescription::create([
                    'categories_id' => $cat->categories_id,
                    'categories_name' => $request->$cat_name,
                    'categories_description' => $request->$desc,
                    'languages_id' => $value->languages_id,
                    //   'categories_menu_name'=>$request->$categories_menu_name
                ]);

                if ($value->languages_code == 'en') {
                    $category = Category::find($cat->categories_id);
                    $category->categories_slug = Str::slug($request->$cat_name);
                    $category->save();
                }

            } else {

                return ApiHelper::JSON_RESPONSE(false, [], 'CATEGORY_ALREADY_EXIST');

            }

            // return ApiHelper::JSON_RESPONSE(true,$request->$categories_meta_desc,'SUCCESS_STATUS_UPDATE');

            if (!empty($request->$title) || !empty($request->$categories_meta_desc)) {

                $seo = SeoMeta::create([
                    'page_type' => 1,
                    'reference_id' => $catDesc->categories_description_id,
                    'language_id' => $value->languages_id,
                    'seometa_title' => $request->$title,
                    'seometa_desc' => $request->$categories_meta_desc,
                ]);
            }

        }

        if ($cat) {
            return ApiHelper::JSON_RESPONSE(true, $cat, 'SUCCESS_CATEGORY_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_CATEGORY_ADD');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $response = Category::with('categorydescription')->find($request->categories_id);

        if (!empty($response)) {
            if (!empty($response->categorydescription)) {
                $response->categorydescription->map(function ($description) {

                    $seoInfo = SeoMeta::select('seometa_title', 'seometa_desc')->where([
                        'page_type' => 1,
                        'reference_id' => $description->categories_id,
                        'language_id' => 1,
                    ])->first();

                    $description->seo = $seoInfo;

                    return $description;

                });
            }

        }

        // // create category tfor table

        // $selected_category = [];
        // if(!empty($response)){
        //     // foreach ($response as $key => $cat) {

        //     //     $label_res = $cat->categorydescription()->where('categories_id', $cat->categories_id)->first();
        //     //     $label = ($label_res !== null) ? $label_res->categories_name : '';

        //     //     array_push($selected_category,[
        //     //         "label"=>$label,
        //     //         "value"=>$cat->categories_id
        //     //     ]);
        //     // }

        // }
        // $response->selected_category = $selected_category;

        return ApiHelper::JSON_RESPONSE(true, $response, '');

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        $categories_id = $request->categories_id;

        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

       
            //validation

            foreach ( ApiHelper::allSupportLang() as $key => $value) {

                                    
                //validation check 
                $validator = Validator::make($request->all(),[
                    'categories_name_'. $value->languages_id=> 'required',
                    
                ],
                [
                    
                'categories_name_'.$value->languages_id.'.required'=>$value->languages_name.'_'.'CATEGORY_NAME_REQUIRED',

                ]



            );
                if ($validator->fails())
                    return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
                
            }

        // store category
        $saveData = $request->only(['sort_order', 'status', 'parent_id', 'categories_image']);

        if ($saveData['categories_image'] != '') {
            ApiHelper::image_upload_with_crop($api_token, $saveData['categories_image'], 3, 'category', '', false);
        }

        Category::where('categories_id', $categories_id)->update($saveData);

        $cat = Category::find($categories_id);

        // remove old seo info
        // SeoMeta::where(['page_type'=>2, 'reference_id' => $categories_id])->delete();

        // store cat details
        foreach (ApiHelper::allSupportLang() as $key => $value) {

            $cat_name = "categories_name_" . $value->languages_id;
            $desc = "categories_description_" . $value->languages_id;
            $title = "categories_title_" . $value->languages_id;
            $categories_meta_desc = "categories_meta_desc_" . $value->languages_id;
           //   $categories_menu_name = "categories_menu_name_".$value->languages_id;

            $categories_description_id = "categories_description_id_" . $value->languages_id;

            if ($value->languages_code == 'en') {
                $category = Category::find($categories_id);
                $category->categories_slug = Str::slug($request->$cat_name);
                $category->save();
            }

            $cats = CategoryDescription::where('categories_name', $request->$cat_name)->where('categories_id', '<>', $categories_id)->first();

            if (empty($cats)) {
                $catDesc = CategoryDescription::updateOrCreate([
                    'categories_id' => $cat->categories_id,
                    'languages_id' => $value->languages_id,
                ], [
                    'categories_id' => $cat->categories_id,
                    'languages_id' => $value->languages_id,
                    'categories_name' => $request->$cat_name,
                    'categories_description' => $request->$desc,
                  //   'categories_menu_name'=>$request->$categories_menu_name
                    // 'categories_title'=>$request->$title,
                    // 'categories_meta_desc'=>$request->$categories_meta_desc,
                ]);
            } else {

                return ApiHelper::JSON_RESPONSE(false, [], 'CATEGORY_UNIQUE_REQUIRED');

            }

            // $catDesc = CategoryDescription::where('categories_description_id', $request->$categories_description_id)->update([
            //     'categories_name'=>$request->$cat_name,
            //     'categories_description'=>$request->$desc,
            //     // 'categories_title'=>$request->$title,
            //     // 'categories_meta_desc'=>$request->$categories_meta_desc,
            // ]);

            //   return ApiHelper::JSON_RESPONSE(true, $catDesc->categories_description_id,'SUCCESS_CATEGORY_UPDATE');

            if (!empty($request->$title) || !empty($request->$categories_meta_desc)) {
                // create new
                SeoMeta::updateOrCreate(['page_type' => 1,
                    'reference_id' => $catDesc->categories_description_id,
                    'language_id' => $value->languages_id,
                ], [
                    'seometa_title' => $request->$title, 'seometa_desc' => $request->$categories_meta_desc,
                ]);

                //  SeoMeta::create([
                //     'page_type'=>1,
                //     'reference_id'=>$request->$categories_description_id,
                //     'language_id'=>$value->languages_id,
                //     'seometa_title'=>$request->$title,
                //     'seometa_desc'=>$request->$categories_meta_desc
                // ]);
            }

        }
        if ($cat) {
            return ApiHelper::JSON_RESPONSE(true, $saveData, 'SUCCESS_CATEGORY_UPDATE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_CATEGORY_UPDATE');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        $infoData = Category::find($request->update_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_STATUS_UPDATE');

    }

    public function is_featured(Request $request)
    {

        $api_token = $request->api_token;
        $categories_id = $request->categories_id;
        $infodata = Category::find($categories_id);
        $infodata->is_featured = ($infodata->is_featured == 0) ? 1 : 0;
        $infodata->save();

        return ApiHelper::JSON_RESPONSE(true, $infodata, 'SUCCESS_IS_FEATURED_UPDATE');
    }

    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $categories_id = $request->categories_id;
        $sort_order = $request->sort_order;
        $infoData = Category::find($categories_id);
        if (empty($infoData)) {
            $infoData = new Category();
            $infoData->categories_id = $categories_id;
            $infoData->sort_order = $sort_order;
            $infoData->status = 1;

            $infoData->save();

        } else {
            $infoData->sort_order = $sort_order;
            $infoData->save();
        }

        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_SORT_ORDER_UPDATE');
    }
}
