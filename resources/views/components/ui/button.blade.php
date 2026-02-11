@props([
    'variant' => 'primary',
    'type' => 'button',
    'disabled' => false,
])

@php
$classes = match($variant) {
    'primary' => 'bg-sky-600 text-white hover:bg-sky-700 focus:ring-sky-500',
    'secondary' => 'bg-slate-200 text-slate-800 hover:bg-slate-300 focus:ring-slate-500 dark:bg-slate-700 dark:text-slate-100 dark:hover:bg-slate-600',
    'success' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
    'danger' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    'ghost' => 'bg-transparent text-slate-600 hover:bg-slate-100 focus:ring-slate-500 border border-slate-300 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white',
    default => 'bg-sky-600 text-white hover:bg-sky-700 focus:ring-sky-500',
};

$disabledClasses = $disabled ? 'opacity-50 cursor-not-allowed' : '';
@endphp

<button
    type="{{ $type }}"
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-150 ' . $classes . ' ' . $disabledClasses]) }}
>
    {{ $slot }}
</button>
