@foreach ($posts as $post)
    <div>
        <article>
            <div><a href="{{ $post->url }}"></a>
                <img src="{{ MediaManagement::getImageUrl($post->image, null, false, MediaManagement::getDefaultImage()) }}" alt="{{ $post->name }}">
            </div>
            <header><a href="{{ $post->url }}"> {{ $post->name }}</a></header>
        </article>
    </div>
@endforeach

<div class="pagination">
    {!! $posts->withQueryString()->links() !!}
</div>
