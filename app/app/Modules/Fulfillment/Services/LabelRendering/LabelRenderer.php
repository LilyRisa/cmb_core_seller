<?php

namespace CMBcoreSeller\Modules\Fulfillment\Services\LabelRendering;

use CMBcoreSeller\Modules\Fulfillment\Models\ShippingLabelTemplate;
use CMBcoreSeller\Modules\Orders\Models\Order;
use Illuminate\Support\Collection;

class LabelRenderer
{
    public function __construct(
        private readonly FieldTypeRegistry $registry,
        private readonly FieldRenderHelpers $helpers,
        private readonly LabelDataResolver $resolver,
    ) {}

    /**
     * Render 1 trang body (chưa wrap shell) — dùng để ghép nhiều order.
     */
    public function renderBody(DataContext $ctx, ShippingLabelTemplate $tpl): string
    {
        $html = '<div class="page" style="position:relative;width:'.$tpl->paper_w_mm.'mm;'.
                ($tpl->paper_h_mm > 0 ? 'height:'.$tpl->paper_h_mm.'mm;' : '').'overflow:hidden">';
        foreach ((array) ($tpl->schema['fields'] ?? []) as $field) {
            $type = $this->registry->get((string) ($field['type'] ?? ''));
            if (! $type) {
                continue;
            }
            try {
                $html .= $type->renderHtml($field, $ctx, $this->helpers);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $html.'</div>';
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @param  array<int, array{name:string,phone:string,address:string}>  $senderMap  Người gửi theo order_id
     *                                                                                 (phiếu giao hàng — từ hồ sơ đã chọn). Rỗng ⇒ resolver suy người gửi từ kho như cũ.
     */
    public function renderBatch(Collection $orders, ShippingLabelTemplate $tpl, array $senderMap = []): string
    {
        $pages = [];
        foreach ($orders as $order) {
            $ctx = $this->resolver->resolve($order, $senderMap[(int) $order->getKey()] ?? null);
            $pages[] = $this->renderBody($ctx, $tpl);
        }

        return $this->shell($tpl, implode('<div class="page-break" style="page-break-after:always"></div>', $pages));
    }

    public function renderSample(string $profile, ShippingLabelTemplate $tpl, SampleDataFactory $factory): string
    {
        return $this->shell($tpl, $this->renderBody($factory->build($profile), $tpl));
    }

    private function shell(ShippingLabelTemplate $tpl, string $body): string
    {
        $size = $tpl->paper_h_mm > 0 ? $tpl->paper_w_mm.'mm '.$tpl->paper_h_mm.'mm' : $tpl->paper_w_mm.'mm auto';

        return '<!doctype html><html><head><meta charset="utf-8"><style>'.
               '@page{size:'.$size.';margin:0}'.
               '*{font-family:DejaVu Sans,Arial,sans-serif;box-sizing:border-box}'.
               'body{margin:0;padding:0;color:#222}'.
               '.page{page-break-inside:avoid}'.
               '</style></head><body>'.$body.$this->helpers->autofitScript().'</body></html>';
    }
}
