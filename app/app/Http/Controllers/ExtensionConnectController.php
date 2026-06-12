<?php

declare(strict_types=1);

namespace CMBcoreSeller\Http\Controllers;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Đăng nhập Chrome Extension qua redirect OAuth (hợp đồng EXTENSION_OAUTH_LOGIN_CONTRACT).
 *
 * `GET /extension/connect?redirect_uri=<cb>&state=<nonce>` — dùng web guard + session
 * (Sanctum stateful cookie) để đọc user đang đăng nhập trên web, mint một token hẹp
 * (`copy-product:push`) rồi 302 kèm token ở URL fragment về callback của extension
 * (`https://<id>.chromiumapp.org/`). Không có form login trong extension, không hardcode
 * extension id.
 *
 * Giữ nguyên luật email verify (SPEC 0022): user CHƯA verify sẽ bị đưa về SPA để xác
 * thực email trước — chỉ khi đã verify mới được cấp token và chuyển hướng về extension.
 */
class ExtensionConnectController extends Controller
{
    public function connect(Request $request): RedirectResponse
    {
        $redirectUri = (string) $request->query('redirect_uri', '');

        // 1) Validate redirect_uri TRƯỚC — sai thì 400, không redirect đi đâu (chống open-redirect).
        abort_unless($this->isAllowedRedirect($redirectUri), 400, 'invalid redirect_uri');

        // Đường quay lại chính route này (path tương đối — an toàn để SPA điều hướng nội bộ).
        $self = '/extension/connect?'.$request->getQueryString();

        // 2) Chưa đăng nhập ⇒ về trang login của SPA, kèm đường quay lại.
        if (Auth::guard('web')->guest()) {
            return redirect('/login?redirect='.urlencode($self));
        }

        $user = $request->user();

        // 3) Đã đăng nhập nhưng CHƯA verify email ⇒ giữ luật verify: KHÔNG cấp token.
        //    Đưa về SPA root (RequireAuth hiện màn verify, không phải form login); verify
        //    xong SPA tự quay lại đây để cấp token.
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect('/?redirect='.urlencode($self));
        }

        // 4) Tenant: session không giữ current_tenant_id (SPA dùng header) ⇒ lấy tenant đầu tiên.
        $tenantId = session('current_tenant_id') ?: optional($user->tenants()->orderBy('tenants.id')->first())->getKey();
        abort_if(! $tenantId, 409, 'Tài khoản chưa thuộc gian hàng nào.');

        // 5) Mint token hẹp (chỉ copy-product:push) y như ExtensionTokenController::store, rồi 302
        //    với token ở FRAGMENT (#) để không lọt vào access log phía server.
        $token = $user->createToken('Chrome Extension', ['copy-product:push']);

        $frag = http_build_query([
            'token' => $token->plainTextToken,
            'token_id' => $token->accessToken->getKey(),
            'tenant_id' => $tenantId,
            'state' => (string) $request->query('state', ''),
        ]);

        return redirect()->away($redirectUri.'#'.$frag);
    }

    /**
     * Allowlist callback: chỉ `https://<32 ký tự a–p>.chromiumapp.org[/...]` (Chrome Identity).
     * Bản dev có thể thêm URI cố định qua `config('integrations.extension.dev_redirect_uris')`
     * — KHÔNG nới lỏng regex chung.
     */
    private function isAllowedRedirect(string $uri): bool
    {
        if ($uri === '') {
            return false;
        }
        if (preg_match('#^https://[a-p]{32}\.chromiumapp\.org(/.*)?$#', $uri) === 1) {
            return true;
        }

        return in_array($uri, (array) config('integrations.extension.dev_redirect_uris', []), true);
    }
}
