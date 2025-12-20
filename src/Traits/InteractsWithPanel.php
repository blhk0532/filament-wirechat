<?php

namespace AdultDate\FilamentWirechat\Traits;

use AdultDate\FilamentWirechat\Exceptions\NoPanelProvidedException;
use Filament\Facades\Filament;

trait InteractsWithPanel
{
    public ?string $panel;

    /**
     * Set the panel from provided value or default.
     *
     * @throws NoPanelProvidedException
     * @throws \Exception
     */
    public function resolvePanel(?string $panel = null): void
    {
        if (is_string($panel) && filled($panel)) {
            $this->panel = $panel;
        } else {
            // Get the current Filament panel ID
            $currentPanel = Filament::getCurrentPanel();
            $this->panel = $currentPanel ? $currentPanel->getId() : 'admin';
        }

        if (! $this->panel) {
            throw NoPanelProvidedException::make();
        }
    }

    /**
     * Get the resolved Filament panel instance.
     */
    public function getPanel(): ?\Filament\Panel
    {
        return Filament::getPanel($this->panel);
    }
}
