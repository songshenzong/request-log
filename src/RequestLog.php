<?php

namespace Songshenzong\RequestLog;

use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'time',
        'ip',
        'method',
        'uri',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'updated_at',
    ];


    /**
     * @param $key
     *
     * @return false|string
     */
    public function getTimeAttribute($key)
    {
        return date('m/d/y H:i:s:ms', $key);
    }
}
