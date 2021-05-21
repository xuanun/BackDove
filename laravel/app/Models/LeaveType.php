<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LeaveType extends Model
{
    protected $table = "dove_leave_type";
    /**
     * 假期类型
     * @return mixed
     */
    public function getAllTypes()
    {
        return  DB::table($this->table)
            ->select(DB::raw('type_id, type_name'))
            ->get();
    }
}
