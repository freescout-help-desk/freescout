@if (count($articles))
    <div class="kb-articles text-larger">
        @foreach($articles as $article)
            <a href="{{ $article->urlFrontend($mailbox, $category_id ?? null) }}"><small class="glyphicon glyphicon-list-alt"></small> &nbsp;{{ $article->title }}</a>
        @endforeach
    </div>
@endif