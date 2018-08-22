@if ($paginator->hasPages())
 
    {{-- First Page Link --}}
    @if ($paginator->currentPage() > 2)
        <a href="#" class="pager-nav pager-first glyphicon glyphicon-backward" data-page="1" title="{{ __('First Page') }}"></a>
    @endif

    {{-- Previous Page Link --}}
    @if (!$paginator->onFirstPage())
        <a href="#" class="pager-nav pager-prev glyphicon glyphicon-triangle-left" data-page="{{ $paginator->currentPage()-1 }}" title="{{ __('Previous Page') }}"></a>
    @endif

    {{-- Next Page Link --}}
    @if ($paginator->hasMorePages())
        <a href="#" class="pager-nav pager-next glyphicon glyphicon-triangle-right" data-page="{{ $paginator->currentPage()+1 }}" title="{{ __('Next Page') }}"></a>
    @endif

    {{-- Last Page Link --}}
    @if ($paginator->currentPage() < $paginator->lastPage()-1)
        <a href="#" class="pager-nav pager-last glyphicon glyphicon-forward" data-page="{{ $paginator->lastPage() }}" title="{{ __('Last Page') }}"></a>
    @endif

@endif
