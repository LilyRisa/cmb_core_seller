<?php

namespace Tests\Feature\EInvoice;

use CMBcoreSeller\Modules\Tenancy\Support\PermissionCatalog;
use Tests\TestCase;

class EInvoicePermissionCatalogTest extends TestCase
{
    public function test_einvoice_permissions_registered(): void
    {
        $all = PermissionCatalog::all();
        foreach (['einvoice.view', 'einvoice.config', 'einvoice.issue', 'einvoice.manage'] as $k) {
            $this->assertContains($k, $all, "Thiếu quyền {$k}");
        }
    }
}
