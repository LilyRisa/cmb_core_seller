@php
    $f = $payload['forecast']['next_7d'] ?? [];
    $strategy = $payload['strategy'] ?? [];
    $review = $payload['creative_review'] ?? [];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    // Echo as a plain string: a Blade @if glued to "sàng" would not compile.
    $when = $generatedAt ? ' (lúc ' . $generatedAt->format('H:i d/m/Y') . ')' : '';
@endphp
<h2>Báo cáo quảng cáo — {{ $account->name ?? $account->external_account_id }}</h2>
<p>Báo cáo AI cho tài khoản quảng cáo của bạn đã sẵn sàng{{ $when }}.</p>

<h3>Dự báo 7 ngày tới</h3>
<ul>
    <li>Đơn dự kiến: <b>{{ $f['orders'] ?? '—' }}</b></li>
    <li>Chi tiêu dự kiến: <b>{{ isset($f['spend']) ? $fmt($f['spend']).'đ' : '—' }}</b></li>
    <li>Hội thoại dự kiến: <b>{{ $f['conversations'] ?? '—' }}</b></li>
</ul>

<h3>Chiến lược đề xuất</h3>
<ul>
    @forelse($strategy as $s)
        <li><b>{{ $s['action'] ?? '' }}</b> — {{ $s['rationale'] ?? '' }}</li>
    @empty
        <li>—</li>
    @endforelse
</ul>

<h3>Đánh giá nội dung quảng cáo</h3>
@forelse($review as $r)
    <p>
        <b>{{ $r['name'] ?? $r['ref'] ?? 'Quảng cáo' }}</b> — {{ $r['verdict'] ?? '' }}<br>
        @foreach(($r['suggestions'] ?? []) as $sug)• {{ $sug }}<br>@endforeach
    </p>
@empty
    <p>—</p>
@endforelse

<p><a href="{{ $appUrl }}">Xem chi tiết trong ứng dụng</a></p>
