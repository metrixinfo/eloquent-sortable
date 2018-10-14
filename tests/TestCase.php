<?php

namespace Metrix\EloquentSortable\Test;

use Illuminate\Database\Schema\Blueprint;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base TestCase
 */
abstract class TestCase extends Orchestra
{

    /**
     *  Setup the Tests
     */
    public function setUp()
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Database Migrations
     *
     * @return void
     */
    protected function setUpDatabase(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('dummies', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('group_id', false, true);
            $table->integer('display_order');
        });

        collect(range(1, 20))->each(function (int $i) {
            Dummy::create([
                'name' => $i,
                'display_order' => $i,
                'group_id' => 1,
            ]);
        });

    }

    /**
     *  Add soft deletes to dummy data
     */
    protected function setUpSoftDeletes(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->table('dummies', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     *  Add group row to dummy data
     */
    protected function setUpGroups(): void
    {

        collect(range(1, 20))->each(function (int $i=1) {
            DummyWithGroups::create([
                'name' => $i,
                'display_order' => $i,
                'group_id' => 2,
            ]);
        });

        collect(range(1, 20))->each(function (int $i=1) {
            DummyWithGroups::create([
                'name' => $i,
                'display_order' => $i,
                'group_id' => 3,
            ]);
        });

    }

}
