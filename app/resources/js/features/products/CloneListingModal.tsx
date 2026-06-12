import { useEffect, useMemo, useState } from 'react';
import { Alert, App as AntApp, Empty, Modal, Radio, Space, Tag, Typography } from 'antd';
import { errorMessage } from '@/lib/api';
import { useChannelAccounts } from '@/lib/channels';
import { useCloneListing } from './hooks';
import type { ListingDraft } from './api';

/**
 * Sao chép một listing nháp/đã đăng sang một gian hàng khác.
 *
 * Cùng nền tảng: copy đầy đủ dữ liệu đã validate. Khác nền tảng: chỉ copy nội dung
 * dùng chung (mô tả, ảnh, SKU) — nháp đích vẫn cần soạn ngành hàng/thuộc tính nên
 * `onCloned` được gọi để màn cha mở editor hoàn thiện trước khi đẩy.
 */
export function CloneListingModal({
    sourceListingId,
    sourceProvider,
    sourceChannelAccountId,
    open,
    onClose,
    onCloned,
}: {
    sourceListingId: number | null;
    sourceProvider: string | null;
    sourceChannelAccountId: number | null;
    open: boolean;
    onClose: () => void;
    /** Gọi sau khi tạo nháp đích thành công; `crossProvider` = true khi khác nền tảng (nên mở editor). */
    onCloned: (draft: ListingDraft, crossProvider: boolean) => void;
}) {
    const { message } = AntApp.useApp();
    const { data: channelData } = useChannelAccounts();
    const cloneListing = useCloneListing();
    const [targetShopId, setTargetShopId] = useState<number | null>(null);

    // Loại gian hàng nguồn khỏi danh sách đích (không thể clone vào chính nó).
    const targets = useMemo(
        () => (channelData?.data ?? []).filter((a) => a.id !== sourceChannelAccountId),
        [channelData, sourceChannelAccountId],
    );

    useEffect(() => {
        if (open) setTargetShopId(targets[0]?.id ?? null);
    }, [open, targets]);

    const targetShop = targets.find((a) => a.id === targetShopId) ?? null;
    const crossProvider = !!(targetShop && sourceProvider && targetShop.provider !== sourceProvider);

    const handleClone = () => {
        if (sourceListingId == null || targetShopId == null) return;
        cloneListing.mutate(
            { id: sourceListingId, channelAccountId: targetShopId },
            {
                onSuccess: (draft) => {
                    onClose();
                    if (crossProvider) {
                        message.warning('Đã sao chép — khác nền tảng nên cần soạn lại ngành hàng & thuộc tính.');
                    } else {
                        message.success('Đã sao chép sang gian hàng mới.');
                    }
                    onCloned(draft, crossProvider);
                },
                onError: (e) => message.error(errorMessage(e)),
            },
        );
    };

    return (
        <Modal
            title="Sao chép sang gian hàng khác"
            open={open}
            onCancel={onClose}
            okText="Sao chép"
            okButtonProps={{ disabled: targetShopId == null, loading: cloneListing.isPending }}
            onOk={handleClone}
        >
            <Typography.Text type="secondary">Chọn gian hàng đích</Typography.Text>
            <div style={{ marginTop: 8 }}>
                {targets.length === 0 ? (
                    <Empty description="Không có gian hàng đích nào khác. Kết nối thêm gian hàng ở mục Gian hàng." />
                ) : (
                    <Radio.Group value={targetShopId ?? undefined} onChange={(e) => setTargetShopId(e.target.value)}>
                        <Space direction="vertical">
                            {targets.map((a) => (
                                <Radio key={a.id} value={a.id}>
                                    {a.name} <Tag>{a.provider}</Tag>
                                </Radio>
                            ))}
                        </Space>
                    </Radio.Group>
                )}
            </div>
            {crossProvider && (
                <Alert
                    type="info"
                    showIcon
                    style={{ marginTop: 12 }}
                    message="Khác nền tảng"
                    description="Chỉ sao chép mô tả, ảnh và SKU (giá/tồn/đóng gói). Ngành hàng, thương hiệu, thuộc tính và vận chuyển sẽ để trống — bạn cần soạn lại trong trình chỉnh sửa trước khi đẩy."
                />
            )}
        </Modal>
    );
}
