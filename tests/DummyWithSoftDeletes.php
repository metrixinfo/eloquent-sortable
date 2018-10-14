<?php

namespace Metrix\EloquentSortable\Test;

use Metrix\EloquentSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DummyWithSoftDeletes extends Model
{
    use SoftDeletes, Sortable;

    protected $table = 'dummies';
    protected $guarded = [];
    public $timestamps = false;
}
