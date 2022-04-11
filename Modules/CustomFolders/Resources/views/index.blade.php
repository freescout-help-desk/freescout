@extends('layouts.app')

@section('title_full', __('Custom Folders').' - '.$mailbox->name)

@section('body_attrs')@parent data-mailbox_id="{{ $mailbox->id }}"@endsection

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')

    <div class="section-heading">
        {{ __('Custom Folders') }}<a href="{{ route('mailboxes.custom_folders.ajax_html', ['mailbox_id' => $mailbox->id, 'action' => 'create']) }}" class="btn btn-primary margin-left" data-trigger="modal" ddata-modal-title="{{ __('New Folder') }}" data-modal-no-footer="true" data-modal-size="lg" data-modal-on-show="initNewFolder">{{ __('New Folder') }}</a>
    </div>
    @include('partials/flash_messages')
    @if (count($folders))
	    <div class="row-container">
	    	<div class="col-md-11">
				<div class="panel-group accordion margin-top" id="custom-folders-index">
					@foreach ($folders as $folder)
				        <div class="panel panel-default panel-sortable" id="folder-{{ $folder->id }}" data-folder-id="{{ $folder->id }}">
				            <div class="panel-heading">
				            	<span class="handle"><i class="glyphicon glyphicon-menu-hamburger"></i></span>
				                <h4 class="panel-title">
				                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse-{{ $folder->id }}">
				                    	<span>{{ $folder->meta['name'] ?? '' }}</span> @if ($folder->tag_name)<span class="tag">{{ $folder->tag_name }}@endif</span>
				                    </a>
				                </h4>
				            </div>
				            <div id="collapse-{{ $folder->id }}" class="panel-collapse collapse">
				                <div class="panel-body">
									<form class="form-horizontal custom-folders-form" method="POST" action="" data-folder_id="{{ $folder->id }}" >

										@include('customfolders::partials/form_update', ['mode' => 'update'])

										<div class="form-group margin-top margin-bottom-10">
									        <div class="col-sm-10 col-sm-offset-2">
									            <button class="btn btn-primary" data-loading-text="{{ __('Saving') }}…">{{ __('Save') }}</button> 
									            <a href="#" class="btn btn-link text-danger folder-delete-trigger" data-loading-text="{{ __('Deleting') }}…" data-folder_id="{{ $folder->id }}">{{ __('Delete') }}</a>
									        </div>
									    </div>
									</form>
				                </div>
				            </div>
				        </div>
				    @endforeach
			    </div>
			</div>
		</div>
	@else
		@include('partials/empty', ['icon' => 'folder-close', 'empty_header' => __("Create custom folders and organize conversations!"), 'empty_text' => ''])
	@endif
@endsection

@section('javascript')
    @parent
    initCustomFoldersAdmin('{{ __('Delete this folder?') }}');
@endsection