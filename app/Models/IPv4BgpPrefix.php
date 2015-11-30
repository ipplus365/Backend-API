<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IPv4BgpPrefix extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ipv4_bgp_prefixes';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['id', 'created_at', 'updated_at'];

    public function whois()
    {
        return $this->hasOne('App\Models\IPv4PrefixWhois', 'bgp_prefix_id', 'id');
    }
}
