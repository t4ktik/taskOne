@php
    $assignees = $assignees ?? ($task->users() ?: collect());
@endphp
@if(count($assignees) > 0)
    <div class="avatar-group task-assignees">
        @foreach($assignees as $key => $user)
            @if($key < 3)
                <span class="avatar rounded-circle avatar-sm" title="{{ $user->name }}">
                    <img {{ $user->img_avatar }} alt="{{ $user->name }}">
                </span>
            @else
                @break
            @endif
        @endforeach
        @if(count($assignees) > 3)
            <span class="avatar rounded-circle avatar-sm" title="+{{ count($assignees) - 3 }}">
                <img avatar="+{{ count($assignees) - 3 }}" alt="+{{ count($assignees) - 3 }}">
            </span>
        @endif
    </div>
@else
    <span class="task-unassigned">{{ __('Unassigned') }}</span>
@endif
