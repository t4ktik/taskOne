<div class="col-12">
    @if(count($tasks) > 0)
        <div class="task-table-wrap">
            <table class="table task-table align-items-center mb-0">
                <caption class="sr-only">{{ __('Tasks') }}</caption>
                <thead>
                <tr>
                    <th scope="col">{{ __('Key') }}</th>
                    <th scope="col">{{ __('Summary') }}</th>
                    <th scope="col">{{ __('Project') }}</th>
                    <th scope="col">{{ __('Status') }}</th>
                    <th scope="col">{{ __('Priority') }}</th>
                    <th scope="col">{{ __('Due') }}</th>
                    <th scope="col">{{ __('Assigned To') }}</th>
                    <th scope="col">{{ __('Completion') }}</th>
                    <th scope="col"><span class="sr-only">{{ __('Activity') }}</span></th>
                </tr>
                </thead>
                <tbody>
                @foreach($tasks as $task)
                    @php
                        $permissions = \Auth::user()->getPermission($task->project_id);
                        $canShow = is_array($permissions) && in_array('show task', $permissions);
                        $showUrl = route('projects.tasks.show', [$task->project_id, $task->id]);
                        $dueLabel = \App\Utility::getDateFormated($task->end_date);
                        $overdue = $dueLabel !== '' && strtotime($task->end_date) < strtotime(date('Y-m-d')) && !$task->is_complete;
                        $progress = $task->taskProgress();
                        $progressValue = (int) str_replace('%', '', $progress['percentage']);
                    @endphp
                    <tr class="task-row">
                        <td data-label="{{ __('Key') }}">
                            @if($canShow)
                                <button type="button" class="task-issue-card__key" data-ajax-popup-right="true" data-url="{{ $showUrl }}">T-{{ $task->id }}</button>
                            @else
                                <span class="task-issue-card__key">T-{{ $task->id }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('Summary') }}">
                            @if($canShow)
                                <button type="button" class="task-row__summary task-issue-card__title" data-ajax-popup-right="true" data-url="{{ $showUrl }}">{{ $task->name }}</button>
                            @else
                                <span class="task-row__summary">{{ $task->name }}</span>
                            @endif
                            @if($task->is_favourite)
                                <span class="task-fav-icon" title="{{ __('Starred') }}"><i class="fas fa-star" aria-hidden="true"></i><span class="sr-only">{{ __('Starred') }}</span></span>
                            @endif
                        </td>
                        <td data-label="{{ __('Project') }}">
                            @if(optional($task->project)->id)
                                <a class="task-row__project" href="{{ route('projects.tasks.index', $task->project_id) }}">{{ $task->project->name }}</a>
                                <span class="badge badge-xs badge-{{ (\Auth::user()->checkProject($task->project_id) == 'Owner') ? 'success' : 'warning' }}">{{ __(\Auth::user()->checkProject($task->project_id)) }}</span>
                            @else
                                <span class="task-row__project">{{ __('-') }}</span>
                            @endif
                        </td>
                        <td data-label="{{ __('Status') }}">
                            @if(optional($task->stage)->name)
                                <span class="task-status"><span class="task-status__dot" aria-hidden="true"></span>{{ $task->stage->name }}</span>
                            @else
                                {{ __('-') }}
                            @endif
                        </td>
                        <td data-label="{{ __('Priority') }}">
                            @include('tasks.partials.priority', ['priorityKey' => $task->priority])
                        </td>
                        <td data-label="{{ __('Due') }}">
                            @if($dueLabel !== '')
                                <span class="task-due {{ $overdue ? 'is-overdue' : '' }}">{{ $dueLabel }}</span>
                            @else
                                {{ __('-') }}
                            @endif
                        </td>
                        <td data-label="{{ __('Assigned To') }}">
                            @include('tasks.partials.assignees', ['task' => $task])
                        </td>
                        <td data-label="{{ __('Completion') }}">
                            <div class="d-flex align-items-center" style="gap: 0.4rem; min-width: 88px;">
                                <span>{{ $progress['percentage'] }}</span>
                                <div class="task-progress flex-fill mb-0" role="progressbar" aria-valuenow="{{ $progressValue }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Completion') }}">
                                    <span style="width: {{ $progressValue }}%;"></span>
                                </div>
                            </div>
                        </td>
                        <td data-label="{{ __('Activity') }}">
                            <span class="task-meta" title="{{ __('Attachments') }}"><i class="fas fa-paperclip" aria-hidden="true"></i><span class="sr-only">{{ __('Attachments') }}</span>{{ count($task->taskFiles) }}</span>
                            <span class="task-meta" title="{{ __('Comments') }}"><i class="fas fa-comment-alt" aria-hidden="true"></i><span class="sr-only">{{ __('Comments') }}</span>{{ count($task->comments) }}</span>
                            <span class="task-meta" title="{{ __('Checklist') }}"><i class="fas fa-tasks" aria-hidden="true"></i><span class="sr-only">{{ __('Checklist') }}</span>{{ $task->countTaskChecklist() }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        @include('tasks.partials.empty-state', [
            'icon' => 'fa-clipboard-list',
            'title' => __('Nothing matches these filters'),
            'message' => __('Try a different search or clear the filters.'),
        ])
    @endif
</div>
