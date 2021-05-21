<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Banner extends Model
{
    protected $table = "dove_banner";
    const SHOW = 1; // 显示
    const HIDE = 0; //不显示
    const IS_DEL = 1;//删除
    const NOT_DEL = 0;//未删除
    /**
     * 查询轮播图列表
     * @param $page_size
     * @param $firm_id
     * @param $position
     * @return mixed
     */
    public function getAllBanner($page_size, $firm_id, $position)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('b_id, title, type, linkurl, serial, position, uptime, creatime'))
            ->where('is_del', self::NOT_DEL)
            ->where('firm_id', $firm_id);
        if($position){
            $results = $results->where('position', $position);
        }
        $results = $results
            ->orderBy('type','desc')
            ->orderBy('serial','asc')
            ->orderBy('uptime', 'desc')
            ->paginate($page_size);
        $imgUrl = env('IMAGE_URL');
        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];

        foreach($results as $v){
            $v->linkurl = $imgUrl.$v->linkurl;
            $data['list'][] = $v;
        }
        return  $data;
    }
    /**
     * 修改是否显示
     * @param $show_status
     * @param $b_id
     * @return mixed
     */
    public function editBanner($b_id, $show_status)
    {
        DB::beginTransaction();
        $return = array();
        try{
            $UpdateArray = [
                'type' => $show_status,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('b_id', $b_id)
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
     * 修改轮播图信息
     * @param $show_status
     * @param $b_id
     * @param $title
     * @param $img_url
     * @param $position
     * @return mixed
     */
    public function editBannerInfo($b_id, $title, $img_url, $show_status, $position)
    {
        DB::beginTransaction();
        $return = array();
        try{
            if($img_url){
                $UpdateArray = [
                    'title' => $title,
                    'linkurl' => $img_url,
                    'type' => $show_status,
                    'position' => $position,
                    'uptime' => time(),
                ];
            }else{
                $UpdateArray = [
                    'title' => $title,
                    'type' => $show_status,
                    'position' => $position,
                    'uptime' => time(),
                ];
            }

            DB::table($this->table)
                ->where('b_id', $b_id)
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
     * 修改是否显示
     * @param $self_b_id
     * @param $self_serial
     * @param $up_b_id
     * @param $up_serial
     * @return mixed
     */
    public function UpRank($self_b_id, $self_serial, $up_b_id, $up_serial)
    {
        DB::beginTransaction();
        try{
            $UpdateUpArray = [
                'serial' => $self_serial,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('b_id', $up_b_id)
                ->where('is_del', self::NOT_DEL)
                ->update($UpdateUpArray);
            $UpdateSelfArray = [
                'serial' => $up_serial,
                'uptime' => time() + 1,
            ];
            DB::table($this->table)
                ->where('b_id', $self_b_id)
                ->where('is_del', self::NOT_DEL)
                ->update($UpdateSelfArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

    /**
     * 修改是否显示
     * @param $self_b_id
     * @param $self_serial
     * @param $down_b_id
     * @param $down_serial
     * @return mixed
     */
    public function downRank($self_b_id, $self_serial, $down_b_id, $down_serial)
    {
        DB::beginTransaction();
        try{
            $UpdateSelfArray = [
                'serial' => $down_serial,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('b_id', $self_b_id)
                ->where('is_del', self::NOT_DEL)
                ->update($UpdateSelfArray);
            $UpdateDownArray = [
                'serial' => $self_serial,
                'uptime' => time() + 1,
            ];
            DB::table($this->table)
                ->where('b_id', $down_b_id)
                ->where('is_del', self::NOT_DEL)
                ->update($UpdateDownArray);
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
     * @param $b_id
     * @return mixed
     */
    public function delBanner($b_id)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'type' => self::HIDE,
                'is_del'=> self::IS_DEL,
                'uptime' => time(),
            ];
            DB::table($this->table)
                ->where('b_id', $b_id)
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
     * @param $title
     * @param $img_url
     * @param $show_status
     * @param $firm_id
     * @param $position
     * 新增轮播图
     * @return mixed
     */
    public function addBanner($title, $img_url, $show_status, $firm_id, $position)
    {
        DB::beginTransaction();
        try{
            $insertArray = [
                'title' => $title,
                'position' => $position,
                'firm_id' => $firm_id,
                'type' =>$show_status,
                'linkurl' => $img_url,
                'uptime' => time(),
                'creatime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $UpdateArray = [
                'serial' => $id,
                'uptime' => time(),
            ];
            DB::table($this->table)->where('b_id', $id)->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[$id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }
}
