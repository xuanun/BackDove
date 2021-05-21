<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EarlyWarning extends Model
{
    protected $table = "dove_early_warning";
    /**
     * 通过企业ID查询预警列表
     * @param $firm_id
     * @return mixed
     */
    public function getList($firm_id)
    {
        $results = DB::table($this->table)
            ->where('firm_id', $firm_id)
            ->get();
        $data = array();
        foreach($results as $v){
            $v->b_pigeon = $v->b_pigeon.'%';
            $v->c_pigeon = $v->c_pigeon.'%';
            $v->s_pigeon = $v->s_pigeon.'%';
            $v->w_pigeon = $v->w_pigeon.'%';
            $v->b_egg = $v->b_egg.'%';
            $v->one_year = $v->one_year.'%';
            $v->two_year = $v->two_year.'%';
            $v->three_year = $v->three_year.'%';
            $v->four_year = $v->four_year.'%';
            $v->five_year = $v->five_year.'%';
            $data[] = $v;
        }
        return $data;
    }

    /**
     * 修改预警设置
     * @param $die_id
     * @param $sick_id
     * @param $incompetent_id
     * @param $d_b_pigeon
     * @param $d_c_pigeon
     * @param $d_s_pigeon
     * @param $d_w_pigeon
     * @param $s_b_pigeon
     * @param $s_c_pigeon
     * @param $s_s_pigeon
     * @param $s_w_pigeon
     * @param $b_egg
     * @param $one_year
     * @param $two_year
     * @param $three_year
     * @param $four_year
     * @param $five_year
     * @return mixed
     */
    public function editEarly($die_id, $sick_id, $incompetent_id, $d_b_pigeon,
                              $d_c_pigeon, $d_s_pigeon, $d_w_pigeon, $s_b_pigeon, $s_c_pigeon, $s_s_pigeon,
                              $s_w_pigeon, $b_egg, $one_year, $two_year, $three_year, $four_year, $five_year)
    {
        DB::beginTransaction();
        try{
            $dieUpdateArray = [
                'b_pigeon' => $d_b_pigeon,
                'c_pigeon' => $d_c_pigeon,
                's_pigeon' => $d_s_pigeon,
                'w_pigeon' => $d_w_pigeon,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('id', $die_id)
                ->update($dieUpdateArray);
            $sickUpdateArray = [
                'b_pigeon' => $s_b_pigeon,
                'c_pigeon' => $s_c_pigeon,
                's_pigeon' => $s_s_pigeon,
                'w_pigeon' => $s_w_pigeon,
                'b_egg' => $b_egg,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('id', $sick_id)
                ->update($sickUpdateArray);

            $UpdateArray = [
                'one_year' => $one_year,
                'two_year' => $two_year,
                'three_year' => $three_year,
                'four_year' => $four_year,
                'five_year' => $five_year,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('id', $incompetent_id)
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
     * 通过企业ID查询预警列表
     * @param $name
     * @return mixed
     */
    public function getDataByName($name)
    {
        return DB::table($this->table)
            ->where('name', $name)
            ->first();
    }
}
