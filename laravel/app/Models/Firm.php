<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Firm extends Model
{
    protected $table = "dove_firm";
    const INVALID = 0;
    const NORMAL = 1;
    protected $avatar = 'icon.jpg';
    /**
     * 通过企业ID查询企业信息
     * @param $firm_id
     * @return mixed
     */
    public function getFirmInfo($firm_id)
    {
         $results = DB::table($this->table)
            ->select(DB::raw('id, name, icon'))
            ->where('id', $firm_id)
            ->where('data_status', self::NORMAL)
            ->first();
        $imgUrl = env('IMAGE_URL');
        $results->icon = $imgUrl.$results->icon;
        return $results;
    }

    /**
     * 查询全部企业信息
     * @return mixed
     */
    public function getFirmList()
    {
        $results = DB::table($this->table)
            ->select(DB::raw('id, name, icon'))
            ->where('data_status', self::NORMAL)
            ->get();
        $imgUrl = env('IMAGE_URL');
        foreach($results as $v){
            $v->icon = $imgUrl.$v->icon;
        }
        return $results;
    }

    /**
     * 查询全部企业信息分页
     * @param $page_size
     * @return mixed
     */
    public function getAllFirm($page_size)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('id, name, icon, show_status'))
            ->paginate($page_size);
        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];

        $imgUrl = env('IMAGE_URL');
        foreach($results as $v){
            $v->icon_name = $v->icon;
            $v->icon = $imgUrl.$v->icon;
            $data['list'][] = $v;
        }
        return $data;
    }

    /**
     * 查询全部企业信息分页
     * @return mixed
     */
    public function getAllFirms()
    {
        $results = DB::table($this->table)
            ->select(DB::raw('id, name, icon'))
            ->where('data_status', self::NORMAL)
            ->get();

        $imgUrl = env('IMAGE_URL');
        foreach($results as $v){
            $v->icon = $imgUrl.$v->icon;
        }
        return $results;
    }

    /**
     * @param $firm_name
     * @param $firm_icon
     * 新增企业
     * @return mixed
     */
    public function addFirm($firm_name, $firm_icon)
    {
        $exists = $this->existsFirm($firm_name);
        $return = array();
        if(!$exists)
        {
            try{
                $insertArray = [
                    'name' => $firm_name,
                    'icon' => $firm_icon,
                    'data_status'=>self::NORMAL,
                    'show_status'=>self::NORMAL,
                    'updated_time' => time(),
                    'created_time' => time(),
                ];
                $id = DB::table($this->table)->insertGetId($insertArray);
                if($id){
                    $return = ['code'=>20000,'msg'=>'新增成功', 'firm_id'=>$id];
                }
                else
                    DB::rollBack();
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
            }
        }else{
            $return = ['code'=>40004,'msg'=>'新增失败', 'data'=>['企业名字已经存在']];
        }
        return $return;

    }

    /**
     * 编辑企业数据
     * @param $id,
     * @param $firm_name,
     * @param $firm_icon,
     * @return mixed
     */
    public function editFirm($id, $firm_name, $firm_icon)
    {
        try{
            $updateArray = [
                'name' => $firm_name,
                'icon' => $firm_icon,
                'updated_time' => time(),
            ];
            $id = DB::table($this->table)
                ->where('id', $id)
                ->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>['firm_id'=>$id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * 修改企业状态
     * @param $id,
     * @param $show_status,
     * @return mixed
     */
    public function editFirmStatus($id, $show_status)
    {
        try{
            $updateArray = [
                'show_status' => $show_status,
                'data_status' => $show_status,
                'updated_time' => time(),
            ];
            $id = DB::table($this->table)
                ->where('id', $id)
                ->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>['firm_id'=>$id]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[]];
        }
        return $return;
    }

    /**
     * 删除企业
     * @param $id,
     * @return mixed
     */
    public function delFirm($id)
    {
        try{
            $updateArray = [
                'data_status' => 0,
                'updated_time' => time(),
            ];
            $id = DB::table($this->table)
                ->where('id', $id)
                ->update($updateArray);
            $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * 判断企业是否存在
     * @param $firm_name
     * @return mixed
     */
    public function existsFirm($firm_name)
    {
        return DB::table($this->table)
            ->where('name', $firm_name)
            ->where('data_status', self::NORMAL)
            ->exists();
    }

    /**
     * 通过企业ID判断企业是否存在
     * @param $firm_id
     * @return mixed
     */
    public function existsFirmById($firm_id)
    {
        return DB::table($this->table)
            ->where('id', $firm_id)
            ->where('show_status', self::NORMAL)
            ->where('data_status', self::NORMAL)
            ->exists();
    }
}
