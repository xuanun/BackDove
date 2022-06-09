<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class News extends Model
{
    protected $table = "dove_news";
    const IS_DEL = 1;//删除
    const NOT_DEL = 0;//未删除

    /**
     * 通过类型获取新闻
     * @param $category_id
     * @param $page_size
     * @param $firm_id
     * @return mixed
     */
    public function getNesByType($category_id, $page_size, $firm_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('dove_news.news_id, dove_news.cat_id, dove_news.display_img, dove_news.title, dove_news.creatime,
            dove_news.serial, category.cat_name, dove_news.content'));
        if($category_id)
            $results = $results->where('dove_news.cat_id', $category_id);
        $results = $results
            ->leftJoin('dove_news_category as category', 'dove_news.cat_id', '=','category.news_cat_id')
            ->where('is_del', self::NOT_DEL)
            ->where('dove_news.firm_id', $firm_id)
            ->orderBy('dove_news.serial', 'desc')
            ->orderBy('dove_news.creatime', 'desc')
            ->paginate($page_size);
        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];
        $imgUrl = env('IMAGE_URL');

        foreach($results as $v){
            $v->display_img = $imgUrl.$v->display_img;
            $data['list'][] = $v;
        }
        return  $data;
    }

    /**
     * 新增新闻
     * @param $category_id
     * @param $title
     * @param $img_url
     * @param $content
     * @param $serial
     * @param $firm_id
     * @return mixed
     */
    public function addNews($category_id, $title, $img_url, $content, $serial, $firm_id)
    {
        DB::beginTransaction();
        try{
            $insertArray = [
                'firm_id' => $firm_id,
                'cat_id' => $category_id,
                'title' => $title,
                'display_img' => $img_url,
                'content' => $content,
                'serial' => $serial,
                'uptime' => time(),
                'creatime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[$id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }

    /**
     * 编辑新闻
     * @param $news_id
     * @param $category_id
     * @param $title
     * @param $img_url
     * @param $content
     * @param $serial
     * @return mixed
     */
    public function editNewsInfo($news_id, $category_id, $title, $img_url, $content, $serial)
    {
        DB::beginTransaction();
        try{
            if($img_url){
                $UpdateArray = [
                    'cat_id' => $category_id,
                    'title' => $title,
                    'display_img' => $img_url,
                    'content' => $content,
                    'serial' => $serial,
                    'uptime' => time(),
                    'creatime' => time(),
                ];
            }else{
                $UpdateArray = [
                    'cat_id' => $category_id,
                    'title' => $title,
                    'content' => $content,
                    'serial' => $serial,
                    'uptime' => time(),
                    'creatime' => time(),
                ];
            }

            DB::table($this->table)
                ->where('news_id', $news_id)
                ->where('is_del', self::NOT_DEL)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }


    /**
     * 软删除
     * @param $news_id
     * @return mixed
     */
    public function delNews($news_id)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'is_del'=> self::IS_DEL,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('news_id', $news_id)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

    /**
     * 批量软删除
     * @param $news_ids
     * @return mixed
     */
    public function batchDelNews($news_ids)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'is_del'=> self::IS_DEL,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->whereIn('news_id', $news_ids)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

    /**
     * @param $news_id
     * 新闻详情
     * @return mixed
     */
    public function getNewsContent($news_id)
    {
        return DB::table($this->table)
            ->select(DB::raw('content'))
            ->where('news_id', $news_id)
            ->where('is_del', 0)
            ->first();
    }
}
