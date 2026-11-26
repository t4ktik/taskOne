<div class="col-12">
    @if(count($tasks) > 0)
        <div class="task-card-grid">
            @foreach($tasks as $task)
                @php
                    $permissions = \Auth::user()->getPermission($task->project_id);
                @endphp
                @include('tasks.partials.issue-card', [
                    'task' => $task,
                    'permissions' => $permissions,
                    'draggable' => false,
                    'showProject' => true,
                    'showStage' => true,
                ])
            @endforeach
        </div>
    @else
        @include('tasks.partials.empty-state', [
            'icon' => 'fa-clipboard-list',
            'title' => __('Nothing matches these filters'),
            'message' => __('Try a different search or clear the filters.'),
        ])
    @endif
</div>
