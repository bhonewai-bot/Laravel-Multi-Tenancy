@extends('errors.layout')

@section('code', '419')

@section('title', 'Page expired')
@section('description', 'Your session has expired. Refresh the page and try again.')

@section('body')
    <button type="button" onclick="window.location.reload()"
        class="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-semibold text-white rounded-lg bg-gradient-to-b from-brand-500 to-brand-600 border border-brand-400/20 hover:shadow-glow-brand-strong hover:from-brand-500 hover:to-brand-700 active:from-brand-600 active:to-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 dark:focus:ring-offset-[#08080c] shadow-card transition-all duration-200 ease-in-out">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182" /></svg>
        Refresh page
    </button>
@endsection
