<?php

namespace AdultDate\FilamentWirechat\Livewire\Pages;

use Livewire\Attributes\Title;
use Livewire\Component;
use AdultDate\FilamentWirechat\Livewire\Concerns\HasPanel;

class Chats extends Component
{
    use HasPanel;

    #[Title('Chats')]
    public function render()
    {

        return view('filament-wirechat::livewire.pages.chats')
            ->layout($this->panel()->getLayout());

    }
}
