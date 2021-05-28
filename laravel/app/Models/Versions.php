<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Versions extends Model
{
    protected $table = "dove_edition";
    /**
     * 获取版本列表
     * @return string
     */
    public function getList()
    {
        return $result = DB::table($this->table)
            ->select(DB::raw('edition_id, platform, edition_num, edition_name, edition_content, download, novation'))
            ->get();

    }

    /**
     * 修改数据
     * @param $edition_id
     * @param $platform
     * @param $edition_num
     * @param $edition_name
     * @param $edition_content
     * @param $download
     * @param $novation
     * @return mixed
     */
    public function updateData($edition_id, $platform, $edition_num, $edition_name, $edition_content, $download, $novation)
    {
        try{
            $updateArray = [
                'platform' =>$platform,
                'edition_num' =>$edition_num,
                'edition_name' =>$edition_name,
                'edition_content'=>$edition_content,
                'download' => $download,
                'novation' => $novation,
                'uptime' => time()

            ];
            DB::table($this->table)->where('edition_id', $edition_id)->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[$e->getMessage()]];
        }
        return $return;

    }
}
