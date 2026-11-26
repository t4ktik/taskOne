<script>
    window.taskUiRefreshColumn = function ($list) {
        if (!$list || !$list.length) {
            return;
        }
        var count = $list.children('.task-issue-card').length;
        $list.parent().find('.count').first().text(count);
        $list.children('.task-column__empty').prop('hidden', count !== 0);
        if ($('#task-board-total').length) {
            $('#task-board-total').text($('.task-board .task-issue-card').length);
        }
    };

    window.taskUiSyncMoveMenu = function ($card, stageId) {
        stageId = String(stageId);
        $card.find('.task-move').each(function () {
            var to = String($(this).attr('data-to'));
            $(this).attr('data-from', stageId).prop('disabled', to === stageId);
        });
    };

    var now = @json(__('Now'));

    function taskUiEscape(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    $(document).on('click', '.task-ui a[href="#"], .task-dialog a[href="#"]', function (e) {
        e.preventDefault();
    });

    $(document).on('keydown', '.colorPickSelector', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

    $('#commonModal-right').on('shown.bs.modal', function () {
        var title = $(this).find('.task-dialog-title').first();
        if (title.length) {
            $(this).attr('aria-labelledby', title.attr('id') || 'task-dialog-title');
            title.attr('tabindex', '-1').trigger('focus');
        }
    });

    $(document).on('click', '.task-move', function (e) {
        e.preventDefault();
        var $btn = $(this);
        if ($btn.is(':disabled')) {
            return;
        }
        var newStage = $btn.attr('data-to');
        var oldStage = $btn.attr('data-from');
        var $card = $btn.closest('.task-issue-card');
        var $target = $('#task-list-' + newStage);
        var $source = $('#task-list-' + oldStage);
        if (!$card.length || !$target.length) {
            return;
        }
        $card.addClass('is-saving');
        $.ajax({
            url: $btn.attr('data-url'),
            type: 'PATCH',
            data: {
                id: $btn.attr('data-task'),
                sort: [$btn.attr('data-task')],
                new_stage: newStage,
                old_stage: oldStage,
                project_id: $btn.attr('data-project')
            },
            success: function () {
                var $empty = $target.children('.empty-container');
                if ($empty.length) {
                    $card.insertBefore($empty);
                } else {
                    $target.append($card);
                }
                window.taskUiSyncMoveMenu($card, newStage);
                window.taskUiRefreshColumn($source);
                window.taskUiRefreshColumn($target);
                $('#task-board-live').text(@json(__('Task moved.')));
                show_toastr(@json(__('Success')), @json(__('Task moved.')), 'success');
            },
            error: function () {
                show_toastr(@json(__('Error')), @json(__('Could not move the task.')), 'error');
            },
            complete: function () {
                $card.removeClass('is-saving');
            }
        });
    });

    $(document).ready(function () {
        $(document).on('click', '.add_usr', function () {
            var ids = [];
            $(this).toggleClass('selected');
            var crr_id = $(this).attr('data-id');
            $('#usr_txt_' + crr_id).html($('#usr_txt_' + crr_id).html() == 'Add' ? @json(__('Added')) : @json(__('Add')));
            if ($('#usr_icon_' + crr_id).hasClass('fa-plus')) {
                $('#usr_icon_' + crr_id).removeClass('fa-plus').addClass('fa-check');
            } else {
                $('#usr_icon_' + crr_id).removeClass('fa-check').addClass('fa-plus');
            }
            $('.selected').each(function () {
                ids.push($(this).attr('data-id'));
            });
            $('input[name="assign_to"]').val(ids);
        });

        $(document).on('keydown', '#form-checklist input[name=name]', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#checklist_submit').trigger('click');
            }
        });

        $(document).on("click", ".del_task", function () {
            var id = $(this);
            var $list = id.closest('.task-issue-card').closest('.task-list-items');
            $.ajax({
                url: $(this).attr('data-url'),
                type: 'DELETE',
                dataType: 'JSON',
                success: function (data) {
                    $('#' + data.task_id).remove();
                    if ($list.length) {
                        window.taskUiRefreshColumn($list);
                    }
                    show_toastr(@json(__('Success')), @json(__('Task Deleted Successfully!')), 'success');
                }
            });
        });

        $(document).on('click', '#comment_submit', function (e) {
            var curr = $(this);
            var comment = $.trim($("#form-comment textarea[name='comment']").val());
            if (comment != '') {
                $.ajax({
                    url: $("#form-comment").data('action'),
                    data: {comment: comment},
                    type: 'POST',
                    success: function (data) {
                        data = JSON.parse(data);
                        var html = "<div class='list-group-item px-0'>" +
                            "                    <div class='row align-items-center'>" +
                            "                        <div class='col-auto'>" +
                            "                            <a href='#' class='avatar avatar-sm rounded-circle'>" +
                            "                                <img " + data.user.img_avatar + " alt='" + taskUiEscape(data.user.name) + "'>" +
                            "                            </a>" +
                            "                        </div>" +
                            "                        <div class='col ml-n2'>" +
                            "                            <p class='d-block h6 text-sm font-weight-light mb-0 text-break'>" + taskUiEscape(data.comment) + "</p>" +
                            "                            <small class='d-block'>" + now + "</small>" +
                            "                        </div>" +
                            "                        <div class='col-auto'><a href='#' class='delete-comment' data-url='" + taskUiEscape(data.deleteUrl) + "'><i class='fas fa-trash-alt text-danger'></i></a></div>" +
                            "                    </div>" +
                            "                </div>";

                        $("#comments").prepend(html);
                        $("#comments-empty").remove();
                        $("#form-comment textarea[name='comment']").val('');
                        load_task(curr.closest('.side-modal').attr('id'));
                        show_toastr(@json(__('Success')), @json(__('Comment Added Successfully!')), 'success');
                    },
                    error: function () {
                        show_toastr(@json(__('Error')), @json(__('Some Thing Is Wrong!')), 'error');
                    }
                });
            } else {
                show_toastr(@json(__('Error')), @json(__('Please write comment!')), 'error');
            }
        });

        $(document).on("click", ".delete-comment", function () {
            var btn = $(this);
            $.ajax({
                url: $(this).attr('data-url'),
                type: 'DELETE',
                dataType: 'JSON',
                success: function () {
                    load_task(btn.closest('.side-modal').attr('id'));
                    show_toastr(@json(__('Success')), @json(__('Comment Deleted Successfully!')), 'success');
                    btn.closest('.list-group-item').remove();
                },
                error: function (data) {
                    data = data.responseJSON;
                    if (data.message) {
                        show_toastr(@json(__('Error')), data.message, 'error');
                    } else {
                        show_toastr(@json(__('Error')), @json(__('Some Thing Is Wrong!')), 'error');
                    }
                }
            });
        });

        $(document).on('click', '#checklist_submit', function () {
            var name = $("#form-checklist input[name=name]").val();
            if (name != '') {
                $.ajax({
                    url: $("#form-checklist").data('action'),
                    data: {name: name},
                    type: 'POST',
                    success: function (data) {
                        data = JSON.parse(data);
                        load_task($('.side-modal').attr('id'));
                        show_toastr(@json(__('Success')), @json(__('Checklist Added Successfully!')), 'success');
                        $('#checklist-empty').remove();
                        var html = '<div class="card border shadow-none checklist-member">' +
                            '                    <div class="px-3 py-2 row align-items-center">' +
                            '                        <div class="col-10">' +
                            '                            <div class="custom-control custom-checkbox">' +
                            '                                <input type="checkbox" class="custom-control-input" id="check-item-' + taskUiEscape(data.id) + '" value="' + taskUiEscape(data.id) + '" data-url="' + taskUiEscape(data.updateUrl) + '">' +
                            '                                <label class="custom-control-label h6 text-sm" for="check-item-' + taskUiEscape(data.id) + '">' + taskUiEscape(data.name) + '</label>' +
                            '                            </div>' +
                            '                        </div>' +
                            '                        <div class="col-auto card-meta d-inline-flex align-items-center ml-sm-auto">' +
                            '                            <a href="#" class="action-item delete-checklist" role="button" data-url="' + taskUiEscape(data.deleteUrl) + '">' +
                            '                                <i class="fas fa-trash-alt text-danger"></i>' +
                            '                            </a>' +
                            '                        </div>' +
                            '                    </div>' +
                            '                </div>';

                        $("#checklist").append(html);
                        $("#form-checklist input[name=name]").val('');
                        $("#form-checklist").collapse('toggle');
                    },
                    error: function (data) {
                        data = data.responseJSON;
                        show_toastr(@json(__('Error')), data.message, 'error');
                    }
                });
            } else {
                show_toastr(@json(__('Error')), @json(__('Please write checklist name!')), 'error');
            }
        });

        $(document).on("change", "#checklist input[type=checkbox]", function () {
            $.ajax({
                url: $(this).attr('data-url'),
                type: 'PUT',
                dataType: 'JSON',
                success: function () {
                    load_task($('.side-modal').attr('id'));
                    show_toastr(@json(__('Success')), @json(__('Checklist Updated Successfully!')), 'success');
                },
                error: function (data) {
                    data = data.responseJSON;
                    if (data.message) {
                        show_toastr(@json(__('Error')), data.message, 'error');
                    } else {
                        show_toastr(@json(__('Error')), @json(__('Some Thing Is Wrong!')), 'error');
                    }
                }
            });
        });

        $(document).on("click", ".delete-checklist", function () {
            var btn = $(this);
            $.ajax({
                url: $(this).attr('data-url'),
                type: 'DELETE',
                dataType: 'JSON',
                success: function () {
                    load_task($('.side-modal').attr('id'));
                    show_toastr(@json(__('Success')), @json(__('Checklist Deleted Successfully!')), 'success');
                    btn.closest('.checklist-member').remove();
                },
                error: function (data) {
                    data = data.responseJSON;
                    if (data.message) {
                        show_toastr(@json(__('Error')), data.message, 'error');
                    } else {
                        show_toastr(@json(__('Error')), @json(__('Some Thing Is Wrong!')), 'error');
                    }
                }
            });
        });

        $(document).on('click', '#file_submit', function () {
            var file_data = $("#task_attachment").prop("files")[0];
            if (file_data != '' && file_data != undefined) {
                var formData = new FormData();
                formData.append('file', file_data);
                $.ajax({
                    url: $("#file_submit").data('action'),
                    type: 'POST',
                    data: formData,
                    cache: false,
                    processData: false,
                    contentType: false,
                    success: function (data) {
                        $('#task_attachment').val('');
                        $('.attachment_text').html(@json(__('Choose a file…')));
                        data = JSON.parse(data);
                        load_task(data.task_id);
                        show_toastr(@json(__('Success')), @json(__('File Added Successfully!')), 'success');

                        var delLink = '';
                        if (data.deleteUrl.length > 0) {
                            delLink = '<a href="#" class="action-item delete-comment-file" role="button" data-url="' + taskUiEscape(data.deleteUrl) + '">' +
                                '                                        <i class="fas fa-trash"></i>' +
                                '                                    </a>';
                        }

                        var html = '<div class="card mb-3 border shadow-none task-file">' +
                            '                    <div class="px-3 py-3">' +
                            '                        <div class="row align-items-center">' +
                            '                            <div class="col-auto">' +
                            '                                <img src="{{ asset('assets/img/icons/files') }}/' + taskUiEscape(data.extension) + '.png" class="img-fluid" style="width: 40px;">' +
                            '                            </div>' +
                            '                            <div class="col ml-n2">' +
                            '                                <h6 class="text-sm mb-0">' +
                            '                                    <a href="#">' + taskUiEscape(data.name) + '</a>' +
                            '                                </h6>' +
                            '                                <p class="card-text small text-muted">' + taskUiEscape(data.file_size) + '</p>' +
                            '                            </div>' +
                            '                            <div class="col-auto actions">' +
                            '                                <a href="{{ asset(Storage::url('tasks')) }}/' + taskUiEscape(data.file) + '" download class="action-item" role="button">' +
                            '                                    <i class="fas fa-download"></i>' +
                            '                                </a>' +
                            delLink +
                            '                            </div>' +
                            '                        </div>' +
                            '                    </div>' +
                            '                </div>';

                        $("#comments-file").prepend(html);
                    },
                    error: function (data) {
                        data = data.responseJSON;
                        if (data.message) {
                            show_toastr(@json(__('Error')), data.errors.file[0], 'error');
                            $('#file-error').text(data.errors.file[0]).show();
                        } else {
                            show_toastr(@json(__('Error')), @json(__('Some Thing Is Wrong!')), 'error');
                        }
                    }
                });
            } else {
                show_toastr(@json(__('Error')), @json(__('Please select file!')), 'error');
            }
        });

        $(document).on("click", ".delete-comment-file", function () {
            var btn = $(this);
            $.ajax({
                url: $(this).attr('data-url'),
                type: 'DELETE',
                dataType: 'JSON',
                success: function () {
                    load_task(btn.closest('.side-modal').attr('id'));
                    show_toastr(@json(__('Success')), @json(__('File Deleted Successfully!')), 'success');
                    btn.closest('.task-file').remove();
                },
                error: function (data) {
                    data = data.responseJSON;
                    if (data.message) {
                        show_toastr(@json(__('Error')), data.message, 'error');
                    } else {
                        show_toastr(@json(__('Error')), @json(__('Some Thing Is Wrong!')), 'error');
                    }
                }
            });
        });

        $(document).on('click', '#add_favourite', function () {
            $.ajax({
                url: $(this).attr('data-url'),
                type: 'POST',
                success: function (data) {
                    if (data.fav == 1) {
                        $('#add_favourite').addClass('action-favorite').attr('aria-pressed', 'true');
                    } else if (data.fav == 0) {
                        $('#add_favourite').removeClass('action-favorite').attr('aria-pressed', 'false');
                    }
                }
            });
        });

        $(document).on('change', '#complete_task', function () {
            $.ajax({
                url: $(this).attr('data-url'),
                type: 'POST',
                success: function (data) {
                    if (data.com == 1) {
                        $("#complete_task").prop("checked", true);
                    } else if (data.com == 0) {
                        $("#complete_task").prop("checked", false);
                    }
                    $('#' + data.task).insertBefore($('#task-list-' + data.stage + ' .empty-container'));
                    load_task(data.task);
                    window.taskUiRefreshColumn($('#task-list-' + data.stage));
                    $('.task-list-items').each(function () {
                        window.taskUiRefreshColumn($(this));
                    });
                }
            });
        });

        $(document).on('change', '#task_progress', function () {
            var progress = $(this).val();
            $('#t_percentage').html(progress);
            $(this).attr('aria-valuenow', progress);
            $.ajax({
                url: $(this).attr('data-url'),
                data: {progress: progress},
                type: 'POST',
                success: function (data) {
                    load_task(data.task_id);
                }
            });
        });
    });

    function load_task(id) {
        $.ajax({
            url: "{{ route('projects.tasks.get', '__TASK__') }}".replace('__TASK__', id),
            dataType: 'html',
            success: function (data) {
                $('#' + id).html('');
                $('#' + id).html(data);
            },
            error: function () {
                show_toastr(@json(__('Error')), @json(__('Some Thing Is Wrong!')), 'error');
            }
        });
    }
</script>
