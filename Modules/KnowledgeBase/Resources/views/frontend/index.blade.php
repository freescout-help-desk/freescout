@extends('knowledgebase::layouts.index')

@section('title', 'Overview')

@section('content')

  @if (count($mailboxes))
    @include('knowledgebase::partials/frontend/knowledgebase_panels', ['mailboxes' => $mailboxes])
	@endif

@endsection
