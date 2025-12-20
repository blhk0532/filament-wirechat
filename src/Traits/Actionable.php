<?php

namespace AdultDate\FilamentWirechat\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use AdultDate\FilamentWirechat\Models\Action;

/**
 * Trait Actionable
 */
trait Actionable
{
    /**
     * Actions - that were performed on this model
     */
    public function actions(): MorphMany
    {
        return $this->morphMany(Action::class, 'actionable', 'actionable_type', 'actionable_id', 'id');
    }
}
