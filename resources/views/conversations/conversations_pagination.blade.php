@if ($paginator->hasPages())
 
    {{-- First Page Link --}}
    <a href="#" class="pager-nav pager-first glyphicon glyphicon-backward @if ($paginator->currentPage() <= 2) disabled @endif" data-page="1" title="{{ __('First Page') }}"></a>

    {{-- Previous Page Link --}}
    <a href="#" class="pager-nav pager-prev glyphicon glyphicon-triangle-left @if ($paginator->onFirstPage()) disabled @endif" data-page="{{ $paginator->currentPage()-1 }}" title="{{ __('Previous Page') }}"></a>

    {{-- Next Page Link --}}
    <a href="#" class="pager-nav pager-next glyphicon glyphicon-triangle-right @if (!$paginator->hasMorePages()) disabled @endif" data-page="{{ $paginator->currentPage()+1 }}" title="{{ __('Next Page') }}"></a>

    {{-- Last Page Link --}}
    <a href="#" class="pager-nav pager-last glyphicon glyphicon-forward @if ($paginator->currentPage() >= $paginator->lastPage()-1) disabled @endif" data-page="{{ $paginator->lastPage() }}" title="{{ __('Last Page') }}"></a>

@endif
