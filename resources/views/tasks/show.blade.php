<div class="modal-dialog modal-vertical modal-lg side-modal task-dialog" role="document" id="{{ $task->id }}" aria-labelledby="task-dialog-title">
    <div class="modal-content">
        <div class="modal-header align-items-start">
            <div class="col pr-2">
                <div class="task-dialog__eyebrow">
                    <span>T-{{ $task->id }}</span>
                    @if(optional($task->project)->name)
                        <span aria-hidden="true">/</span>
                        <span>{{ $task->project->name }}</span>
                    @endif
                </div>
                <div class="d-flex align-items-start">
                    <div class="custom-control custom-checkbox mt-1 mr-2">
                        <input type="checkbox" class="custom-control-input" id="complete_task" @if($task->is_complete == 1) checked @endif data-url="{{ route('change.complete', [$task->project_id, $task->id]) }}">
                        <label class="custom-control-label" for="complete_task"><span class="sr-only">{{ __('Mark task complete') }}</span></label>
                    </div>
                    <h2 class="task-dialog-title" id="task-dialog-title">{{ $task->name }}</h2>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center">
                <a href="#" class="action-item task-fav {{ ($task->is_favourite) ? 'action-favorite' : '' }}" data-url="{{ route('change.fav', [$task->project_id, $task->id]) }}" id="add_favourite" aria-pressed="{{ $task->is_favourite ? 'true' : 'false' }}" aria-label="{{ __('Mark as favorite') }}">
                    <i class="fas fa-star" aria-hidden="true"></i>
                </a>
                <button type="button" class="close ml-2" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
        <div class="scrollbar-inner">
            <div class="modal-body">
                <div class="task-props">
                    <div>
                        <span class="task-prop__label">{{ __('Status') }}</span>
                        @if(optional($task->stage)->name)
                            <span class="task-status"><span class="task-status__dot" aria-hidden="true"></span>{{ $task->stage->name }}</span>
                        @else
                            <span class="task-muted">{{ __('-') }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="task-prop__label">{{ __('Priority') }}</span>
                        <div class="d-flex align-items-center" style="gap: 0.5rem;">
                            @include('tasks.partials.priority', ['priorityKey' => $task->priority])
                            <div class="priority-color">
                                <div class="colorPickSelector" style="background-color: {{ $task->priority_color }}" role="button" tabindex="0" aria-label="{{ __('Priority') }}"></div>
                            </div>
                        </div>
                    </div>
                    <div class="task-prop--wide">
                        <span class="task-prop__label">{{ __('Assigned To') }}</span>
                        @php $assignees = $task->users(); @endphp
                        @if(count($assignees) > 0)
                            <div>
                                @foreach($assignees as $user)
                                    <span class="task-person">
                                        <span class="avatar avatar-sm rounded-circle"><img {{ $user->img_avatar }} alt=""></span>
                                        {{ $user->name }}
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <span class="task-muted">{{ __('Unassigned') }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="task-prop__label">{{ __('Start Date') }}</span>
                        <span>{{ \App\Utility::getDateFormated($task->start_date) ?: __('-') }}</span>
                    </div>
                    <div>
                        @php
                            $dueLabel = \App\Utility::getDateFormated($task->end_date);
                            $overdue = $dueLabel !== '' && strtotime($task->end_date) < strtotime(date('Y-m-d')) && !$task->is_complete;
                        @endphp
                        <span class="task-prop__label">{{ __('End Date') }}</span>
                        @if($dueLabel !== '')
                            <span class="task-due {{ $overdue ? 'is-overdue' : '' }}">{{ $dueLabel }}</span>
                        @else
                            <span class="task-muted">{{ __('-') }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="task-prop__label">{{ __('Milestone') }}</span>
                        <span>{{ (!empty($task->milestone)) ? $task->milestone->title : __('-') }}</span>
                    </div>
                    <div>
                        <span class="task-prop__label">{{ __('Estimated Hours') }}</span>
                        <span>{{ (!empty($task->estimated_hrs)) ? number_format($task->estimated_hrs) : __('-') }}</span>
                    </div>
                </div>

                @if($allow_progress == 'false')
                    <div class="task-section">
                        <div class="task-section__head">
                            <h3>{{ __('Task Progress') }}: <b id="t_percentage">{{ $task->progress }}</b>%</h3>
                        </div>
                        <input type="range" class="task_progress custom-range" min="0" max="100" value="{{ $task->progress }}" id="task_progress" name="progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $task->progress }}" aria-label="{{ __('Task Progress') }}" data-url="{{ route('change.progress', [$task->project_id, $task->id]) }}">
                    </div>
                @endif

                <div class="task-section">
                    <div class="task-section__head">
                        <h3>{{ __('Description') }}</h3>
                        <a href="#" class="btn btn-xs btn-secondary btn-icon rounded-pill" data-toggle="collapse" data-target="#overview" aria-expanded="true" aria-controls="overview">
                            <span class="btn-inner--icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                            <span class="sr-only">{{ __('Description') }}</span>
                        </a>
                    </div>
                    <div id="overview" class="collapse show">
                        @if(!empty($task->description))
                            <div class="task-description">{{ $task->description }}</div>
                        @else
                            <p class="task-muted mb-0">{{ __('No description yet.') }}</p>
                        @endif
                    </div>
                </div>

                <div class="task-section">
                    <div class="task-section__head">
                        <h3>{{ __('Checklist') }} <span class="task-muted">{{ $task->countTaskChecklist() }}</span></h3>
                        <a href="#" class="btn btn-xs btn-secondary btn-icon rounded-pill" data-toggle="collapse" data-target="#form-checklist">
                            <span class="btn-inner--icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                            <span class="btn-inner--text">{{ __('Add item') }}</span>
                        </a>
                    </div>
                    <div class="checklist" id="checklist">
                        <form method="post" id="form-checklist" class="collapse pb-2" data-action="{{ route('checklist.store', [$task->project_id, $task->id]) }}">
                            <div class="card border shadow-none">
                                <div class="px-3 py-2 row align-items-center">
                                    <div class="col-10">
                                        <label class="sr-only" for="checklist-name">{{ __('Checklist Name') }}</label>
                                        <input type="text" name="name" id="checklist-name" required class="form-control" placeholder="{{ __('Checklist Name') }}"/>
                                    </div>
                                    <div class="col-2 card-meta d-inline-flex align-items-center">
                                        <button class="btn btn-primary btn-xs" type="button" id="checklist_submit" aria-label="{{ __('Add item') }}">
                                            <i class="fas fa-plus" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        @forelse($task->checklist as $checklist)
                            <div class="card border shadow-none checklist-member">
                                <div class="px-3 py-2 row align-items-center">
                                    <div class="col-10">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="check-item-{{ $checklist->id }}" @if($checklist->status) checked @endif data-url="{{ route('checklist.update', [$task->project_id, $checklist->id]) }}">
                                            <label class="custom-control-label h6 text-sm" for="check-item-{{ $checklist->id }}">{{ $checklist->name }}</label>
                                        </div>
                                    </div>
                                    <div class="col-auto card-meta d-inline-flex align-items-center ml-sm-auto">
                                        <a href="#" class="action-item delete-checklist" data-url="{{ route('checklist.destroy', [$task->project_id, $checklist->id]) }}" aria-label="{{ __('Delete') }}">
                                            <i class="fas fa-trash-alt text-danger" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="task-muted" id="checklist-empty">{{ __('No checklist items yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="task-section">
                    <div class="task-section__head">
                        <h3>{{ __('Attachments') }}</h3>
                        <a href="#" class="btn btn-xs btn-secondary btn-icon rounded-pill" data-toggle="collapse" data-target="#add_file">
                            <span class="btn-inner--icon"><i class="fas fa-plus" aria-hidden="true"></i></span>
                            <span class="btn-inner--text">{{ __('Add item') }}</span>
                        </a>
                    </div>
                    <div class="card mb-3 border shadow-none collapse" id="add_file">
                        <div class="card border-0 shadow-none mb-0">
                            <div class="px-3 py-2 row align-items-center">
                                <div class="col-10">
                                    <input type="file" name="task_attachment" id="task_attachment" required class="custom-input-file"/>
                                    <label for="task_attachment">
                                        <i class="fa fa-upload" aria-hidden="true"></i>
                                        <span class="attachment_text">{{ __('Choose a file…') }}</span>
                                    </label>
                                </div>
                                <div class="col-2 card-meta d-inline-flex align-items-center">
                                    <button class="btn btn-primary btn-xs" type="button" id="file_submit" data-action="{{ route('comment.store.file', [$task->project_id, $task->id]) }}" aria-label="{{ __('Add item') }}">
                                        <i class="fas fa-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="comments-file">
                        @foreach($task->taskFiles as $file)
                            <div class="card mb-3 border shadow-none task-file">
                                <div class="px-3 py-3">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <img src="{{ asset('assets/img/icons/files/'.$file->extension.'.png') }}" alt="" class="img-fluid" style="width: 40px;">
                                        </div>
                                        <div class="col ml-n2">
                                            <h3 class="text-sm mb-0">{{ $file->name }}</h3>
                                            <p class="card-text small text-muted mb-0">{{ $file->file_size }}</p>
                                        </div>
                                        <div class="col-auto actions">
                                            <a href="{{ asset(Storage::url('tasks/'.$file->file)) }}" download class="action-item" role="button" aria-label="{{ __('Download') }}">
                                                <i class="fas fa-download" aria-hidden="true"></i>
                                            </a>
                                            @auth('web')
                                                <a href="#" class="action-item delete-comment-file" role="button" data-url="{{ route('comment.destroy.file', [$task->project_id, $task->id, $file->id]) }}" aria-label="{{ __('Delete') }}">
                                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                                </a>
                                            @endauth
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="task-section">
                    <div class="task-section__head">
                        <h3>{{ __('Activity') }}</h3>
                    </div>
                    @if(count($task->activity_log()) > 0)
                        <div class="list-group list-group-flush mb-0">
                            @foreach($task->activity_log() as $activity)
                                <div class="list-group-item px-0">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="avatar avatar-sm">
                                                <img {{ $activity->user->img_avatar }} alt="{{ optional($activity->user)->name }}" class="avatar avatar-sm rounded-circle">
                                            </span>
                                        </div>
                                        <div class="col ml-n2">
                                            <span class="text-dark text-sm">{{ __($activity->log_type) }}</span>
                                            <span class="d-block h6 text-sm font-weight-light mb-0">{!! $activity->getRemark() !!}</span>
                                            <small class="d-block">{{ $activity->created_at->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="task-muted mb-0">{{ __('No activity yet') }}</p>
                    @endif
                </div>

                <div class="task-section">
                    <div class="task-section__head">
                        <h3>{{ __('Comments') }}</h3>
                    </div>
                    @if(count($task->comments) === 0)
                        <p class="task-muted" id="comments-empty">{{ __('No comments yet') }}</p>
                    @endif
                    <div class="list-group list-group-flush mb-0" id="comments">
                        @foreach($task->comments as $comment)
                            <div class="list-group-item px-0">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar avatar-sm rounded-circle">
                                            <img {{ $comment->user->img_avatar }} alt="{{ $comment->user->name }}">
                                        </span>
                                    </div>
                                    <div class="col ml-n2">
                                        <p class="d-block h6 text-sm font-weight-light mb-0 text-break">{{ $comment->comment }}</p>
                                        <small class="d-block">{{ $comment->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="col-auto">
                                        <a href="#" class="delete-comment" data-url="{{ route('comment.destroy', [$task->project_id, $task->id, $comment->id]) }}" aria-label="{{ __('Delete') }}"><i class="fas fa-trash-alt text-danger" aria-hidden="true"></i></a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="task-comment-compose">
                <img {{ Auth::user()->img_avatar }} alt="{{ Auth::user()->name }}" class="avatar rounded-circle avatar-sm">
                <form method="post" class="card-comment-box flex-fill mb-0" id="form-comment" data-action="{{ route('comment.store', [$task->project_id, $task->id]) }}">
                    <label class="sr-only" for="task-comment">{{ __('Add a comment...') }}</label>
                    <textarea rows="1" class="form-control" id="task-comment" name="comment" data-toggle="autosize" placeholder="{{ __('Add a comment...') }}"></textarea>
                </form>
                <a href="#" id="comment_submit" class="task-comment-send" aria-label="{{ __('Add a comment...') }}"><i class="fas fa-paper-plane" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $(".colorPickSelector").colorPick({
            'onColorSelected': function () {
                var task_id = this.element.parents('.side-modal').attr('id');
                var color = this.color;

                if (task_id) {
                    this.element.css({'backgroundColor': color});
                    $.ajax({
                        url: '{{ route('update.task.priority.color') }}',
                        method: 'PATCH',
                        data: {
                            'task_id': task_id,
                            'color': color,
                        },
                        success: function () {
                            $('.task-list-items').find('#' + task_id).attr('style', 'border-left:3px solid ' + color + ' !important');
                        }
                    });
                }
            }
        });
    });
</script>
