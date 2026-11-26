@php
    $priorityKey = $priorityKey ?? 'medium';
    $priorityTone = \App\ProjectTask::$priority_color[$priorityKey] ?? 'secondary';
    $priorityLabel = \App\ProjectTask::$priority[$priorityKey] ?? $priorityKey;
@endphp
<span class="task-priority task-priority--{{ $priorityTone }}">
    <span class="task-priority__dot" aria-hidden="true"></span>
    {{ __($priorityLabel) }}
</span>
