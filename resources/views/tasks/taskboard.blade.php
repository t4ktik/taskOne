@extends('layouts.admin')

@section('title')
    {{ __('Tasks') }}
@endsection

@section('content')
    <div class="task-ui">
        <div class="card task-toolbar-card">
            <div class="card-body task-toolbar">
                <div class="task-toolbar__row">
                    <div class="task-view-switch" role="group" aria-label="{{ __('View') }}">
                        <a href="{{ route('taskBoard.view', 'list') }}" class="task-view-switch__btn {{ $view == 'list' ? 'is-active' : '' }}" @if($view == 'list') aria-current="page" @endif>
                            <i class="fas fa-list" aria-hidden="true"></i>{{ __('List View') }}
                        </a>
                        <a href="{{ route('taskBoard.view', 'grid') }}" class="task-view-switch__btn {{ $view == 'grid' ? 'is-active' : '' }}" @if($view == 'grid') aria-current="page" @endif>
                            <i class="fas fa-th-large" aria-hidden="true"></i>{{ __('Card View') }}
                        </a>
                    </div>
                    <div class="task-search">
                        <label class="sr-only" for="task_keyword">{{ __('Search by Name') }}</label>
                        <i class="fas fa-search task-search__icon" aria-hidden="true"></i>
                        <input type="search" id="task_keyword" placeholder="{{ __('Search by Name') }}" autocomplete="off">
                        <button type="button" id="task_keyword_clear" class="task-search__clear" aria-label="{{ __('Clear search') }}" hidden>&times;</button>
                        <kbd class="task-kbd" aria-hidden="true">/</kbd>
                    </div>
                    <div class="dropdown">
                        <button type="button" class="task-sort-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-sort-amount-down" aria-hidden="true"></i> {{ __('Sort') }}
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" id="task_sort">
                            <a class="dropdown-item active" href="#" data-val="created_at-desc">{{ __('Newest') }}</a>
                            <a class="dropdown-item" href="#" data-val="created_at-asc">{{ __('Oldest') }}</a>
                            <a class="dropdown-item" href="#" data-val="name-asc">{{ __('From A-Z') }}</a>
                            <a class="dropdown-item" href="#" data-val="name-desc">{{ __('From Z-A') }}</a>
                        </div>
                    </div>
                </div>
                <div class="task-filter-actions task-filters" role="toolbar" aria-label="{{ __('Quick filters') }}">
                    <span class="task-filters__label">{{ __('Quick filters') }}</span>
                    <button type="button" class="task-chip filter-action filter-show-all" aria-pressed="false">{{ __('Show All') }}</button>
                    <button type="button" class="task-chip filter-action active" data-val="see_my_tasks" aria-pressed="true">{{ __('See My Tasks') }}</button>
                    @foreach(\App\ProjectTask::$priority as $key => $val)
                        <button type="button" class="task-chip filter-action" data-val="{{ $key }}" aria-pressed="false">{{ __($val) }}</button>
                    @endforeach
                    <button type="button" class="task-chip filter-action filter-other" data-val="due_today" aria-pressed="false">{{ __('Due Today') }}</button>
                    <button type="button" class="task-chip filter-action filter-other" data-val="over_due" aria-pressed="false">{{ __('Over Due') }}</button>
                    <button type="button" class="task-chip filter-action filter-other" data-val="starred" aria-pressed="false">{{ __('Starred') }}</button>
                </div>
                <p class="task-hint">{{ __('Press / to search.') }}</p>
                <p id="task-result-status" class="sr-only" aria-live="polite"></p>
            </div>
        </div>
        <div class="row task-board-results" id="taskboard_view" aria-busy="false"></div>
    </div>
@endsection

@push('script')
    <script>
        $(function () {
            var sort = 'created_at-desc';
            var status = ['see_my_tasks'];
            var requestSeq = 0;
            var searchTimer = null;
            var loadingText = @json(__('Loading tasks'));
            var errorTitle = @json(__('Could not load tasks.'));
            var retryText = @json(__('Retry'));
            var updatedText = @json(__('Results updated.'));

            function skeleton() {
                return '<div class="col-12"><div class="task-state" role="status"><span class="task-spinner" aria-hidden="true"></span><span>' + loadingText + '</span></div></div>';
            }

            function ajaxFilterTaskView(task_sort, keyword, taskStatus) {
                var seq = ++requestSeq;
                var mainEle = $('#taskboard_view');
                var first = mainEle.find('.task-row, .task-issue-card, .task-state').length === 0;
                mainEle.attr('aria-busy', 'true').addClass('is-loading');
                if (first) {
                    mainEle.html(skeleton());
                }

                $.ajax({
                    url: '{{ route('project.taskboard.view') }}',
                    data: {
                        view: @json($view),
                        sort: task_sort,
                        keyword: keyword,
                        status: taskStatus
                    },
                    success: function (data) {
                        if (seq !== requestSeq) {
                            return;
                        }
                        mainEle.removeClass('is-loading').attr('aria-busy', 'false').html(data.html || '');
                        var empty = mainEle.find('.task-state h3').first().text();
                        $('#task-result-status').text(empty || updatedText);
                    },
                    error: function () {
                        if (seq !== requestSeq) {
                            return;
                        }
                        mainEle.removeClass('is-loading').attr('aria-busy', 'false').html(
                            '<div class="col-12"><div class="task-state task-state--error" role="alert"><h3>' + errorTitle + '</h3><button type="button" class="btn btn-sm btn-primary mt-2" id="task_retry">' + retryText + '</button></div></div>'
                        );
                    }
                });
            }

            ajaxFilterTaskView(sort, '', status);

            $(".task-filter-actions").on('click', '.filter-action', function (e) {
                e.preventDefault();
                if ($(this).hasClass('filter-show-all')) {
                    $('.filter-action').removeClass('active').attr('aria-pressed', 'false');
                    $(this).addClass('active').attr('aria-pressed', 'true');
                } else {
                    $('.filter-show-all').removeClass('active').attr('aria-pressed', 'false');
                    if ($(this).hasClass('filter-other')) {
                        $('.filter-other').removeClass('active').attr('aria-pressed', 'false');
                    }
                    if ($(this).hasClass('active')) {
                        $(this).removeClass('active').attr('aria-pressed', 'false');
                    } else {
                        $(this).addClass('active').attr('aria-pressed', 'true');
                    }
                }

                var filterArray = [];
                $('div.task-filter-actions').find('.active').each(function () {
                    var val = $(this).attr('data-val');
                    if (val) {
                        filterArray.push(val);
                    }
                });
                status = filterArray;
                ajaxFilterTaskView(sort, $('#task_keyword').val(), status);
            });

            $('#task_sort').on('click', 'a', function (e) {
                e.preventDefault();
                sort = $(this).attr('data-val');
                ajaxFilterTaskView(sort, $('#task_keyword').val(), status);
                $('#task_sort a').removeClass('active');
                $(this).addClass('active');
            });

            $(document).on('input', '#task_keyword', function () {
                var value = $(this).val();
                $('#task_keyword_clear').prop('hidden', value.length === 0);
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    ajaxFilterTaskView(sort, value, status);
                }, 250);
            });

            $(document).on('click', '#task_keyword_clear, #task_retry', function () {
                if (this.id === 'task_keyword_clear') {
                    $('#task_keyword').val('').trigger('focus');
                    $('#task_keyword_clear').prop('hidden', true);
                }
                ajaxFilterTaskView(sort, $('#task_keyword').val(), status);
            });

            $(document).on('keydown', function (e) {
                if (e.key === '/' && !$(e.target).is('input, textarea, select, [contenteditable="true"]')) {
                    var field = document.getElementById('task_keyword');
                    if (!field) {
                        return;
                    }
                    e.preventDefault();
                    field.focus();
                }
            });

            $('#task_keyword').on('keydown', function (e) {
                if (e.key === 'Escape' && this.value !== '') {
                    e.preventDefault();
                    this.value = '';
                    $('#task_keyword_clear').prop('hidden', true);
                    ajaxFilterTaskView(sort, '', status);
                }
            });
        });
    </script>
@endpush
