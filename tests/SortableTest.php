<?php

namespace Metrix\EloquentSortable\Test;

use Illuminate\Support\Collection;

class SortableTest extends TestCase
{

    /**
     * @test
     */
    public function it_sets_the_display_order_on_creation(): void
    {
        foreach (Dummy::all() as $dummy) {
            $this->assertEquals($dummy->name, $dummy->display_order);
        }
    }

    /**
     * @test
     */
    public function it_sets_the_display_order_on_creation_with_grouped_models(): void
    {
        $this->setUpGroups();

        $all = DummyWithGroups::where('group_id', 2)->get()->all();

        foreach ($all as $dummy) {
            $this->assertEquals($dummy->name, $dummy->display_order);
        }
    }

    /**
     * @test
     */
    public function it_can_get_the_highest_sort_order_value(): void
    {
        $this->assertEquals(Dummy::all()->count(), (new Dummy())->getHighestOrderValue());
    }

    /**
     * @test
     */
    public function it_can_get_the_highest_sort_order_value_with_grouped_models(): void
    {
        $this->setUpGroups();

        $this->assertEquals(DummyWithGroups::where('group_id', 1)->count(), DummyWithGroups::make(['name'=>21, 'group_id'=>1])->getHighestOrderValue());
        $this->assertEquals(DummyWithGroups::where('group_id', 2)->count(), DummyWithGroups::make(['name'=>21, 'group_id'=>2])->getHighestOrderValue());
        $this->assertEquals(DummyWithGroups::where('group_id', 3)->count(), DummyWithGroups::make(['name'=>21, 'group_id'=>3])->getHighestOrderValue());
    }

    /**
     * @test
     */
    public function it_can_get_the_order_number_at_a_specific_position(): void
    {
        $all = Dummy::all();

        $first = $all->first();
        $this->assertEquals($first->display_order, (new Dummy())->getOrderValueAtPosition(0));

        $fourth = $all->slice(3, 1)->first();
        $this->assertEquals($fourth->display_order, (new Dummy())->getOrderValueAtPosition(4));

        $seventh = $all->slice(6, 1)->first();
        $this->assertEquals($seventh->display_order, (new Dummy())->getOrderValueAtPosition(7));
    }

    /**
     * @test
     */
    public function it_can_get_the_order_number_at_a_specific_position_with_grouped_models(): void
    {

        $this->setUpGroups();
        $all = DummyWithGroups::all();

        $first = $all->first();
        $this->assertEquals($first->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>1])->getOrderValueAtPosition(0));

        $fourth = $all->slice(3, 1)->first();
        $this->assertEquals($fourth->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>1])->getOrderValueAtPosition(4));

        $seventh = $all->slice(6, 1)->first();
        $this->assertEquals($seventh->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>1])->getOrderValueAtPosition(7));

        $first = $all->slice(20, 1)->first();
        $this->assertEquals($first->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>2])->getOrderValueAtPosition(0));

        $fourth = $all->slice(23, 1)->first();
        $this->assertEquals($fourth->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>2])->getOrderValueAtPosition(4));

        $seventh = $all->slice(26, 1)->first();
        $this->assertEquals($seventh->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>2])->getOrderValueAtPosition(7));

        $first = $all->slice(40, 1)->first();
        $this->assertEquals($first->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>3])->getOrderValueAtPosition(0));

        $fourth = $all->slice(43, 1)->first();
        $this->assertEquals($fourth->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>3])->getOrderValueAtPosition(4));

        $seventh = $all->slice(46, 1)->first();
        $this->assertEquals($seventh->display_order, DummyWithGroups::make(['name'=>21, 'group_id'=>3])->getOrderValueAtPosition(7));
    }

    /**
     * @test
     */
    public function it_provides_an_ordered_trait(): void
    {
        $i=1;

        foreach (Dummy::ordered()->get()->pluck('display_order') as $order) {
            $this->assertEquals($i++, $order);
        }
    }

    /**
     * @test
     */
    public function it_provides_an_ordered_trait_with_a_single_set_of_grouped_models(): void
    {
        $this->setUpGroups();

        $i=1;

        foreach (DummyWithGroups::where('group_id', 2)->ordered()->get()->pluck('display_order') as $order ) {
            $this->assertEquals($i++, $order);
        }

    }

    /**
     * @test
     */
    public function it_provides_an_ordered_trait_with_multiple_sets_of_grouped_models(): void
    {
        $this->setUpMultipleGroups();

        $i=1;
        $group_id = 1;

        $all = DummyWithMultipleGroups::ordered()->get();

        foreach (DummyWithGroups::ordered()->get() as $order ) {
            $this->assertEquals($i++, $order->display_order);
            $this->assertEquals($group_id, $order->group_id);
            if ( $i > 20 ){
                $group_id++;
                $i=1;
            }
        }

    }

    /**
     * @test
     */
    public function it_can_get_the_highest_order_number_with_trashed_models(): void
    {
        $this->setUpSoftDeletes();

        DummyWithSoftDeletes::first()->delete();

        $this->assertEquals(DummyWithSoftDeletes::withTrashed()->count(), (new DummyWithSoftDeletes())->getHighestOrderValue());
    }

    /** @test */
    public function it_can_get_the_order_number_at_a_specific_position_with_trashed_models(): void
    {
        $this->setUpSoftDeletes();
        $all = DummyWithSoftDeletes::all();

        $first = $all->first();
        $this->assertEquals($first->display_order, (new Dummy())->getOrderValueAtPosition(1));

        $fourth = $all->slice(3, 1)->first();
        $this->assertEquals($fourth->display_order, (new Dummy())->getOrderValueAtPosition(4));

        $seventh = $all->slice(6, 1)->first();
        $this->assertEquals($seventh->display_order, (new Dummy())->getOrderValueAtPosition(7));
    }

    /** @test */
    public function it_can_set_a_new_order(): void
    {
        $newOrder = Collection::make(Dummy::all()->pluck('id'))->shuffle()->toArray();

        Dummy::setNewOrder($newOrder);

        foreach (Dummy::orderBy('display_order')->get() as $i => $dummy) {
            $this->assertEquals($newOrder[$i], $dummy->id);
        }
    }

    /** @test */
    public function it_can_set_a_new_order_from_collection(): void
    {
        $newOrder = Collection::make(Dummy::all()->pluck('id'))->shuffle();

        Dummy::setNewOrder($newOrder);

        foreach (Dummy::orderBy('display_order')->get() as $i => $dummy) {
            $this->assertEquals($newOrder[$i], $dummy->id);
        }
    }

    /** @test */
    public function it_can_set_a_new_order_with_trashed_models(): void
    {
        $this->setUpSoftDeletes();

        $dummies = DummyWithSoftDeletes::all();

        $dummies->random()->delete();

        $newOrder = Collection::make($dummies->pluck('id'))->shuffle();

        DummyWithSoftDeletes::setNewOrder($newOrder);

        foreach (DummyWithSoftDeletes::withTrashed()->orderBy('display_order')->get() as $i => $dummy) {
            $this->assertEquals($newOrder[$i], $dummy->id);
        }
    }

    /** @test */
    public function it_can_set_a_new_order_without_trashed_models(): void
    {
        $this->setUpSoftDeletes();

        DummyWithSoftDeletes::first()->delete();

        $newOrder = Collection::make(DummyWithSoftDeletes::pluck('id'))->shuffle();

        DummyWithSoftDeletes::setNewOrder($newOrder);

        foreach (DummyWithSoftDeletes::orderBy('display_order')->get() as $i => $dummy) {
            $this->assertEquals($newOrder[$i], $dummy->id);
        }
    }

    /** @test */
    public function it_will_determine_to_sort_when_creating_if_sortable_attribute_does_not_exist(): void
    {
        $model = new Dummy();

        $this->assertTrue($model->shouldSortWhenCreating());
    }

    /** @test */
    public function it_will_determine_to_sort_when_creating_if_sort_when_creating_setting_does_not_exist(): void
    {
        $model = new class extends Dummy {
            public $sortable = [];
        };

        $this->assertTrue($model->shouldSortWhenCreating());
    }

    /** @test */
    public function it_will_respect_the_sort_when_creating_setting(): void
    {
        $model = new class extends Dummy {
            public $sortable = ['sort_on_creating' => true];
        };

        $this->assertTrue($model->shouldSortWhenCreating());

        $model = new class extends Dummy {
            public $sortable = ['sort_on_creating' => false];
        };

        $this->assertFalse($model->shouldSortWhenCreating());
    }


    /** @test */
    public function it_can_move_the_order_down(): void
    {
        $firstModel = Dummy::find(3);
        $secondModel = Dummy::find(4);

        $this->assertEquals($firstModel->display_order, 3);
        $this->assertEquals($secondModel->display_order, 4);

        $this->assertNotFalse($firstModel->moveOrderDown());

        $firstModel = Dummy::find(3);
        $secondModel = Dummy::find(4);

        $this->assertEquals($firstModel->display_order, 4);
        $this->assertEquals($secondModel->display_order, 3);
    }

    /** @test */
    public function it_will_not_fail_when_it_cant_move_the_order_down(): void
    {
        $lastModel = Dummy::all()->last();

        $this->assertEquals($lastModel->display_order, 20);
        $this->assertEquals($lastModel, $lastModel->moveOrderDown());
    }

    /** @test */
    public function it_can_move_the_order_up(): void
    {
        $firstModel = Dummy::find(3);
        $secondModel = Dummy::find(4);

        $this->assertEquals($firstModel->display_order, 3);
        $this->assertEquals($secondModel->display_order, 4);

        $this->assertNotFalse($secondModel->moveOrderUp());

        $firstModel = Dummy::find(3);
        $secondModel = Dummy::find(4);

        $this->assertEquals($firstModel->display_order, 4);
        $this->assertEquals($secondModel->display_order, 3);
    }

    /** @test */
    public function it_will_not_break_when_it_cant_move_the_order_up(): void
    {
        $lastModel = Dummy::first();

        $this->assertEquals($lastModel->display_order, 1);
        $this->assertEquals($lastModel, $lastModel->moveOrderUp());
    }

    /** @test */
    public function it_can_swap_the_position_of_two_given_models(): void
    {
        $firstModel = Dummy::find(3);
        $secondModel = Dummy::find(4);

        $this->assertEquals($firstModel->display_order, 3);
        $this->assertEquals($secondModel->display_order, 4);

        Dummy::swapOrder($firstModel, $secondModel);

        $this->assertEquals($firstModel->display_order, 4);
        $this->assertEquals($secondModel->display_order, 3);
    }

    /** @test */
    public function it_can_swap_itself_with_another_model(): void
    {
        $firstModel = Dummy::find(3);
        $secondModel = Dummy::find(4);

        $this->assertEquals($firstModel->display_order, 3);
        $this->assertEquals($secondModel->display_order, 4);

        $firstModel->swapOrderWithModel($secondModel);

        $this->assertEquals($firstModel->display_order, 4);
        $this->assertEquals($secondModel->display_order, 3);
    }

    /** @test */
    public function it_can_move_a_model_to_the_first_place(): void
    {
        $position = 3;

        $oldModels = Dummy::whereNot('id', $position)->get();

        $model = Dummy::find($position);

        $this->assertEquals(3, $model->display_order);

        $model = $model->moveToStart();

        $this->assertEquals(1, $model->display_order);

        $oldModels = $oldModels->pluck('display_order', 'id');
        $newModels = Dummy::whereNot('id', $position)->get()->pluck('display_order', 'id');

        foreach ($oldModels as $key => $oldModel) {
            $this->assertEquals($oldModel + 1, $newModels[$key]);
        }
    }

    /**
     * @test
     */
    public function it_can_move_a_model_to_the_last_place(): void
    {
        $position = 3;

        $oldModels = Dummy::whereNot('id', $position)->get();

        $model = Dummy::find($position);

        $this->assertNotEquals(20, $model->display_order);

        $model = $model->moveToEnd();

        $this->assertEquals(20, $model->display_order);

        $oldModels = $oldModels->pluck('display_order', 'id');

        $newModels = Dummy::whereNot('id', $position)->get()->pluck('display_order', 'id');

        foreach ($oldModels as $key => $order) {
            if ($order > $position) {
                $this->assertEquals($order - 1, $newModels[$key]);
            } else {
                $this->assertEquals($order, $newModels[$key]);
            }
        }
    }

    /**
     * @test
     */
    public function it_can_move_a_model_to_a_specified_position(): void
    {
        // Move the model up
        $originalPosition = 3;
        $newPosition = 7;

        $model = Dummy::find($originalPosition);
        $modelAtPosition = Dummy::find($newPosition);

        $this->assertEquals($originalPosition, $model->display_order);
        $this->assertEquals($newPosition, $modelAtPosition->display_order);

        $model = $model->moveToPosition($newPosition);

        $this->assertEquals($newPosition, $model->display_order);

        $modelAtPosition->refresh();
        $this->assertEquals($newPosition - 1, $modelAtPosition->display_order);

        $all = Dummy::all()->sortBy('display_order');
        $all->values()->all();
        $count = $all->where('display_order', '<=', $newPosition)->count();
        $this->assertEquals($newPosition, $count);

        $counter = 1;
        foreach ($all as $m) {
            $this->assertEquals($m->display_order, $counter);
            $counter++;
        }

        // Move the model down
        $originalPosition = 10;
        $newPosition = 2;

        $model = Dummy::find($originalPosition);
        $modelAtPosition = Dummy::find($newPosition);

        $this->assertEquals($originalPosition, $model->display_order);
        $this->assertEquals($newPosition, $modelAtPosition->display_order);

        $model = $model->moveToPosition($newPosition);

        $this->assertEquals($newPosition, $model->display_order);

        $modelAtPosition->refresh();
        $this->assertEquals($newPosition + 1, $modelAtPosition->display_order);

        $all = Dummy::all()->sortBy('display_order');
        $all->values()->all();
        $count = $all->where('display_order', '<=', $newPosition)->count();
        $this->assertEquals($newPosition, $count);

        $counter = 1;
        foreach ($all as $m) {
            $this->assertEquals($m->display_order, $counter);
            $counter++;
        }
    }

}
