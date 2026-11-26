@php
    $permissions = $permissions ?? [];
    $draggable = !empty($draggable);
@endphp
<div class="card card-progress task-issue-card {{ $draggable ? 'draggable-item' : '' }} border shadow-none"
     id="{{ $task->id }}"
     data-priority="{{ $task->priority }}"
     @if(!empty($task->priority_color)) style="border-left: 3px solid {{ $task->priority_color }} !important" @endif>
    @include('tasks.partials.issue-card-body', [
        'task' => $task,
        'permissions' => $permissions,
        'showProject' => !empty($showProject),
        'showStage' => !empty($showStage),
        'stages' => $stages ?? null,
    ])
</div>
