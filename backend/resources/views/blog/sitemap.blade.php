<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{{ url('/') }}</loc><priority>1.0</priority></url>
  @foreach($posts as $p)
    <url>
      <loc>{{ route('blog.post',$p) }}</loc>
      <lastmod>{{ $p->updated_at->toAtomString() }}</lastmod>
      <priority>0.8</priority>
    </url>
  @endforeach
  @foreach($cats as $c)
    <url><loc>{{ route('blog.category',$c) }}</loc><priority>0.6</priority></url>
  @endforeach
</urlset>
