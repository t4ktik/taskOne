<div class="task-state {{ $stateClass ?? '' }}" role="{{ ($stateClass ?? '') === 'task-state--error' ? 'alert' : 'status' }}">
    @if(!empty($icon))
        <div aria-hidden="true"><i class="fas {{ $icon }}"></i></div>
    @endif
    <h3>{{ $title }}</h3>
    @if(!empty($message))
        <p>{{ $message }}</p>
    @endif
    {{ $slot ?? '' }}
</div>
