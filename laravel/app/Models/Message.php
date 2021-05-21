<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Message  extends Model
{
    protected $table = "dove_message";
    const IS_DEL = 1;//删除
    const NOT_DEL = 0;//未删除
    /**
     * 通过类型获取物品
     * @param $search_word
     * @param $page_size
     * @param $firm_id
     * @return mixed
     */
    public function messageList($search_word, $page_size, $firm_id)
    {
        $results = DB::table($this->table)
            ->select(DB::raw('dove_message.message_id, dove_message.title, dove_message.content, dove_message.role_id, roles.name as role_name,  user.user_name, dove_message.created_time'));
        if($search_word)
            $results = $results->where('title', 'like', $search_word);
        $results = $results
            ->leftJoin('dove_user as user', 'user.id', '=', 'dove_message.send_id')
            ->leftJoin('dove_roles as roles', 'roles.id', '=', 'dove_message.role_id')
            ->where('dove_message.firm_id', $firm_id)
            ->where('dove_message.delete', self::NOT_DEL)
            ->paginate($page_size);

        $data = [
            'total'=>$results->total(),
            'currentPage'=>$results->currentPage(),
            'pageSize'=>$page_size,
            'list'=>[]
        ];

        foreach($results as $v){
            if($v->role_id == 0)
                $v->role_name = '所有人';
            $data['list'][] = $v;
        }
        return  $data;
    }
    /**
     * 通过类型获取物品
     * @param $title
     * @param $role_id
     * @param $content
     * @param $user_id
     * @param $firm_id
     * @return mixed
     */
    public function addMessage($title, $role_id, $content, $user_id, $firm_id)
    {
        DB::beginTransaction();
        try{
            $insertArray = [
                'title' => $title,
                'role_id' => $role_id,
                'content' => $content,
                'firm_id' => $firm_id,
                'send_id' => $user_id,
                'delete' => self::NOT_DEL,
                'created_time' => time(),
                'updated_time' => time(),
            ];
            $id = DB::table($this->table)->insertGetId($insertArray);
            $return = ['code'=>20000,'msg'=>'新增成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'新增失败', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return $return;
    }
    /**
     * 软删除消息
     * @param $message_id
     * @return mixed
     */
    public function delMessage($message_id)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'delete' => self::IS_DEL,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->where('message_id', $message_id)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除成功', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

    /**
     * 软删除消息
     * @param $message_ids
     * @return mixed
     */
    public function batchDelMessage($message_ids)
    {
        DB::beginTransaction();
        try{
            $UpdateArray = [
                'delete' => self::IS_DEL,
                'updated_time' => time(),
            ];
            DB::table($this->table)
                ->whereIn('message_id', $message_ids)
                ->update($UpdateArray);
            $return = ['code'=>20000,'msg'=>'删除成功', 'data'=>[]];
        }catch(\Exception $e){
            DB::rollBack();
            $return = ['code'=>40000,'msg'=>'删除成功', 'data'=>[$e->getMessage()]];
        }
        DB::commit();
        return  $return;
    }

}
