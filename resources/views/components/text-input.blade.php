@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 dark:border-[#2a2a38] dark:bg-[#14141c] dark:text-gray-100 focus:border-brand-500 focus:ring-brand-500 dark:focus:border-brand-400 dark:focus:ring-brand-400 rounded-md shadow-sm']) }}>
