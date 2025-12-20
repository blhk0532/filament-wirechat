<?php

namespace AdultDate\FilamentWirechat\Livewire\New;

use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;
use AdultDate\FilamentWirechat\Livewire\Concerns\HasPanel;
use AdultDate\FilamentWirechat\Livewire\Concerns\ModalComponent;
use AdultDate\FilamentWirechat\Livewire\Concerns\Widget;
use AdultDate\FilamentWirechat\Livewire\Widgets\Wirechat as WidgetsWirechat;

class Group extends ModalComponent
{
    use HasPanel;
    use Widget;
    use WithFileUploads;

    public $users = [];

    public $search;

    public $selectedMembers;

    #[Validate('required')]
    #[Validate('max:120')]
    public $name;

    #[Validate('nullable')]
    #[Validate('max:500')]
    public $description;

    #[Validate('image|max:12024|nullable|mimes:png,jpg,jpeg,webp')] // 1MB Max
    public $photo = null;

    public bool $showAddMembers = false;

    public function messages(): array
    {
        return [
            'name.required' => __('filament-wirechat::validation.required', ['attribute' => __('filament-wirechat::chat.group.info.inputs.name.label')]),
            'name.max' => __('filament-wirechat::validation.max.string', ['attribute' => __('filament-wirechat::chat.group.info.inputs.name.label')]),
            'description.max' => __('filament-wirechat::validation.max.string', ['attribute' => __('filament-wirechat::chat.group.info.inputs.description.label')]),
            'photo.max' => __('filament-wirechat::validation.max.file', ['attribute' => __('filament-wirechat::chat.group.info.inputs.photo.label')]),
            'photo.image' => __('filament-wirechat::validation.image', ['attribute' => __('filament-wirechat::chat.group.info.inputs.photo.label')]),
            'photo.mimes' => __('filament-wirechat::validation.mimes', ['attribute' => __('filament-wirechat::chat.group.info.inputs.photo.label')]),
        ];
    }

    public function deletePhoto()
    {

        // delete from tmp-folder
        // $this->removeUpload('photo', $this->photo->temporaryUrl());

        // delete photo
        $this->reset('photo');
    }

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => false,
            'closeOnEscapeIsForceful' => false,
            'destroyOnClose' => false,
            'closeOnClickAway' => false,
        ];

    }

    /**
     * Search For users to create conversations with
     */
    public function updatedSearch()
    {

        // Make sure it's not empty
        if (blank($this->search)) {

            $this->users = [];
        } else {

            $this->users = $this->searchUsers($this->search)->resolve();

        }
    }

    // Add members to selectedMembers list
    public function addMember($id, string $class)
    {
        try {
            $model = app($class);

            $model = $model::find($id);

            if ($model) {
                if ($model && ! $this->selectedMembers->contains($model)) {
                    $this->selectedMembers->push($model);
                }
            }
        } catch (\Throwable $th) {

            throw $th;
        }
    }

    // Remove Member from   selectedMembers list
    public function removeMember($id, string $class)
    {
        // Filter out the member with the specified ID and class
        $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
            return $member->id == $id && get_class($member) == $class;
        });
    }

    public function toggleMember($id, string $class)
    {

        $model = app($class)->find($id);

        if ($model) {
            if ($this->selectedMembers->contains(fn ($member) => $member->id == $model->id && get_class($member) == get_class($model))) {
                // Remove member if they are already selected
                $this->selectedMembers = $this->selectedMembers->reject(function ($member) use ($id, $class) {
                    return $member->id == $id && $member->getMorphClass() == $class;
                });
            } else {

                // validte members count
                if (count($this->selectedMembers) >= $this->getMaxGroupMembers()) {
                    return $this->dispatch('show-member-limit-error');
                }

                // Add member if they are not selected
                $this->selectedMembers->push($model);

            }

        }
    }

    public function validateDetails()
    {

        $this->validate();

        // if validation passed then show members to true
        $this->showAddMembers = true;
    }

    /** * Create group */
    public function create()
    {

        $this->validate();

        // create group
        /* @var $conversation */
        $conversation = auth()->user()->createGroup($this->name, $this->description, $this->photo);

        // Add participants
        foreach ($this->selectedMembers as $key => $participant) {

            // make sure user does not belong to conversation already
            // mostly this is the auth user
            $alreadyExists = $conversation->participants()->where('participantable_id', $participant->id)->where('participantable_type', $participant->getMorphClass())->exists();
            if (! $alreadyExists) {
                $conversation->addParticipant($participant);
            }
        }

        // close dialog
        // The froce close is importnat because it will close all dialogs including parents or children
        $this->forceClose();
        $this->closeWirechatModal();

        // redirect to conversation
        $this->handleComponentTermination(
            redirectRoute: $this->chatRoute($conversation->id),
            events: [
                WidgetsWirechat::class => ['open-chat',  ['conversation' => $conversation->id]],
            ]
        );

    }

    public function mount()
    {

        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->canCreateGroups(), 403, 'You do not have permission to create groups.');

        $this->selectedMembers = collect();
    }

    public function render()
    {

        return view('filament-wirechat::livewire.new.group', ['maxGroupMembers' => $this->getMaxGroupMembers()]);
    }
}
