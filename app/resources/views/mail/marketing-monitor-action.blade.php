@php
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
    $cur = $currency ? ' ' . $currency : 'đ';
    $levelVi = ['campaign' => 'Chiến dịch', 'adset' => 'Nhóm QC'];
@endphp
<h2>Giám sát quảng cáo — {{ $account->name ?? $account->external_account_id }}</h2>
<p>Hệ thống giám sát tự động vừa thực hiện các hành động sau:</p>
<ul>
    @foreach($actions as $a)
        <li>
            <b>{{ $levelVi[$a['level']] ?? $a['level'] }}: {{ $a['name'] ?? $a['level'] }}</b> —
            @if(($a['type'] ?? '') === 'pause')
                Đã <b>tạm dừng</b> (chi phí/kết quả {{ $fmt($a['cpr'] ?? 0) }}{{ $cur }} vượt ngưỡng).
            @elseif(($a['type'] ?? '') === 'increase')
                Đã <b>tăng ngân sách</b> {{ $fmt($a['from'] ?? 0) }}{{ $cur }} → {{ $fmt($a['to'] ?? 0) }}{{ $cur }}
                (chi phí/kết quả {{ $fmt($a['cpr'] ?? 0) }}{{ $cur }} dưới ngưỡng).
            @endif
        </li>
    @endforeach
</ul>
<p><a href="{{ $appUrl }}">Xem trong ứng dụng</a></p>
