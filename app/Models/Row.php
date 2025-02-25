<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Row extends Model
{
    public $incrementing = false; // Отключаем автоинкремент
    protected $keyType = 'string'; // Укажи 'int', если id — число

    protected $fillable = ['id', 'name', 'date'];
}
