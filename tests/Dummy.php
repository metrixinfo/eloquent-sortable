<?php

namespace Metrix\EloquentSortable\Test;

use Illuminate\Database\Eloquent\Model;
use Metrix\EloquentSortable\Sortable;

/**
 *  Dummy Test Data
 */
class Dummy extends Model
{

    use Sortable;

    /**
     * The database table name.
     *
     * @var string
     */
    protected $table = 'dummies';

    /**
     * Guarded from mass assignments.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Auto-generated Timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

}
