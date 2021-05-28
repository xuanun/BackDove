<?php


namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Feedback;
use App\Models\Firm;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\Versions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class IndexController  extends Controller
{
    /**
     * 首页——轮播图
     * @param Request $request
     * @return mixed
     */
    public function bannerList(Request $request)
    {
        $input = $request->all();
        $position = isset($input['position']) ? $input['position'] : 0;
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page = isset($input['page']) ? $input['page'] : 1;
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        if(empty($firm_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        //return $position;
        $model_banner = new Banner();
        $return_data = $model_banner->getAllBanner($page_size, $firm_id, $position);
        return response()->json(['status_code'=>20000,'msg'=>'请求成功',  'data'=>$return_data]);
    }
    /**
     * 首页——轮播显示状态
     * @param Request $request
     * @return mixed
     */
    public function editBannerShow(Request $request)
    {
        $input = $request->all();
        $b_id = isset($input['b_id']) ? $input['b_id'] : 0;
        $show_status = isset($input['show_status']) ? $input['show_status'] : 0;
        //return response()->json($show_status);
        $model_banner = new Banner();
        $return_data = $model_banner->editBanner($b_id, $show_status);
        return response()->json($return_data);
    }

    /**
     * 首页——轮播排序
     * @param Request $request
     * @return mixed
     */
    public function editBannerRank(Request $request)
    {
        //获取参数
        $input = $request->all();
        $self_b_id = isset($input['self_b_id']) ? $input['self_b_id'] : 0;
        $self_serial = isset($input['self_serial']) ? $input['self_serial'] : 0;
        $up_b_id = isset($input['up_b_id']) ? $input['up_b_id'] : 0;
        $up_serial = isset($input['up_serial']) ? $input['up_serial'] : 0;
        $down_b_id = isset($input['down_b_id']) ? $input['down_b_id'] : 0;
        $down_serial = isset($input['down_serial']) ? $input['down_serial'] : 0;
        $rank_status = isset($input['rank_status']) ? $input['rank_status'] : 1; //上移：1   0 ：下移

        $model_banner = new Banner();
        $return_data = array();
        //上移排序
        if($rank_status == 1){
            $return_data = $model_banner->UpRank($self_b_id, $self_serial, $up_b_id, $up_serial);
        }
        //下移排序
        if($rank_status == 0){
            $return_data = $model_banner->downRank($self_b_id, $self_serial, $down_b_id, $down_serial);
        }

        return response()->json($return_data);
    }

    /**
     * 首页——轮播图删除
     * @param Request $request
     * @return mixed
     */
    public function delBanner(Request $request)
    {
        $input = $request->all();
        $b_id = isset($input['b_id']) ? $input['b_id'] : 0;
        $model_banner = new Banner();
        $return_data = $model_banner->delBanner($b_id);
        return response()->json($return_data);
    }

    /**
     * 上传图片
     * @param Request $request
     * @return mixed
     */
    public function uploadBannerImage(Request $request)
    {
        if ($request->isMethod('POST')) { //判断文件是否是 POST的方式上传
            $tmp = $request->file('file');
            if ($tmp->isValid()) { //判断文件上传是否有效
                $FileType = $tmp->getClientOriginalExtension(); //获取文件后缀

                $FilePath = $tmp->getRealPath(); //获取文件临时存放位置

                $FileName = date('Ymd') . uniqid() . '.' . $FileType; //定义文件名

                Storage::disk('banner')->put($FileName, file_get_contents($FilePath)); //存储文件
                $IMAGE_URL = env('IMAGE_URL');
                $BANNER_URL= env('BANNER_URL');
                $obj['url'] = $IMAGE_URL.$BANNER_URL. $FileName;
                $data['code'] = 20000;
                $data['data'] = $obj;
                $data['file_name'] = $BANNER_URL.$FileName;
                $data['msg'] = "";
                $data['time'] = time();
                return response()->json($data);
            }
        }
    }

    /**
     * 首页——新增轮播图
     * @param Request $request
     * @return mixed
     */
    public function addBanner(Request $request)
    {
        //获取参数
        $input = $request->all();
        $img_url = isset($input['file_name']) ? $input['file_name'] : '';
        $position = isset($input['position']) ? $input['position'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        $title = isset($input['title']) ? $input['title'] : '';
        $show_status = isset($input['show_status']) ? $input['show_status'] : 1;
        if(empty($img_url) || empty($title)) return response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>[]]);
//        $file_array = explode('.',$img_url);
//        if($file_array[1] == 'jpg' || $file_array[1] == 'jpeg' || $file_array[1] == 'png'|| $file_array[1] == 'gif') {
        //新增轮播图
        $model_banner = new Banner();
        $return_data = $model_banner->addBanner($title, $img_url, $show_status, $firm_id, $position);
        return response()->json($return_data);
//        }
////        else{
////            return ['code'=>40000,'msg'=>'文件格式不正确', 'data'=>[$img_url]];
////        }
    }

    /**
     * 首页——编辑轮播图
     * @param Request $request
     * @return mixed
     */
    public function editBannerInfo(Request $request)
    {
        //获取参数
        $input = $request->all();
        $b_id = isset($input['b_id']) ? $input['b_id'] : '';
        $img_url = isset($input['file_name']) ? $input['file_name'] : ''; //没有更改 传空值
        $title = isset($input['title']) ? $input['title'] : '';
        $show_status = isset($input['show_status']) ? $input['show_status'] : 1;
        $position = isset($input['position']) ? $input['position'] : '';
        if( empty($title) || empty($b_id)) return response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>[]]);
        $model_banner = new Banner();
        if($img_url){
//            $file_array = explode('.',$img_url);
//            if($file_array[1] == 'jpg' || $file_array[1] == 'jpeg' || $file_array[1] == 'png'|| $file_array[1] == 'gif') {
            //修改轮播图
            $return_data = $model_banner->editBannerInfo($b_id, $title, $img_url, $show_status, $position);
            return response()->json($return_data);
//            }
//            else{
//                return ['code'=>40000,'msg'=>'文件格式不正确', 'data'=>[$img_url]];
//            }
        }else{
            $return_data = $model_banner->editBannerInfo($b_id, $title, $img_url, $show_status, $position);
            return response()->json($return_data);
        }
    }

    /**
     * 首页——新闻列表-全部分类
     * @param Request $request
     * @return mixed
     */
    public function newsCategory(Request $request)
    {
        //获取参数
        $input = $request->all();
        //新增轮播图
        $model_category = new NewsCategory();
        $return_data = $model_category->getAllCategory();
        return response()->json(['code'=>20000,'msg'=>"成功",  'data'=>$return_data]);
    }

    /**
     * 首页——新闻列表
     * @param Request $request
     * @return mixed
     */
    public function newsList(Request $request)
    {
        //获取参数
        $input = $request->all();
        $category_id = isset($input['category_id']) ? $input['category_id'] : 0;
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page = isset($input['page']) ? $input['page'] : 1;

        //新闻列表
        $model_news = new News();
        $return_data = $model_news->getNesByType($category_id, $page_size, $firm_id);
        return response()->json(['code'=>20000,'msg'=>"成功",  'data'=>$return_data]);
    }

    /**
     * 首页——新闻详情
     * @param Request $request
     * @return mixed
     */
    public function newsContent(Request $request)
    {
        //获取参数
        $input = $request->all();
        $news_id = isset($input['news_id']) ? $input['news_id'] : 0;
        if(empty($news_id)) return response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>[]]);

        //新闻内容
        $model_news = new News();
        $return_data = $model_news->getNewsContent($news_id);
        return response()->json(['code'=>20000,'msg'=>"成功",  'data'=>$return_data]);
    }

    /**
     * 首页——新闻--新增新闻
     * @param Request $request
     * @return mixed
     */
    public function addNews(Request $request)
    {
        //获取参数
        $input = $request->all();
        $category_id = isset($input['category_id']) ? $input['category_id'] : 0;
        $title = isset($input['title']) ? $input['title'] : '';
        $content = isset($input['content']) ? $input['content'] : '';
        $img_url = isset($input['img_url']) ? $input['img_url'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        $serial = isset($input['serial']) ? $input['serial'] : 0;
        if(empty($img_url) || empty($title) || empty($content))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
//        $file_array = explode('.',$img_url);
//        if(count($file_array) != 2) return response()->json(['code'=>40000,'msg'=>'文件格式不正确', 'data'=>[]]);
//        if($file_array[1] == 'jpg' || $file_array[1] == 'jpeg' || $file_array[1] == 'png'|| $file_array[1] == 'gif')
//        {
            //新增轮播图
        $model_news = new News();
        $return_data = $model_news->addNews($category_id, $title, $img_url, $content, $serial, $firm_id);
        return response()->json($return_data);
//        }
//        else{
//            return ['code'=>40000,'msg'=>'文件格式不正确', 'data'=>[$img_url]];
//        }
    }

    /**
     * 上传新闻图片
     * @param Request $request
     * @return mixed
     */
    public function uploadNewsImg(Request $request)
    {
        if ($request->isMethod('POST')) { //判断文件是否是 POST的方式上传
            $tmp = $request->file('file');
            if ($tmp->isValid()) { //判断文件上传是否有效
                $FileType = $tmp->getClientOriginalExtension(); //获取文件后缀

                $FilePath = $tmp->getRealPath(); //获取文件临时存放位置

                $FileName = date('Ymd') . uniqid() . '.' . $FileType; //定义文件名

                Storage::disk('news')->put($FileName, file_get_contents($FilePath)); //存储文件
                $IMAGE_URL = env('IMAGE_URL');
                $NEWS_URL = env('NEWS_URL');
                $obj['url'] = $IMAGE_URL.$NEWS_URL. $FileName;
                $data['code'] = 20000;
                $data['data'] = $obj;
                $data['file_name'] = $NEWS_URL.$FileName;
                $data['msg'] = "";
                $data['time'] = time();
                return response()->json($data);
            }
        }
    }

    /**
     * 首页——编辑新闻
     * @param Request $request
     * @return mixed
     */
    public function editNewsInfo(Request $request)
    {
        $input = $request->all();
        $news_id = isset($input['news_id']) ? $input['news_id'] : 0;
        $category_id = isset($input['category_id']) ? $input['category_id'] : 0;
        $title = isset($input['title']) ? $input['title'] : '';
        $content = isset($input['content']) ? $input['content'] : '';
        $img_url = isset($input['img_url']) ? $input['img_url'] : '';
        $serial = isset($input['serial']) ? $input['serial'] : 0;
        if(empty($news_id) || empty($category_id) || empty($title) || empty($content) || empty($serial) )
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_news = new News();
        if($img_url){
//            $file_array = explode('.',$img_url);
//            if(count($file_array) != 2) return response()->json(['code'=>40000,'msg'=>'文件格式不正确', 'data'=>[]]);
//            if($file_array[1] == 'jpg' || $file_array[1] == 'jpeg' || $file_array[1] == 'png'|| $file_array[1] == 'gif')
//            {
                //新增轮播图
                $return_data = $model_news->editNewsInfo($news_id, $category_id, $title, $img_url, $content, $serial);
                return response()->json($return_data);
//            }
//            else{
//                return ['code'=>40000,'msg'=>'文件格式不正确', 'data'=>[$img_url]];
//            }
        }else{
            $return_data = $model_news->editNewsInfo($news_id, $category_id, $title, $img_url, $content, $serial);
            return response()->json($return_data);
        }

    }

    /**
     * 首页——新闻批量删除
     * @param Request $request
     * @return mixed
     */
    public function delNews(Request $request)
    {
        $input = $request->all();
        $news_id = isset($input['news_id']) ? $input['news_id'] : 0;
        $model_news = new News();
        $return_data = $model_news->delNews($news_id);
        return response()->json($return_data);
    }

    /**
     * 首页——批量删除新闻
     * @param Request $request
     * @return mixed
     */
    public function batchDelNews(Request $request)
    {
        $input = $request->all();
        $news_ids = isset($input['news_ids']) ? $input['news_ids'] : [];
        $model_news = new News();
        $return_data = $model_news->batchDelNews($news_ids);
        return response()->json($return_data);
    }


    /**
     * 首页——公司切换--获取全部企业
     * @param Request $request
     * @return mixed
     */
    public function allFirm(Request $request)
    {
        $input = $request->all();
        $token = $request->header('token');
        if(empty($token)) return response()->json(['code'=>50000,'msg'=>'用户未登录',  'data'=>[]]);
        $redis = Redis::connection('default');
        $cacheKey = "dove_user_login_".$token;
        $cacheValue = $redis->get($cacheKey);
        if(!empty($cacheValue)){
            $data = json_decode($cacheValue, true);
        }else{
            return response()->json(['code'=>50000,'msg'=>'你的登录信息已失效',  'data'=>[]]);
        }
        $firm_id = $data['firm_id'];
        if($firm_id)
            return response()->json(['code'=>30000,'msg'=>'你没有权限切换企业',  'data'=>[]]);
        $model_firm = new Firm();
        $firm_data = $model_firm->getAllFirms();
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>[$firm_data]];
        return response()->json($return_data);
    }
    /**
     * 首页——意见反馈-列表
     * @param Request $request
     * @return mixed
     */
    public function opinionList(Request $request)
    {
        $input = $request->all();
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page = isset($input['page']) ? $input['page'] : 1;
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : 0;
        $model_feedback = new Feedback();
        $feedback_data = $model_feedback->getList($page_size, $firm_id);
        $return_data = ['code'=>20000,'msg'=>'请求成功', 'data'=>$feedback_data];
        return response()->json($return_data);
    }

    /**
     * 首页——意见反馈-修改读取状态
     * @param Request $request
     * @return mixed
     */
    public function editOpinion(Request $request)
    {
        $input = $request->all();
        $feedback_id = isset($input['feedback_id']) ? $input['feedback_id'] : 0;
        $reed_status = isset($input['reed_status']) ? $input['reed_status'] : 0;
        if(empty($feedback_id))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_feedback = new Feedback();
        $return_data = $model_feedback->editdata($feedback_id, $reed_status);
        return response()->json($return_data);
    }

    /**
     * 首页——意见反馈-批量删除
     * @param Request $request
     * @return mixed
     */
    public function batchDelOpinion(Request $request)
    {
        $input = $request->all();
        $feedback_ids = isset($input['feedback_ids']) ? $input['feedback_ids'] : [];
        if(!is_array($feedback_ids) || empty($feedback_ids))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);
        $model_feedback = new Feedback();
        $return_data = $model_feedback->batchDelFeedback($feedback_ids);
        return response()->json($return_data);
    }


    /**
     * 版本更新列表
     * @param Request $request
     * @return mixed
     */
    public function versionsList(Request $request)
    {
        $input = $request->all();
        $model_versions = new Versions();
        $return_data = $model_versions->getList();
        return response()->json(['code'=>20000,'msg'=>'请求成功', 'data'=>$return_data]);
    }

    /**
     * 修改版本
     * @param Request $request
     * @return mixed
     */
    public function editVersions(Request $request)
    {
        $input = $request->all();
        $edition_id = isset($input['edition_id']) ? $input['edition_id'] : '';//数据ID
        $platform = isset($input['platform']) ? $input['platform'] : '';//平台
        $edition_num = isset($input['edition_num']) ? $input['edition_num'] : '';//版本号
        $edition_name = isset($input['edition_name']) ? $input['edition_name'] : '';//版本名字
        $edition_content = isset($input['edition_content']) ? $input['edition_content'] : '';//版本内容
        $download = isset($input['download']) ? $input['download'] : '';//下载地址
        $novation = isset($input['novation']) ? $input['novation'] : '';//是否强制更新

        if( empty($edition_id) || empty($platform) ||  empty($edition_num) ||  empty($edition_name) ||  empty($edition_content) ||  empty($download))
            return response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_versions = new Versions();
        $return_data = $model_versions->updateData($edition_id, $platform, $edition_num, $edition_name, $edition_content, $download, $novation);
        return response()->json($return_data);
    }
}
