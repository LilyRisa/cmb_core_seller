<?php

declare(strict_types=1);

namespace CMBcoreSeller\Integrations\Channels\DTO;

/**
 * A category attribute definition returned by the marketplace, NORMALIZED to a
 * provider-agnostic shape the FE renders directly ({@see AttributeForm}).
 *
 * `inputType` PHẢI là một trong 4 giá trị chuẩn dưới — mỗi connector tự ánh xạ kiểu
 * thô của sàn (TikTok type/flags, Lazada input_type chữ, Shopee input_type số 1–5)
 * về tập này. `values` chuẩn hoá thành `[{id, name}]` để render option.
 */
final readonly class ListingAttributeDTO
{
    public const INPUT_TEXT = 'text';

    public const INPUT_NUMBER = 'number';

    public const INPUT_SELECT = 'select';

    public const INPUT_MULTI_SELECT = 'multi_select';

    /**
     * @param  array<int,array{id:string,name:string}>  $values  option chuẩn hoá (rỗng nếu nhập tự do)
     * @param  array<string,mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $required,
        public bool $isSaleProp,
        public string $inputType,
        public array $values = [],
        public array $raw = [],
    ) {}
}
