<?php


namespace App\Http\Controllers\message;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController  extends  Controller
{
    /**
     * 首页--消息中心 消息列表
     * @param Request $request
     * @return mixed
     */
    public function  messageList(Request $request)
    {
        $input = $request->all();
        $page_size = isset($input['page_size']) ? $input['page_size'] : 10;
        $page = isset($input['page']) ? $input['page'] : 1;
        $search_word = isset($input['search_word']) ? $input['search_word'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id)) return  response()->json(['code'=>60000,'msg'=>'参数错误', 'data'=>['缺少参数,企业ID']]);
        //$search_word = isset($input['search_word']) ? '%'.$input['search_word'].'%' : '';
        if(!empty($search_word))
            $search_word = '%'.$search_word.'%';
        $model_message = new Message();
        $data = $model_message->messageList($search_word, $page_size, $firm_id);
        return response()->json(['code'=>20000, 'msg'=>'请求成功',  'data'=>$data]);
    }

    /**
     * 首页--消息中心 消息新增
     * @param Request $request
     * @return mixed
     */
    public function  addMessage(Request $request)
    {
        $input = $request->all();
        $title = isset($input['title']) ? $input['title'] : '';
        $role_id = isset($input['role_id']) ? $input['role_id'] : 0;
        $content = isset($input['content']) ? $input['content'] : '';
        $user_id = isset($input['user_id']) ? $input['user_id'] : '';
        $firm_id = isset($input['firm_id']) ? $input['firm_id'] : '';
        if(empty($firm_id) || empty($user_id))
            return  response()->json(['code'=>60000,'msg'=>'缺少参数', 'data'=>[]]);

        $model_message = new Message();
        $data = $model_message->addMessage($title, $role_id, $content, $user_id, $firm_id);
        return response()->json($data);
    }


    /**
     * 首页--消息中心 消息删除
     * @param Request $request
     * @return mixed
     */
    public function delMessage(Request $request)
    {
        $input = $request->all();
        $message_id = isset($input['message_id']) ? $input['message_id'] : '';
        $model_message = new Message();
        $data = $model_message->delMessage($message_id);
        return response()->json($data);
    }

    /**
     * 首页--消息中心 消息批量删除
     * @param Request $request
     * @return mixed
     */
    public function batchDelMessage(Request $request)
    {
        $input = $request->all();
        $message_id = isset($input['message_ids']) ? $input['message_ids'] : '';
        $model_message = new Message();
        $data = $model_message->batchDelMessage($message_id);
        return response()->json($data);
    }

}
