<?php

namespace Adultdate\Wirechat\Traits;

use AdultDate\FilamentWirechat\Models\Action;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
