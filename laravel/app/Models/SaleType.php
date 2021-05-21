<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SaleType extends Model
{
    protected $table = "dove_sale_type";
    /**
     * @param $start_time
     * @param $end_time
     * @param $factory_id
     * @param $type_id
     * @param $goods_name
     * @param $page_size
     * 查询列表
     * @return mixed
     */
    public function getList($start_time, $end_time, $factory_id, $type_id, $goods_name, $page_size)
    {
        $results =  DB::table($this->table)
            ->select(DB::raw('sale_type_id as id, record_time, dove_sale_type.factory_id, factory.name as factory_name, block.block_type as type_id, specs, remarks, block.type_name, goods_name, number, unit_price, price, customer, pay_method'))
            ->leftJoin('dove_factory as factory', 'factory.id', '=', 'dove_sale_type.factory_id')
            ->leftJoin('dove_block as block', 'block.id', '=', 'dove_sale_type.block_type');
        if($factory_id)
            $results = $results->where('dove_sale_type.factory_id', $factory_id);
        if($type_id)
            $results = $results->where('block.block_type',$type_id);
        if($goods_name)
            $results = $results->where('goods_name',$goods_name);
        if($start_time && $end_time)
            $results = $results->whereBetween('record_time',[$start_time, $end_time]);
        elseif($start_time && empty($end_time))
            $results = $results->where('record_time', '=', $start_time);
        elseif(empty($start_time) && $end_time)
            $results = $results->where('record_time', '=', $end_time);

        $results = $results
            ->orderBy('record_time','desc')
            ->orderBy('sale_type_id','desc')
            ->paginate($page_size);

        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];
        foreach($results as $v){
            $data['list'][] = $v;
        }
        return  $data;

    }

    /**
     * @param $date_time
     * @param $user_id
     * @param $factory_id
     * @param $type_id
     * @param $goods_name
     * @param $specs
     * @param $unit_price
     * @param $number
     * @param $price
     * @param $customer
     * @param $pay_method
     * @param $remarks
     * 记录已经存在 更新记录
     * @return mixed
     */
    public function addData( $date_time, $user_id, $factory_id, $type_id,  $goods_name,
                               $specs, $unit_price, $number, $price, $customer, $pay_method, $remarks)
    {
        try{
            $insertArray = [
                'uid' => $user_id,
                'record_time' => $date_time,
                'factory_id' => $factory_id,
                'block_type' => $type_id,
                'goods_name' => $goods_name,
                'specs' => $specs,
                'unit_price' => $unit_price,
                'number' => $number,
                'price' => $price,
                'customer' => $customer,
                'pay_method' => $pay_method,
                'remarks' => $remarks,
                'usage_y' => date('Y', strtotime($date_time)),
                'usage_m' => date('m', strtotime($date_time)),
                'usage_d' => date('d', strtotime($date_time)),
                'creatime' => time(),
                'uptime' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            if($id)
                $return = ['code'=>20000,'msg'=>'数据录入成功', 'data'=>[]];
            else
                $return = ['code'=>40400,'msg'=>'数剧录入失败', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'数据录入失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }


    /**
     * @param $id
     * @param $date_time
     * @param $factory_id
     * @param $type_id
     * @param $goods_name
     * @param $specs
     * @param $unit_price
     * @param $number
     * @param $price
     * @param $customer
     * @param $pay_method
     * @param $remarks
     * 记录已经存在 更新记录
     * @return mixed
     */
    public function updateData($id, $date_time, $factory_id, $type_id,  $goods_name,
                               $specs, $unit_price, $number, $price, $customer, $pay_method, $remarks)
    {
        try{
            $UpdateArray = [
                'record_time' =>$date_time,
                'factory_id' => $factory_id,
                'block_type'=> $type_id,
                'goods_name'=> $goods_name,
                'specs' => $specs,
                'unit_price'=> $unit_price,
                'number'=> $number,
                'price'=> $price,
                'customer' => $customer,
                'pay_method'=> $pay_method,
                'remarks'=> $remarks,
                'uptime' => time(),
            ];
            $id = DB::table($this->table)
                ->where('sale_type_id', $id)
                ->update($UpdateArray);
            if($id)
                $return = ['code'=>20000,'msg'=>'请求成功', 'data'=>[]];
            else
                $return = ['code'=>40400,'msg'=>'数据已经审核', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'请求失败', 'data'=>[$e->getMessage()]];
        }
        return $return;
    }

    /**
     * @param $id
     * 删除数据
     * @return mixed
     */
    public function delData($id)
    {
        DB::beginTransaction();
        try{
            $id = DB::table($this->table)
                ->where('sale_type_id', $id)
                ->delete();
            if($id){
                $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
            }
            else{
                DB::rollBack();
                $return = ['code'=>40400,'msg'=>'删除失败', 'data'=>[]];
            }
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }

}
