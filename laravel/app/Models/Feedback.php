<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Feedback  extends Model
{
    protected $table = "dove_feedback";
    const IS_REED = 1;//已读
    const NO_REED = 0;//未读
    /**
     * 意见反馈列表
     * @param $page_size
     * @param $firm_id
     * @return mixed
     */
    public function getList($page_size, $firm_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('feedback_id, describes, user.user_name, feedback_time, image, reed_status'))
            ->leftJoin('dove_user as user', 'dove_feedback.uid', '=', 'user.id')
            ->where('user.firm_id', $firm_id)
            ->orderBy('feedback_time', 'asc')
            ->paginate($page_size);
        $imgUrl = env('APP_IMG_URL');
        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];
        foreach($results as $v){
            $img_url = json_decode($v->image);
            $v->image = empty($img_url) ? '' : $imgUrl.$img_url[0];
            $data['list'][] = $v;
        }
        return  $data;
    }

    /**
     * 修改反馈状态
     * @param $feedback_id,
     * @param $reed_status,
     * @return mixed
     */
    public function editData($feedback_id, $reed_status)
    {
        try{
            $updateArray = [
                'reed_status' => $reed_status,
                'uptime' => time(),
            ];
            $id = DB::table($this->table)
                ->where('feedback_id', $feedback_id)
                ->update($updateArray);
            $return = ['code'=>20000,'msg'=>'修改成功', 'data'=>['id'=>$id]];
        }catch(\Exception $e){
            $return = ['code'=>40000,'msg'=>'修改失败', 'data'=>[]];
        }
        return $return;
    }

    /**
     * 批量删除反馈意见
     * @param $feedback_ids
     * @return mixed
     */
    public function batchDelFeedback($feedback_ids)
    {
        DB::beginTransaction();
        try{
            DB::table($this->table)
                ->whereIn('feedback_id', $feedback_ids)
                ->delete();
            $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

}
