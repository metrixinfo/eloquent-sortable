<?php

namespace Metrix\EloquentSortable\Test;

use Illuminate\Database\Eloquent\Model;
use Metrix\EloquentSortable\Sortable;

/**
 *  Dummy Test Data
 */
class DummyWithMultipleGroups extends Model
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

    /**
     * Sort models
     *
     * @var array
     */
    public $sortable = [
        'order_column_name' => 'display_order',
        'sort_on_creating'  => true,
        'group_column'      => ['group_id','user_id']
    ];

}
