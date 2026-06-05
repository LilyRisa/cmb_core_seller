@php
    $summary = $payload['summary'] ?? null;
    $assessment = $payload['assessment'] ?? null;
    $recs = $payload['recommendations'] ?? [];
    $review = $payload['creative_review'] ?? [];
    $days = $params['days'] ?? null;
@endphp
<h2>Phân tích AI chiến dịch — {{ $campaignName }}</h2>
<p>
    Phân tích AI cho chiến dịch của bạn đã sẵn sàng@if($generatedAt) (lúc {{ $generatedAt->format('H:i d/m/Y') }})@endif@if($days) · {{ $days }} ngày gần nhất@endif.
    Tài khoản: <b>{{ $account->name ?? $account->external_account_id }}</b>.
</p>

@if($summary)
    <h3>Tổng quan</h3>
    <p>{{ $summary }}</p>
@endif

@if($assessment)
    <h3>Đánh giá</h3>
    <p>{{ $assessment }}</p>
@endif

<h3>Khuyến nghị</h3>
<ul>
    @forelse($recs as $r)
        <li>@if(is_array($r))<b>{{ $r['action'] ?? '' }}</b> — {{ $r['rationale'] ?? '' }}@else{{ $r }}@endif</li>
    @empty
        <li>—</li>
    @endforelse
</ul>

@if(!empty($review))
    <h3>Đánh giá nội dung quảng cáo</h3>
    @foreach($review as $r)
        <p>
            <b>{{ $r['name'] ?? $r['ref'] ?? 'Quảng cáo' }}</b> — {{ $r['verdict'] ?? '' }}<br>
            @foreach(($r['suggestions'] ?? []) as $sug)• {{ $sug }}<br>@endforeach
        </p>
    @endforeach
@endif

<p><a href="{{ $appUrl }}">Xem chi tiết trong ứng dụng</a></p>
