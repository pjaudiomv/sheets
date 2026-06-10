@extends('template')

@section('content')
<h1 class="mb-4">
    Feed {{ $created ? 'Created' : 'Refreshed' }} ✅
</h1>

<textarea class="bg-black border-0 font-monospace p-3 rounded text-light w-100" rows="2">{{ $feedUrl }}</textarea>

<p class="text-center mt-3 d-flex justify-content-center gap-3">
    <a href="{{ $feedUrl }}" target="_blank"
        class="btn btn-outline-primary d-inline-flex align-items-center gap-2 justify-content-start">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
            class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
            <path fill-rule="evenodd"
                d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z" />
            <path fill-rule="evenodd"
                d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z" />
        </svg>
        JSON
    </a>
    <a href="https://pdf.code4recovery.org/?json={{ $feedUrl }}" target="_blank"
        class="btn btn-outline-primary d-inline-flex align-items-center gap-2 justify-content-start">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
            class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
            <path fill-rule="evenodd"
                d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z" />
            <path fill-rule="evenodd"
                d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z" />
        </svg>
        PDF
    </a>
</p>

@if (count($warnings))
<h2 class="fs-4">
    Warnings
</h2>

<div class="mb-5 d-grid gap-2">
    @foreach ($warnings as $warning)
    <a class="btn btn-outline-danger" href="{{ $warning['link'] }}" target="_blank">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
            class="bi bi-box-arrow-up-right" viewBox="0 0 16 16">
            <path fill-rule="evenodd"
                d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z" />
            <path fill-rule="evenodd"
                d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z" />
        </svg>

        {{ $warning['error'] }}
        <u>{{ implode(', ', $warning['value']) }}</u>
    </a>
    @endforeach
</div>
@endif

<h2 class="fs-4">Notes</h2>

<p>Bookmark this page so you don't need to fill out the form every time you want to refresh your feed.</p>

<p>You can be redirected after refreshing by passing a <code>redirectTo</code> parameter to this URL.</p>

<p>Feeds that are not refreshed within 30 days are removed automatically.</p>

<h2 class="mt-5 fs-4">Setting up TSML UI?</h2>

<textarea class="bg-black border-0 font-monospace mb-3 p-3 rounded text-light w-100" rows="8">&lt;div
  id="tsml-ui"
  data-src="{{ $feedUrl }}"
  data-timezone="America/Los_Angeles"
&gt;&lt;/div&gt;
&lt;script src="https://tsml-ui.code4recovery.org/app.js" async&gt;&lt;/script&gt;</textarea>

<p>Update the timezone above with <a href="https://en.wikipedia.org/wiki/List_of_tz_database_time_zones"
        target="_blank">one from this list</a>.</p>
@endsection