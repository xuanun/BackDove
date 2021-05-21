<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Factory extends Model
{
    protected $table = "dove_factory";
    /**
     * 查询管理员列表基本信息
     * @param $firm_id
     * @return mixed
     */
    public function getAllFactoryInfo($firm_id)
    {
        return $results = DB::table($this->table)
            ->select(DB::raw('id, name'))
            ->where('firm_id', $firm_id)
            ->get();
    }

    /**
     * @param $factory_name
     * @param $firm_id
     * 新增厂区
     * @return mixed
     */
    public function addFactory($factory_name, $firm_id)
    {
        $exists = $this->existsFactory($factory_name, $firm_id);
        if(empty($exists)){
            try{
                $insertArray = [
                    'name' =>$factory_name,
                    'firm_id' =>$firm_id,
                    'created_time' => time(),
                    'updated_time' => time(),
                ];
                $id = DB::table($this->table)->insertGetId($insertArray);
                $return = ['code'=>20000,'msg'=>'新增成功', 'factory_id'=>$id];
            }catch(\Exception $e){
                DB::rollBack();
                $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
            }
            return $return;
        }else{
            DB::rollBack();
            return ['code'=>40000,'msg'=>'名字已经存在', 'data'=>[]];
        }

    }

    /**
     * @param $factory_name
     * @param $firm_id
     * 查询厂区名字存不存在
     * @return mixed
     */
    public function existsFactory($factory_name, $firm_id)
    {
        return DB::table($this->table)
            ->where('name', $factory_name)
            ->where('firm_id', $firm_id)
            ->exists();

    }
}
