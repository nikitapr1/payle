<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{

    use HasFactory;

    protected $guarded = false;

    public function getTelegramLinkAttribute()
    {
        return sprintf("http://t.me/PayleRuBot?%s", http_build_query(['start' => $this->telegram_start_token]));
    }

}
