@php
    $permissions = $permissions ?? [];
    $showProject = !empty($showProject);
    $showStage = !empty($showStage);
    $canShow = is_array($permissions) && in_array('show task', $permissions);
    $canEdit = is_array($permissions) && in_array('edit task', $permissions);
    $canDelete = is_array($permissions) && in_array('delete task', $permissions);
    $canMove = is_array($permissions) && in_array('move task', $permissions) && !empty($stages) && count($stages) > 0;
    $progress = $task->taskProgress();
    $progressValue = (int) str_replace('%', '', $progress['percentage']);
    $dueLabel = \App\Utility::getDateFormated($task->end_date);
    $overdue = $dueLabel !== '' && strtotime($task->end_date) < strtotime(date('Y-m-d')) && !$task->is_complete;
    $assignees = $task->users();
    $showUrl = route('projects.tasks.show', [$task->project_id, $task->id]);
@endphp
<div class="card-body task-issue-card__body {{ $task->is_complete ? 'is-complete' : '' }}">
    <div class="task-issue-card__top">
        <div class="d-flex align-items-center flex-wrap" style="gap: 0.35rem;">
            @include('tasks.partials.priority', ['priorityKey' => $task->priority])
            @if($task->is_favourite)
                <span class="task-fav-icon" title="{{ __('Starred') }}"><i class="fas fa-star" aria-hidden="true"></i><span class="sr-only">{{ __('Starred') }}</span></span>
            @endif
        </div>
        @if($canShow || $canEdit || $canDelete || $canMove)
            <div class="dropdown">
                <a href="#" class="task-actions-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('Task actions') }}">
                    <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    @if($canShow)
                        <a href="#" data-url="{{ $showUrl }}" data-ajax-popup-right="true" class="dropdown-item">{{ __('View') }}</a>
                    @endif
                    @if($canEdit)
                        <a href="#" data-url="{{ route('projects.tasks.edit', [$task->project_id, $task->id]) }}" data-ajax-popup="true" data-size="lg" data-title="{{ __('Edit ') . $task->name }}" class="dropdown-item">{{ __('Edit') }}</a>
                    @endif
                    @if($canDelete)
                        <a href="#" class="dropdown-item del_task" data-url="{{ route('projects.tasks.destroy', [$task->project_id, $task->id]) }}">{{ __('Delete') }}</a>
                    @endif
                    @if($canMove)
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">{{ __('Move to') }}</h6>
                        @foreach($stages as $moveStage)
                            <button type="button"
                                    class="dropdown-item task-move"
                                    data-task="{{ $task->id }}"
                                    data-from="{{ $task->stage_id }}"
                                    data-to="{{ $moveStage->id }}"
                                    data-project="{{ $task->project_id }}"
                                    data-url="{{ route('tasks.update.order', [$task->project_id]) }}"
                                    @if((int) $moveStage->id === (int) $task->stage_id) disabled @endif>
                                {{ $moveStage->name }}
                            </button>
                        @endforeach
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($canShow)
        <button type="button" class="task-issue-card__title" data-ajax-popup-right="true" data-url="{{ $showUrl }}" aria-label="{{ __('Open task') }} {{ $task->name }}">{{ $task->name }}</button>
    @else
        <span class="task-issue-card__title">{{ $task->name }}</span>
    @endif

    <div class="task-issue-card__tags">
        @if($showStage && optional($task->stage)->name)
            <span class="task-status"><span class="task-status__dot" aria-hidden="true"></span>{{ $task->stage->name }}</span>
        @endif
        @if($showProject && optional($task->project)->name)
            <span class="task-tag">{{ $task->project->name }}</span>
        @endif
        @if(optional($task->milestone)->title)
            <span class="task-tag">{{ $task->milestone->title }}</span>
        @endif
    </div>

    @if($progressValue > 0)
        <div class="task-progress" role="progressbar" aria-valuenow="{{ $progressValue }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Completion') }}">
            <span style="width: {{ $progressValue }}%;"></span>
        </div>
    @endif

    <div class="task-issue-card__footer">
        <div>
            @if($dueLabel !== '')
                <span class="task-due {{ $overdue ? 'is-overdue' : '' }}">
                    <i class="far fa-calendar-alt" aria-hidden="true"></i>
                    <span class="sr-only">{{ $overdue ? __('Overdue') : __('Due') }}</span>
                    {{ $dueLabel }}
                </span>
            @endif
            <span class="d-block mt-1">
                @if(count($task->taskFiles) > 0)
                    <span class="task-meta"><i class="fas fa-paperclip" aria-hidden="true"></i><span class="sr-only">{{ __('Attachments') }}</span>{{ count($task->taskFiles) }}</span>
                @endif
                @if(count($task->comments) > 0)
                    <span class="task-meta"><i class="fas fa-comment-alt" aria-hidden="true"></i><span class="sr-only">{{ __('Comments') }}</span>{{ count($task->comments) }}</span>
                @endif
                @if($task->checklist->count() > 0)
                    <span class="task-meta"><i class="fas fa-tasks" aria-hidden="true"></i><span class="sr-only">{{ __('Checklist') }}</span>{{ $task->countTaskChecklist() }}</span>
                @endif
            </span>
        </div>
        @include('tasks.partials.assignees', ['assignees' => $assignees])
    </div>
    <div class="mt-2">
        @if($canShow)
            <button type="button" class="task-issue-card__key" data-ajax-popup-right="true" data-url="{{ $showUrl }}">T-{{ $task->id }}</button>
        @else
            <span class="task-issue-card__key">T-{{ $task->id }}</span>
        @endif
    </div>
</div>
