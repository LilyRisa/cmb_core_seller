import { useState } from 'react';
import { App as AntApp, Button, Modal, Typography } from 'antd';
import { CrownOutlined } from '@ant-design/icons';
import { errorMessage } from '@/lib/api';
import { formatDate } from '@/lib/format';
import { REFUND_TERMS_VERSION, useDeclineProTrial, useProTrialEligibility, useRegisterProTrial } from '@/lib/billing';
import { useCan } from '@/lib/tenant';
import RefundPolicyModal from '@/components/billing/RefundPolicyModal';

/**
 * Popup mời tenant MỚI đăng ký trải nghiệm Pro — mount 1 lần ở AppLayout (cùng chỗ
 * `AnnouncementPopup`), hiện ở mọi trang sau đăng nhập khi `eligibility.show_popup === true`.
 *
 * "Không, cảm ơn" → tắt vĩnh viễn (gọi decline). Đóng bằng X/ESC → chỉ đóng phiên này,
 * lần tải trang/đăng nhập sau sẽ hiện lại (không gọi API gì).
 */
export function ProTrialOfferModal() {
    const { message } = AntApp.useApp();
    const canManage = useCan('billing.manage');
    const eligibilityQ = useProTrialEligibility();
    const registerProTrial = useRegisterProTrial();
    const decline = useDeclineProTrial();

    const [dismissed, setDismissed] = useState(false);
    const [termsOpen, setTermsOpen] = useState(false);

    const showOffer = canManage && !dismissed && !!eligibilityQ.data?.show_popup;
    if (!showOffer && !termsOpen) return null;

    const days = eligibilityQ.data?.duration_days ?? 30;
    const endsPreview = eligibilityQ.data?.ends_preview ? formatDate(eligibilityQ.data.ends_preview, false) : '';

    const acceptTrial = async () => {
        try {
            await registerProTrial.mutateAsync(REFUND_TERMS_VERSION);
            setTermsOpen(false);
            message.success('Đã kích hoạt gói Pro trải nghiệm!');
        } catch (e) {
            message.error(errorMessage(e));
        }
    };

    const declineOffer = () => {
        decline.mutate(undefined, {
            onSuccess: () => message.info('Đã ẩn lời mời — bạn vẫn có thể đăng ký ở Cài đặt > Gói.'),
            onError: (e) => message.error(errorMessage(e)),
        });
    };

    return (
        <>
            <Modal
                open={showOffer && !termsOpen}
                centered
                maskClosable={false}
                title={<><CrownOutlined /> Bạn được tặng {days} ngày dùng thử Pro!</>}
                footer={[
                    <Button key="decline" onClick={declineOffer} loading={decline.isPending}>Không, cảm ơn</Button>,
                    <Button key="accept" type="primary" icon={<CrownOutlined />} onClick={() => setTermsOpen(true)}>Đồng ý kích hoạt</Button>,
                ]}
                onCancel={() => setDismissed(true)}
            >
                <Typography.Paragraph>
                    Kích hoạt ngay để dùng thử toàn bộ tính năng gói Pro đến hết ngày{' '}
                    <strong>{endsPreview}</strong> — hoàn toàn miễn phí, tự động về gói hiện tại khi hết hạn.
                </Typography.Paragraph>
            </Modal>
            <RefundPolicyModal
                open={termsOpen}
                mode="trial"
                loading={registerProTrial.isPending}
                onCancel={() => setTermsOpen(false)}
                onAccept={acceptTrial}
            />
        </>
    );
}
