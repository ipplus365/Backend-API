<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IX extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ixs';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['id', 'peeringdb_id', 'created_at', 'updated_at'];

    public function members()
    {
        return $this->hasMany('App\Models\IXMember', 'ix_peeringdb_id', 'peeringdb_id');
    }

}