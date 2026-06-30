@extends('errors.layout')

@section('code', '500')

@section('title', 'Server error')
@section('description', "Something went wrong on our end. Please try again in a moment.")

@section('body')
    <a href="/"
        class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-white rounded-lg bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 active:from-brand-600 active:to-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] shadow-card transition-all duration-200 ease-in-out">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5m0 0l7 7m-7-7l7-7" /></svg>
        Back to home
    </a>
@endsection
