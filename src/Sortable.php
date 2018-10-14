<?php

namespace Metrix\EloquentSortable;

use ArrayAccess;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Sortable Trait
 */
trait Sortable
{

    /**
     * boot
     *
     * @return void
     */
    public static function bootSortable(): void
    {
        static::creating(function ($model) {
            if ($model->shouldSortWhenCreating()) {
                $model->setHighestOrderValue();
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function setHighestOrderValue(): void
    {
        $orderColumnName = $this->sortOrderColumnName();
        $this->{$orderColumnName} = $this->getHighestOrderValue() + 1;
    }

    /**
     * Determine the order value for the new record.
     */
    public function getHighestOrderValue(): int
    {
        return (int) $this->sortQuery()->max($this->sortOrderColumnName());
    }

    /**
     * Determine the order value of a model at a specified Nth position.
     *
     * @param int $position The position of the model. Positions start at 1.
     *
     * @return int
     */
    public function getOrderValueAtPosition(int $position): int
    {
        $position--;
        $position = max($position, 0);

        return (int) $this->sortQuery()->orderBy($this->sortOrderColumnName())->skip($position)->limit(1)->value($this->sortOrderColumnName());
    }

    /**
     * Provide an ordered scope.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $direction
     *
     * @return \Illuminate\Database\Eloquent\Builder;
     */
    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        $orderColumnName = $this->sortOrderColumnName();
        $group_column    = $this->sortOrderGroupColumnName();

        if ($group_column) {
            // Multiple Group Columns (array)
            if (\is_array($group_column)) {
                foreach ($group_column as $field) {
                    $query = $query->orderBy($field, $direction);
                }
            }

            // Single Group Column
            $query->orderBy($group_column, $direction);
        }

        return $query->orderBy($orderColumnName, $direction);
    }

    /**
     * This function reorders the records: the record with the first id in the array
     * will get order 1, the record with the second id will get order 2, ...
     *
     * A starting order number can be optionally supplied (defaults to 1).
     *
     * @param array|\ArrayAccess $ids
     * @param int $startOrder
     *
     * @return void
     */
    public static function setNewOrder($ids, int $startOrder = 1): void
    {
        if (! \is_array($ids) && ! $ids instanceof ArrayAccess) {
            throw new InvalidArgumentException('You must pass an array or ArrayAccess object to setNewOrder');
        }

        $model = new static;

        $orderColumnName  = $model->sortOrderColumnName();
        $primaryKeyColumn = $model->getKeyName();

        foreach ($ids as $id) {
            static::withoutGlobalScope(SoftDeletingScope::class)
                ->where($primaryKeyColumn, $id)
                ->update([$orderColumnName => $startOrder++]);
        }
    }

    /**
     * Get the order column name.
     */
    protected function sortOrderColumnName(): string
    {
        return $this->sortable['order_column'] ?? 'display_order';
    }

    /**
     * @return string|array|null
     */
    public function sortOrderGroupColumnName()
    {
        return $this->sortable['group_column'] ?? null;
    }

    /**
     * Determine if the order column should be set when saving a new model instance.
     */
    public function shouldSortWhenCreating(): bool
    {
        return $this->sortable['sort_on_creating'] ?? true;
    }

    /**
     * Swaps the order of this model with the model 'below' this model.
     *
     * @return $this
     */
    public function moveOrderDown(): self
    {
        $orderColumnName = $this->sortOrderColumnName();

        $swapWithModel = $this->sortQuery()->limit(1)
            ->ordered()
            ->where($orderColumnName, '>', $this->{$orderColumnName})
            ->first();

        if (! $swapWithModel) {
            return $this;
        }

        return $this->swapOrderWithModel($swapWithModel);
    }

    /**
     * Swaps the order of this model with the model 'above' this model.
     *
     * @return $this
     */
    public function moveOrderUp(): self
    {
        $orderColumnName = $this->sortOrderColumnName();

        $swapWithModel = $this->sortQuery()->limit(1)
            ->ordered('desc')
            ->where($orderColumnName, '<', $this->{$orderColumnName})
            ->first();

        if (! $swapWithModel) {
            return $this;
        }

        return $this->swapOrderWithModel($swapWithModel);
    }

    /**
     * Swap the order of this model with the order of another model.
     *
     * @param mixed $otherModel
     *
     * @return $this
     */
    public function swapOrderWithModel($otherModel): self
    {
        $orderColumnName = $this->sortOrderColumnName();

        $oldOrderOfOtherModel = $otherModel->{$orderColumnName};

        $otherModel->{$orderColumnName} = $this->{$orderColumnName};
        $otherModel->save();

        $this->{$orderColumnName} = $oldOrderOfOtherModel;
        $this->save();

        return $this;
    }

    /**
     * Swap the order of two models.
     *
     * @param mixed $model
     * @param mixed $otherModel
     *
     * @return void
     */
    public static function swapOrder($model, $otherModel): void
    {
        $model->swapOrderWithModel($otherModel);
    }

    /**
     * Moves this model to the first position.
     *
     * @return $this
     */
    public function moveToStart(): self
    {
        $primary_key = $this->getKeyName();

        $firstModel = $this->sortQuery()
            ->limit(1)
            ->ordered()
            ->first();

        if ($firstModel->{$primary_key} === $this->{$primary_key}) {
            return $this;
        }

        $orderColumnName = $this->sortOrderColumnName();

        $this->{$orderColumnName} = $firstModel->{$orderColumnName};
        $this->save();

        $this->sortQuery()->where($primary_key, '!=', $this->{$primary_key})->increment($orderColumnName);

        return $this;
    }

    /**
     * Moves this model to the last position.
     *
     * @return $this
     */
    public function moveToEnd(): self
    {
        $maxOrderValue   = $this->getHighestOrderValue();
        $orderColumnName = $this->sortOrderColumnName();
        $primaryKey      = $this->getKeyName();

        if ($this->{$orderColumnName} === $maxOrderValue) {
            return $this;
        }

        $oldOrder = $this->{$orderColumnName};

        $this->{$orderColumnName} = $maxOrderValue;
        $this->save();

        $this->sortQuery()->where($primaryKey, '!=', $this->{$primaryKey})
            ->where($orderColumnName, '>', $oldOrder)
            ->decrement($orderColumnName);

        return $this;
    }

    /**
     * Move a model into a specified position
     * Positions starts at 1. 0 would be the same as start.
     *
     * @param int $newPosition
     *
     * @return $this
     */
    public function moveToPosition(int $newPosition): self
    {
        $primaryKey      = $this->getKeyName();
        $orderColumnName = $this->sortOrderColumnName();
        $newPosition     = max($newPosition, 0);
        $currentPosition = (int) $this->{$orderColumnName};
        $orderAtPosition = $this->getOrderValueAtPosition($newPosition);

        // No need to do anything, it is already in the correct position
        if ($currentPosition === $newPosition) {
            return $this;
        }

        if ($newPosition > $currentPosition) {
            // The model is moving up
            $this->sortQuery()->where([[$primaryKey, '!=', $this->{$primaryKey}], [$orderColumnName, '>', $currentPosition], [$orderColumnName, '<=', $orderAtPosition]])->decrement($orderColumnName);
        } else {
            // The model is moving down
            $this->sortQuery()->where([[$primaryKey, '!=', $this->{$primaryKey}], [$orderColumnName, '<', $currentPosition], [$orderColumnName, '>=', $orderAtPosition]])->increment($orderColumnName);
        }

        $this->{$orderColumnName} = $orderAtPosition;
        $this->save();

        return $this;
    }

    /**
     * Get eloquent builder for sortable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function sortQuery(): Builder
    {
        $group_column = $this->sortOrderGroupColumnName();

        if ($group_column) {

            /** @var Builder $query */
            $query = static::query();

            // Multiple Group Columns (array)
            if (\is_array($group_column)) {
                foreach ($group_column as $field) {
                    $query = $query->where($field, $this->{$field});
                }
                return $query;
            }

            // Single Group Column
            return $query->where($group_column, $this->{$group_column});
        }

        // No group column
        return static::query();
    }
}
