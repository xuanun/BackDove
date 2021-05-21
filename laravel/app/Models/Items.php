<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Items extends Model
{
    protected $table = "dove_items";

    /**
     * 通过类型获取物品
     * @param $type_id
     * @param $page_size
     * @param $firm_id
     * @param $item_name
     * @param $time_data
     * @return mixed
     */
    public function getNesByType($type_id, $page_size, $firm_id, $item_name, $time_data)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('dove_items.id, dove_items.type_id, dove_items.item_name, dove_items.item_img,
            dove_items.firm_id, dove_items.created_time, type_name'));
        if($type_id)
            $results = $results->where('dove_items.type_id', $type_id);
        if($item_name)
            $results = $results->where('dove_items.item_name', $item_name);
        if($time_data){
            $start_time = strtotime($time_data);
            $end_time = $start_time + 86400;
            //return $start_time.'******'.$end_time;
            $results = $results->whereBetween('dove_items.updated_time', [$start_time, $end_time]);
        }
        $results = $results
            ->leftJoin('dove_item_type as type', 'type.id', '=','dove_items.type_id')
            ->where('dove_items.firm_id', $firm_id)
            ->paginate($page_size);

        $imgUrl = env('IMAGE_URL');
        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];

        foreach($results as $v){
            $v->item_img = $imgUrl.$v->item_img;
            $data['list'][] = $v;
        }
        return  $data;
    }


    /**
     * 获取某一类全部物品
     * @param $type_id
     * @param $firm_id
     * @return mixed
     */
    public function getItemsByType($type_id, $firm_id)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('id, item_name'))
            ->where('type_id', $type_id)
            ->where('firm_id', $firm_id)
            ->get();
    }


    /**
     * @param $time_data
     * @param $type_id
     * @param $item_name
     * @param $firm_id
     * @param $item_img
     * 新增物品
     * @return mixed
     */
    public function addItem($time_data, $type_id, $item_name, $firm_id, $item_img)
    {
        DB::beginTransaction();
        $exists = $this->existsItem($item_name, $firm_id);
        if(!$exists)
        {
            try{
                $insertArray = [
                    'type_id' =>$type_id,
                    'item_name' =>$item_name,
                    'item_img' =>$item_img,
                    'firm_id'=>$firm_id,
                    'updated_time' => $time_data,
                    'created_time' => time(),
                ];
                $id = DB::table($this->table)->insertGetId($insertArray);
                if($id){
                    $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>['item_id'=>$id]];
                }
                else{
                    DB::rollBack();
                    $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[]];
                }
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
            }
        }else{
            $return = ['code'=>40004,'msg'=>'新增失败', 'data'=>['物品名字已经存在']];
        }
        DB::commit();
        return $return;

    }

    /**
     * @param $time_data
     * @param $item_id
     * @param $type_id
     * @param $item_name
     * @param $firm_id
     * @param $item_img
     * 新增物品
     * @return mixed
     */
    public function editItem($item_id, $time_data, $type_id, $item_name, $firm_id, $item_img)
    {
        DB::beginTransaction();
        $exists = $this->existsItem($item_name, $firm_id);
        if(!$exists)
        {
            try{
                $updateArray = [
                    'type_id' =>$type_id,
                    'item_name' =>$item_name,
                    'item_img' =>$item_img,
                    'firm_id'=>$firm_id,
                    'updated_time' => $time_data,
                    'created_time' => time(),
                ];
                DB::table($this->table)->where('id', $item_id)->update($updateArray);
                $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
            }
        }else{
            $return = ['code'=>40004,'msg'=>'修改失败', 'data'=>['物品名字已经存在']];
        }
        DB::commit();
        return $return;

    }

    /**
     * 判断物品是否存在
     * @param $item_name
     * @param $firm_id
     * @return mixed
     */
    public function existsItem($item_name, $firm_id)
    {
        return DB::table($this->table)
            ->where('item_name', $item_name)
            ->where('firm_id', $firm_id)
            ->exists();
    }

    /**
     * @param $item_id
     * 新增物品
     * @return mixed
     */
    public function delItem($item_id)
    {
        DB::beginTransaction();
        try{
             DB::table($this->table)->delete($item_id);
            $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;

    }
}
