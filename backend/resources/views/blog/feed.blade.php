<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0">
  <channel>
    <title>{{ config('blog.site_name', config('app.name')) }}</title>
    <link>{{ url('/') }}</link>
    <description>Latest blog posts</description>
    @foreach($posts as $p)
      <item>
        <title>{{ $p->title }}</title>
        <link>{{ route('blog.post',$p) }}</link>
        <pubDate>{{ $p->created_at->toRfc2822String() }}</pubDate>
        <guid>{{ route('blog.post',$p) }}</guid>
        <description>{{ e($p->excerpt ?? Str::limit(strip_tags($p->content),200)) }}</description>
      </item>
    @endforeach
  </channel>
</rss>
