# Facebook Ad Creation — Plan 2: Authoring Read Helpers Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Add the read/query helpers the ad wizard needs — list Pages, list Page posts (with like/comment/share + image/video), targeting search, audience estimate, and ad previews — onto the segregated authoring axis of the Ads connector.

**Architecture:** Extend the existing `AdsWriteConnector` interface (the "authoring axis") with query methods + new provider-agnostic DTOs. Facebook specifics stay in `FacebookAdsConnector`. All Graph calls tested with `Http::fake` (verify our request shape; real-token smoke testing is a separate manual step). Media upload (`uploadImage`/`uploadVideo`) is intentionally deferred — the headline feature is advertising existing Page posts, which needs no upload.

**Tech Stack:** Laravel 11, PHP 8.2 readonly DTOs, `Http` facade, PHPUnit + `Http::fake`, Pint, Larastan L5.

**Conventions:** Commands from `app/`. Namespace `CMBcoreSeller\Integrations\Ads\*` → `app/app/Integrations/Ads/*`. Follow the style of `FacebookAdsConnector.php` and existing DTOs.

---

### Task 1: Authoring DTOs

**Files:**
- Create: `app/app/Integrations/Ads/DTO/PageRefDTO.php`
- Create: `app/app/Integrations/Ads/DTO/PagePostDTO.php`
- Create: `app/app/Integrations/Ads/DTO/TargetingOptionDTO.php`
- Create: `app/app/Integrations/Ads/DTO/AudienceSizeDTO.php`
- Create: `app/app/Integrations/Ads/DTO/AdPreviewDTO.php`
- Test: `app/tests/Unit/Marketing/AuthoringDtoTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\DTO\AdPreviewDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AudienceSizeDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PagePostDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PageRefDTO;
use CMBcoreSeller\Integrations\Ads\DTO\TargetingOptionDTO;
use PHPUnit\Framework\TestCase;

class AuthoringDtoTest extends TestCase
{
    public function test_dtos_construct(): void
    {
        $page = new PageRefDTO(id: '123', name: 'Shop', accessToken: 'PAGETOK');
        $this->assertSame('123', $page->id);
        $this->assertSame('PAGETOK', $page->accessToken);

        $post = new PagePostDTO(
            id: '123_456', message: 'Sale', createdTime: '2026-06-01T00:00:00+0000',
            mediaType: 'photo', imageUrl: 'https://img', videoId: null,
            likes: 1200, comments: 89, shares: 45,
        );
        $this->assertSame('123_456', $post->id);
        $this->assertSame(1200, $post->likes);
        $this->assertSame('photo', $post->mediaType);

        $opt = new TargetingOptionDTO(id: '6003', name: 'Thời trang', type: 'interests', audienceSize: 5000000);
        $this->assertSame('6003', $opt->id);

        $size = new AudienceSizeDTO(lowerBound: 1000000, upperBound: 2100000);
        $this->assertSame(2100000, $size->upperBound);

        $prev = new AdPreviewDTO(format: 'DESKTOP_FEED_STANDARD', body: '<iframe></iframe>');
        $this->assertSame('DESKTOP_FEED_STANDARD', $prev->format);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AuthoringDtoTest` → FAIL (classes missing).

- [ ] **Step 3: Implement the five DTOs.**

`PageRefDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class PageRefDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $id,
        public string $name,
        public string $accessToken,   // page access token (needed to read posts / back creatives)
        public array $raw = [],
    ) {}
}
```

`PagePostDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class PagePostDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $id,
        public ?string $message,
        public string $createdTime,
        public string $mediaType,     // photo | video | status | link | album
        public ?string $imageUrl,
        public ?string $videoId,
        public int $likes,
        public int $comments,
        public int $shares,
        public array $raw = [],
    ) {}
}
```

`TargetingOptionDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class TargetingOptionDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $id,
        public string $name,
        public string $type,           // interests | behaviors | geo ...
        public ?int $audienceSize = null,
        public array $raw = [],
    ) {}
}
```

`AudienceSizeDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AudienceSizeDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public ?int $lowerBound,
        public ?int $upperBound,
        public array $raw = [],
    ) {}
}
```

`AdPreviewDTO.php`:
```php
<?php

namespace CMBcoreSeller\Integrations\Ads\DTO;

final readonly class AdPreviewDTO
{
    /** @param array<string,mixed> $raw */
    public function __construct(
        public string $format,         // e.g. DESKTOP_FEED_STANDARD, MOBILE_FEED_STANDARD
        public string $body,           // iframe HTML returned by Graph generatepreviews
        public array $raw = [],
    ) {}
}
```

- [ ] **Step 4: Run** `php artisan test --filter=AuthoringDtoTest` → PASS.

- [ ] **Step 5: Quality + commit**

```
# from app/
vendor/bin/pint app/Integrations/Ads/DTO tests/Unit/Marketing/AuthoringDtoTest.php
vendor/bin/phpstan analyse app/Integrations/Ads/DTO
```
```
git add app/app/Integrations/Ads/DTO/PageRefDTO.php app/app/Integrations/Ads/DTO/PagePostDTO.php app/app/Integrations/Ads/DTO/TargetingOptionDTO.php app/app/Integrations/Ads/DTO/AudienceSizeDTO.php app/app/Integrations/Ads/DTO/AdPreviewDTO.php app/tests/Unit/Marketing/AuthoringDtoTest.php
git commit -m "feat(ads): authoring DTOs (page, post, targeting, audience size, preview)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 2: Extend `AdsWriteConnector` with query methods + stubs

**Files:**
- Modify: `app/app/Integrations/Ads/Contracts/AdsWriteConnector.php`
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (add stubs)
- Test: `app/tests/Unit/Marketing/AdsAuthoringContractTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use PHPUnit\Framework\TestCase;

class AdsAuthoringContractTest extends TestCase
{
    public function test_connector_has_authoring_query_methods(): void
    {
        $c = new FacebookAdsConnector(['graph_version' => 'v19.0']);

        $this->assertTrue(method_exists($c, 'listPages'));
        $this->assertTrue(method_exists($c, 'listPagePosts'));
        $this->assertTrue(method_exists($c, 'searchTargeting'));
        $this->assertTrue(method_exists($c, 'estimateAudience'));
        $this->assertTrue(method_exists($c, 'generatePreviews'));
        $this->assertTrue($c->supports('page.posts.read'));
        $this->assertTrue($c->supports('targeting.search'));
        $this->assertTrue($c->supports('preview.generate'));
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=AdsAuthoringContractTest` → FAIL.

- [ ] **Step 3: Implement.** In `AdsWriteConnector.php`, update the docblock to "ad authoring axis (create + creative/targeting/preview queries)" and add these method signatures (with the needed `use` imports for the DTOs):

```php
    /** @return list<\CMBcoreSeller\Integrations\Ads\DTO\PageRefDTO> */
    public function listPages(string $accessToken): array;

    /** @return list<\CMBcoreSeller\Integrations\Ads\DTO\PagePostDTO> */
    public function listPagePosts(string $pageAccessToken, string $pageId, int $limit = 25): array;

    /** @return list<\CMBcoreSeller\Integrations\Ads\DTO\TargetingOptionDTO> */
    public function searchTargeting(string $accessToken, string $query, string $type = 'adinterest'): array;

    /** @param array<string,mixed> $targetingSpec */
    public function estimateAudience(string $accessToken, string $externalAccountId, array $targetingSpec, string $optimizationGoal): \CMBcoreSeller\Integrations\Ads\DTO\AudienceSizeDTO;

    /**
     * @param array<string,mixed> $creativeSpec object_story_spec (or {creative_id})
     * @param list<string> $formats
     * @return list<\CMBcoreSeller\Integrations\Ads\DTO\AdPreviewDTO>
     */
    public function generatePreviews(string $accessToken, string $externalAccountId, array $creativeSpec, array $formats): array;
```

In `FacebookAdsConnector.php`, add the imports and stub bodies (real bodies in Tasks 3–5):
```php
use CMBcoreSeller\Integrations\Ads\DTO\AdPreviewDTO;
use CMBcoreSeller\Integrations\Ads\DTO\AudienceSizeDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PagePostDTO;
use CMBcoreSeller\Integrations\Ads\DTO\PageRefDTO;
use CMBcoreSeller\Integrations\Ads\DTO\TargetingOptionDTO;
```
```php
    public function listPages(string $accessToken): array
    {
        return [];
    }

    public function listPagePosts(string $pageAccessToken, string $pageId, int $limit = 25): array
    {
        return [];
    }

    public function searchTargeting(string $accessToken, string $query, string $type = 'adinterest'): array
    {
        return [];
    }

    public function estimateAudience(string $accessToken, string $externalAccountId, array $targetingSpec, string $optimizationGoal): AudienceSizeDTO
    {
        return new AudienceSizeDTO(lowerBound: null, upperBound: null);
    }

    public function generatePreviews(string $accessToken, string $externalAccountId, array $creativeSpec, array $formats): array
    {
        return [];
    }
```

- [ ] **Step 4: Run** `php artisan test --filter=AdsAuthoringContractTest` → PASS. Regression: `php artisan test --filter='FacebookAdsCreateTest|AdsWriteConnectorContractTest'` green.

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Integrations/Ads/Contracts/AdsWriteConnector.php app/Integrations/Ads/Facebook/FacebookAdsConnector.php tests/Unit/Marketing/AdsAuthoringContractTest.php
vendor/bin/phpstan analyse app/Integrations/Ads
```
```
git add app/app/Integrations/Ads/Contracts/AdsWriteConnector.php app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Unit/Marketing/AdsAuthoringContractTest.php
git commit -m "feat(ads): authoring query methods on AdsWriteConnector (+stubs)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 3: `listPages` + `listPagePosts` (engagement + media)

**Files:**
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (replace the two stubs)
- Test: `app/tests/Feature/Marketing/FacebookAdsPagePostsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsPagePostsTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_list_pages_maps_id_name_token(): void
    {
        Http::fake(['graph.facebook.com/*/me/accounts*' => Http::response([
            'data' => [['id' => '123', 'name' => 'Shop', 'access_token' => 'PAGETOK']],
        ], 200)]);

        $pages = $this->connector()->listPages('USERTOK');

        $this->assertCount(1, $pages);
        $this->assertSame('123', $pages[0]->id);
        $this->assertSame('Shop', $pages[0]->name);
        $this->assertSame('PAGETOK', $pages[0]->accessToken);
    }

    public function test_list_page_posts_maps_media_and_engagement(): void
    {
        Http::fake(['graph.facebook.com/*/123/published_posts*' => Http::response([
            'data' => [[
                'id' => '123_456',
                'message' => 'Sale Tết',
                'created_time' => '2026-06-01T00:00:00+0000',
                'full_picture' => 'https://img/p.jpg',
                'attachments' => ['data' => [['media_type' => 'photo']]],
                'likes' => ['summary' => ['total_count' => 1200]],
                'comments' => ['summary' => ['total_count' => 89]],
                'shares' => ['count' => 45],
            ]],
        ], 200)]);

        $posts = $this->connector()->listPagePosts('PAGETOK', '123', 25);

        $this->assertCount(1, $posts);
        $p = $posts[0];
        $this->assertSame('123_456', $p->id);
        $this->assertSame('Sale Tết', $p->message);
        $this->assertSame('photo', $p->mediaType);
        $this->assertSame('https://img/p.jpg', $p->imageUrl);
        $this->assertSame(1200, $p->likes);
        $this->assertSame(89, $p->comments);
        $this->assertSame(45, $p->shares);
    }

    public function test_list_page_posts_handles_missing_engagement_gracefully(): void
    {
        Http::fake(['graph.facebook.com/*/123/published_posts*' => Http::response([
            'data' => [['id' => '123_789', 'created_time' => '2026-06-02T00:00:00+0000']],
        ], 200)]);

        $posts = $this->connector()->listPagePosts('PAGETOK', '123');

        $this->assertSame(0, $posts[0]->likes);
        $this->assertSame(0, $posts[0]->comments);
        $this->assertSame(0, $posts[0]->shares);
        $this->assertNull($posts[0]->message);
        $this->assertSame('status', $posts[0]->mediaType);
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=FacebookAdsPagePostsTest` → FAIL (stubs return []).

- [ ] **Step 3: Implement.** Replace the `listPages` and `listPagePosts` stubs:

```php
    public function listPages(string $accessToken): array
    {
        $res = Http::timeout(30)->get($this->graphUrl('me/accounts'), [
            'fields' => 'id,name,access_token',
            'access_token' => $accessToken,
            'limit' => 200,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads listPages failed: '.$res->body());
        }

        return array_values(array_map(fn (array $p) => new PageRefDTO(
            id: (string) ($p['id'] ?? ''),
            name: (string) ($p['name'] ?? ''),
            accessToken: (string) ($p['access_token'] ?? ''),
            raw: $p,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function listPagePosts(string $pageAccessToken, string $pageId, int $limit = 25): array
    {
        $res = Http::timeout(30)->get($this->graphUrl($pageId.'/published_posts'), [
            'fields' => 'id,message,created_time,full_picture,attachments{media_type,media},'
                .'likes.summary(true).limit(0),comments.summary(true).limit(0),shares',
            'access_token' => $pageAccessToken,
            'limit' => $limit,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads listPagePosts failed: '.$res->body());
        }

        return array_values(array_map(function (array $p) {
            $attachment = $p['attachments']['data'][0] ?? [];
            $mediaType = (string) ($attachment['media_type'] ?? 'status');

            return new PagePostDTO(
                id: (string) ($p['id'] ?? ''),
                message: isset($p['message']) ? (string) $p['message'] : null,
                createdTime: (string) ($p['created_time'] ?? ''),
                mediaType: $mediaType,
                imageUrl: isset($p['full_picture']) ? (string) $p['full_picture'] : null,
                videoId: isset($attachment['media']['source']) ? null : null, // video source is a URL, not an id; left null in v1
                likes: (int) ($p['likes']['summary']['total_count'] ?? 0),
                comments: (int) ($p['comments']['summary']['total_count'] ?? 0),
                shares: (int) ($p['shares']['count'] ?? 0),
                raw: $p,
            );
        }, array_filter((array) $res->json('data', []), 'is_array')));
    }
```

- [ ] **Step 4: Run** `php artisan test --filter=FacebookAdsPagePostsTest` → PASS (3 tests).

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Integrations/Ads/Facebook/FacebookAdsConnector.php tests/Feature/Marketing/FacebookAdsPagePostsTest.php
vendor/bin/phpstan analyse app/Integrations/Ads
```
```
git add app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Feature/Marketing/FacebookAdsPagePostsTest.php
git commit -m "feat(ads): listPages + listPagePosts (media + like/comment/share)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 4: `searchTargeting` + `estimateAudience`

**Files:**
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (replace two stubs)
- Test: `app/tests/Feature/Marketing/FacebookAdsTargetingTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsTargetingTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_search_targeting_maps_interest_options(): void
    {
        Http::fake(['graph.facebook.com/*/search*' => Http::response([
            'data' => [['id' => '6003', 'name' => 'Thời trang', 'audience_size_lower_bound' => 5000000]],
        ], 200)]);

        $opts = $this->connector()->searchTargeting('TOK', 'thời trang');

        $this->assertCount(1, $opts);
        $this->assertSame('6003', $opts[0]->id);
        $this->assertSame('Thời trang', $opts[0]->name);
        $this->assertSame('interests', $opts[0]->type);
        $this->assertSame(5000000, $opts[0]->audienceSize);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/search') && $r->data()['type'] === 'adinterest' && $r->data()['q'] === 'thời trang');
    }

    public function test_estimate_audience_maps_bounds(): void
    {
        Http::fake(['graph.facebook.com/*/delivery_estimate*' => Http::response([
            'data' => [['estimate_mau_lower_bound' => 1000000, 'estimate_mau_upper_bound' => 2100000]],
        ], 200)]);

        $size = $this->connector()->estimateAudience('TOK', 'act_1', ['geo_locations' => ['countries' => ['VN']]], 'REACH');

        $this->assertSame(1000000, $size->lowerBound);
        $this->assertSame(2100000, $size->upperBound);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'act_1/delivery_estimate') && $r->data()['optimization_goal'] === 'REACH');
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=FacebookAdsTargetingTest` → FAIL.

- [ ] **Step 3: Implement.** Replace the two stubs:

```php
    public function searchTargeting(string $accessToken, string $query, string $type = 'adinterest'): array
    {
        $res = Http::timeout(30)->get($this->graphUrl('search'), [
            'type' => $type,
            'q' => $query,
            'limit' => 50,
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads searchTargeting failed: '.$res->body());
        }

        return array_values(array_map(fn (array $o) => new TargetingOptionDTO(
            id: (string) ($o['id'] ?? ''),
            name: (string) ($o['name'] ?? ''),
            type: 'interests',
            audienceSize: isset($o['audience_size_lower_bound']) ? (int) $o['audience_size_lower_bound'] : null,
            raw: $o,
        ), array_filter((array) $res->json('data', []), 'is_array')));
    }

    public function estimateAudience(string $accessToken, string $externalAccountId, array $targetingSpec, string $optimizationGoal): AudienceSizeDTO
    {
        $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/delivery_estimate'), [
            'optimization_goal' => $optimizationGoal,
            'targeting_spec' => json_encode($targetingSpec),
            'access_token' => $accessToken,
        ]);
        if (! $res->successful()) {
            throw new \RuntimeException('Facebook Ads estimateAudience failed: '.$res->body());
        }
        $row = (array) ($res->json('data.0') ?? []);

        return new AudienceSizeDTO(
            lowerBound: isset($row['estimate_mau_lower_bound']) ? (int) $row['estimate_mau_lower_bound'] : null,
            upperBound: isset($row['estimate_mau_upper_bound']) ? (int) $row['estimate_mau_upper_bound'] : null,
            raw: $row,
        );
    }
```

- [ ] **Step 4: Run** `php artisan test --filter=FacebookAdsTargetingTest` → PASS (2 tests).

- [ ] **Step 5: Quality + commit**
```
# from app/
vendor/bin/pint app/Integrations/Ads/Facebook/FacebookAdsConnector.php tests/Feature/Marketing/FacebookAdsTargetingTest.php
vendor/bin/phpstan analyse app/Integrations/Ads
```
```
git add app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php app/tests/Feature/Marketing/FacebookAdsTargetingTest.php
git commit -m "feat(ads): searchTargeting + estimateAudience (delivery estimate)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

### Task 5: `generatePreviews`

**Files:**
- Modify: `app/app/Integrations/Ads/Facebook/FacebookAdsConnector.php` (replace stub)
- Test: `app/tests/Feature/Marketing/FacebookAdsPreviewTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Marketing;

use CMBcoreSeller\Integrations\Ads\Facebook\FacebookAdsConnector;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookAdsPreviewTest extends TestCase
{
    private function connector(): FacebookAdsConnector
    {
        return new FacebookAdsConnector(['graph_version' => 'v19.0']);
    }

    public function test_generate_previews_returns_iframe_per_format(): void
    {
        Http::fake(['graph.facebook.com/*/generatepreviews*' => Http::response([
            'data' => [['body' => '<iframe src="x"></iframe>']],
        ], 200)]);

        $previews = $this->connector()->generatePreviews(
            'TOK', 'act_1',
            ['page_id' => '123', 'link_data' => ['message' => 'Hi']],
            ['DESKTOP_FEED_STANDARD', 'MOBILE_FEED_STANDARD'],
        );

        $this->assertCount(2, $previews);
        $this->assertSame('DESKTOP_FEED_STANDARD', $previews[0]->format);
        $this->assertStringContainsString('<iframe', $previews[0]->body);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'act_1/generatepreviews') && $r->data()['ad_format'] === 'DESKTOP_FEED_STANDARD');
    }

    public function test_generate_previews_skips_formats_that_error(): void
    {
        Http::fake(['graph.facebook.com/*/generatepreviews*' => Http::response(['error' => ['message' => 'bad']], 400)]);

        $previews = $this->connector()->generatePreviews('TOK', 'act_1', ['page_id' => '123'], ['DESKTOP_FEED_STANDARD']);

        $this->assertSame([], $previews); // best-effort: a failing format is skipped, not fatal
    }
}
```

- [ ] **Step 2: Run** `php artisan test --filter=FacebookAdsPreviewTest` → FAIL.

- [ ] **Step 3: Implement.** Replace the stub:

```php
    public function generatePreviews(string $accessToken, string $externalAccountId, array $creativeSpec, array $formats): array
    {
        $out = [];
        foreach ($formats as $format) {
            $res = Http::timeout(30)->get($this->graphUrl($externalAccountId.'/generatepreviews'), [
                'creative' => json_encode(['object_story_spec' => $creativeSpec]),
                'ad_format' => $format,
                'access_token' => $accessToken,
            ]);
            // Best-effort: a format Graph can't render is skipped, not fatal.
            if (! $res->successful()) {
                continue;
            }
            $body = $res->json('data.0.body');
            if (is_string($body) && $body !== '') {
                $out[] = new AdPreviewDTO(format: (string) $format, body: $body, raw: (array) ($res->json('data.0') ?? []));
            }
        }

        return $out;
    }
```

- [ ] **Step 4: Run** `php artisan test --filter=FacebookAdsPreviewTest` → PASS (2 tests).

- [ ] **Step 5: FINAL gate + commit**
```
# from app/
vendor/bin/pint app/Integrations/Ads tests/Feature/Marketing/FacebookAdsPreviewTest.php
vendor/bin/phpstan analyse app/Integrations/Ads
php artisan test --filter='AuthoringDtoTest|AdsAuthoringContractTest|FacebookAdsPagePostsTest|FacebookAdsTargetingTest|FacebookAdsPreviewTest|FacebookAdsCreateTest|FacebookAdsConnectorTest|AdsReportServiceTest'
```
All green. Then:
```
git add app/app/Integrations/Ads tests/Feature/Marketing/FacebookAdsPreviewTest.php
git commit -m "feat(ads): generatePreviews (Graph iframe per format, best-effort)

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Self-Review (plan author)

**Spec coverage (§4 helpers):** listPages ✓ (T3), listPagePosts w/ engagement+media ✓ (T3, the headline feature), searchTargeting ✓ (T4), estimateAudience ✓ (T4), generatePreviews ✓ (T5), DTOs ✓ (T1), interface+caps ✓ (T2). **Deferred:** `uploadImage`/`uploadVideo` — only needed for the "new creative" branch; the existing-page-post path (emphasized feature) needs no upload. Tracked as a follow-up before Plan 4 publish of new-creative ads.

**Placeholder scan:** T2 stubs return `[]`/empty DTO and are replaced in T3–T5 (full code shown). `videoId` is explicitly left null in v1 with a comment (Graph returns a video URL on the post attachment, not an ad-usable id; resolving it is deferred). No "TBD".

**Type consistency:** DTO names + fields (`PageRefDTO.accessToken`, `PagePostDTO.likes/comments/shares/mediaType/imageUrl`, `AudienceSizeDTO.lowerBound/upperBound`, `AdPreviewDTO.format/body`) match across tasks. Method signatures in the interface (T2) match the implementations (T3–T5).

**Smoke-test note:** Graph field/endpoint exactness (`published_posts` fields, `delivery_estimate` bound keys, `generatepreviews` creative wrapping) is best-effort and must be confirmed with a real token before wiring the wizard. Tests verify OUR request/response mapping, not Graph acceptance.

## Next plans
- Plan 3 — `ad_drafts` model + `AdDraftService` + CRUD/autosave API + `marketing.ads.create` ability.
- Plan 4 — `PublishAdDraft` job + publish endpoint + gating + (uploadImage/Video for new-creative path).
- Plan 5 — FE wizard + post picker/preview + AntD Tour + AI slide-over.
