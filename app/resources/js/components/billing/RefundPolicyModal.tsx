import { Alert, Checkbox, Modal, Typography } from 'antd';
import { useEffect, useState } from 'react';

const { Paragraph } = Typography;

interface Props {
  open: boolean;
  mode: 'trial' | 'payment';
  loading?: boolean;
  onCancel: () => void;
  onAccept: () => void;
}

export default function RefundPolicyModal({ open, mode, loading, onCancel, onAccept }: Props) {
  const [agreed, setAgreed] = useState(false);
  useEffect(() => {
    if (!open) setAgreed(false);
  }, [open]);

  const isTrial = mode === 'trial';

  return (
    <Modal
      open={open}
      title="Điều khoản sử dụng"
      okText={isTrial ? 'Đăng ký trải nghiệm' : 'Tiếp tục thanh toán'}
      cancelText="Huỷ"
      confirmLoading={loading}
      okButtonProps={{ disabled: !agreed }}
      onCancel={onCancel}
      onOk={onAccept}
      destroyOnClose
    >
      <Alert
        type="warning"
        showIcon
        message="Chính sách không hoàn tiền"
        description={
          isTrial
            ? 'Gói Pro trải nghiệm áp dụng 1 lần duy nhất cho mỗi tài khoản. Khi hết thời gian trải nghiệm, hệ thống tự động chuyển về gói trước đó. Khoản thanh toán (nếu có) không được hoàn lại.'
            : 'Khoản thanh toán mua/nâng cấp gói không được hoàn lại sau khi giao dịch hoàn tất. Vui lòng kiểm tra kỹ trước khi thanh toán.'
        }
        style={{ marginBottom: 16 }}
      />
      <Paragraph type="secondary" style={{ fontSize: 13 }}>
        Bằng việc tiếp tục, bạn xác nhận đã đọc và đồng ý với điều khoản nêu trên.
      </Paragraph>
      <Checkbox checked={agreed} onChange={(e) => setAgreed(e.target.checked)}>
        Tôi đã đọc và đồng ý với điều khoản không hoàn lại.
      </Checkbox>
    </Modal>
  );
}
