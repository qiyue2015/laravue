<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['id', 'caption', 'shortname', 'pinyin', 'book_count'];

    protected $hidden = ['created_at', 'updated_at'];

    public function novels()
    {
        return $this->hasOne(Novel::class);
    }
}
