@extends('layouts.admin')

@section('title')
    {{ $project->name.__("'s Tasks") }}
@endsection

@push('theme-script')
    <script src="{{ asset('assets/libs/dragula/dist/dragula.min.js') }}"></script>
@endpush

@section('action-button')
    <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-white rounded-circle btn-icon-only ml-2" aria-label="{{ __('Back') }}">
        <span class="btn-inner--icon"><i class="fas fa-arrow-left" aria-hidden="true"></i></span>
    </a>
@endsection

@php
    $permissions = \Auth::user()->getPermission($project->id);
    $taskTotal = 0;
    foreach ($stages as $stageCount) {
        $taskTotal += $stageCount->tasks->count();
    }
    $columnTones = ['slate', 'blue', 'amber', 'green', 'teal', 'purple'];
@endphp

@section('content')
    <div class="task-ui">
        <div class="card task-toolbar-card mb-3">
            <div class="card-body py-3">
                <p class="mb-1 font-weight-bold" style="color: var(--task-text);"><span id="task-board-total">{{ $taskTotal }}</span> {{ __('tasks') }}</p>
                <p class="task-hint mb-0">{{ __('Drag cards between columns to change their status.') }} {{ __('Keyboard users can move a task from its menu.') }}</p>
            </div>
        </div>
        <div id="task-board-live" class="sr-only" aria-live="polite"></div>
        <div class="task-board-scroll">
            <div class="kanban-board task-board" @if(isset($permissions) && in_array('move task', $permissions)) data-plugin="dragula" @endif data-containers='{{ json_encode($stageClass) }}'>
                @foreach($stages as $stage)
                    @php
                        $tone = !empty($stage->complete) ? 'green' : $columnTones[$loop->index % count($columnTones)];
                    @endphp
                    <section class="kanban-col task-column" aria-labelledby="stage-title-{{ $stage->id }}">
                        <div class="task-column__head">
                            <span class="task-column__dot task-column__dot--{{ $tone }}" aria-hidden="true"></span>
                            <h2 class="task-column__title" id="stage-title-{{ $stage->id }}">{{ $stage->name }}</h2>
                            <span class="count task-column__count">{{ $stage->tasks->count() }}</span>
                            @if(isset($permissions) && in_array('create task', $permissions))
                                <a class="task-column__add" href="#" data-url="{{ route('projects.tasks.create', [$stage->project_id, $stage->id]) }}" data-ajax-popup="true" data-size="lg" data-title="{{ __('Add Task in ').$stage->name }}" aria-label="{{ __('Add Task in ').$stage->name }}">
                                    <i class="fas fa-plus" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                        <div class="card-list-body task-list-items task-column__body" id="task-list-{{ $stage->id }}" data-status="{{ $stage->id }}">
                            @foreach($stage->tasks as $taskDetail)
                                @include('tasks.partials.issue-card', [
                                    'task' => $taskDetail,
                                    'permissions' => $permissions,
                                    'draggable' => isset($permissions) && in_array('move task', $permissions),
                                    'stages' => $stages,
                                ])
                            @endforeach
                            <div class="task-column__empty" @if($stage->tasks->count() > 0) hidden @endif>{{ __('No tasks in this column') }}</div>
                            <span class="empty-container" data-placeholder="Empty"></span>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        !function (a) {
            "use strict";
            var t = function () {
                this.$body = a("body")
            };
            t.prototype.init = function () {
                a('[data-plugin="dragula"]').each(function () {
                    var t = a(this).data("containers"), n = [];
                    if (t) for (var i = 0; i < t.length; i++) n.push(a("#" + t[i])[0]); else n = [a(this)[0]];
                    dragula(n, {
                        moves: function (el) {
                            return el.classList && el.classList.contains('task-issue-card');
                        }
                    }).on('drop', function (el, target, source) {
                        if (!target || !source) {
                            return;
                        }
                        var sort = [];
                        $("#" + target.id + " > .task-issue-card").each(function (index) {
                            sort[index] = $(this).attr('id');
                        });

                        var id = el.id;
                        var old_stage = $("#" + source.id).data('status');
                        var new_stage = $("#" + target.id).data('status');
                        var project_id = '{{ $project->id }}';

                        if (window.taskUiRefreshColumn) {
                            window.taskUiRefreshColumn($("#" + source.id));
                            window.taskUiRefreshColumn($("#" + target.id));
                        }
                        if (window.taskUiSyncMoveMenu) {
                            window.taskUiSyncMoveMenu($(el), new_stage);
                        }

                        $.ajax({
                            url: '{{ route('tasks.update.order', [$project->id]) }}',
                            type: 'PATCH',
                            data: {id: id, sort: sort, new_stage: new_stage, old_stage: old_stage, project_id: project_id},
                            success: function () {
                                $('#task-board-live').text(@json(__('Task moved.')));
                            },
                            error: function () {
                                var $source = $("#" + source.id);
                                var $empty = $source.children('.empty-container');
                                if ($empty.length) {
                                    $(el).insertBefore($empty);
                                } else {
                                    $source.append(el);
                                }
                                if (window.taskUiRefreshColumn) {
                                    window.taskUiRefreshColumn($("#" + source.id));
                                    window.taskUiRefreshColumn($("#" + target.id));
                                }
                                if (window.taskUiSyncMoveMenu) {
                                    window.taskUiSyncMoveMenu($(el), old_stage);
                                }
                                show_toastr(@json(__('Error')), @json(__('Could not move the task.')), 'error');
                            }
                        });
                    });
                })
            }, a.Dragula = new t, a.Dragula.Constructor = t
        }(window.jQuery), function (a) {
            "use strict";
            a.Dragula.init()
        }(window.jQuery);
    </script>
@endpush
