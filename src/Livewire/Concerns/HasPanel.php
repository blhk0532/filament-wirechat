<?php

namespace Adultdate\Wirechat\Livewire\Concerns;

use Adultdate\Wirechat\Exceptions\NoPanelProvidedException;
use Adultdate\Wirechat\Facades\Wirechat;
use Livewire\Attributes\Computed;
use Wirechat\Wirechat\Panel;

trait HasPanel
{
    public Panel|string|null $panel = null;

    /**
     * Resolve and assign the panel during mount.
     */
    public function mountHasPanel(): void
    {
        // If already set by Livewire/public property, use it
        $this->initializePanel($this->panel);
    }

    /**
     * Initialize the panel manually (can be called anywhere).
     *
     * @throws NoPanelProvidedException
     */
    public function initializePanel(Panel|string|null $panelId = null): void
    {
        if ($panelId instanceof Panel) {
            $this->panel = $panelId->getId();
        } elseif (is_string($panelId) && filled($panelId)) {
            $this->panel = $panelId;
        } else {
            $this->panel = Wirechat::getDefaultPanel()?->getId();
        }

        if (! $this->panel || ! Wirechat::getPanel($this->panel)) {
            throw NoPanelProvidedException::make();
        }

        app(\Wirechat\Wirechat\PanelRegistry::class)->setCurrent($this->panel);
    }

    #[Computed(cache: false)]
    public function panel(): ?Panel
    {
        return $this->panel ? Wirechat::getPanel($this->panel) : null;
    }
}
