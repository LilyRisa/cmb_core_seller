import { Tour } from 'antd';
import type { TourProps } from 'antd';

const steps: TourProps['steps'] = [
    {
        title: 'Chọn mục tiêu',
        description: 'Chọn mục tiêu — quyết định Facebook tối ưu cho điều gì.',
        target: null,
    },
    {
        title: 'Đặt ngân sách',
        description: 'Đặt ngân sách mỗi ngày — bắt đầu nhỏ 100–200k để thử.',
        target: null,
    },
    {
        title: 'Chọn nội dung',
        description: 'Chọn bài viết Trang có sẵn để giữ lượt thích/bình luận, tăng uy tín.',
        target: null,
    },
    {
        title: 'Xem trước & Xuất bản',
        description: 'Xem trước rồi Xuất bản — quảng cáo sẽ ở trạng thái Tạm dừng để bạn kiểm tra.',
        target: null,
    },
];

interface WizardTourProps {
    open: boolean;
    onClose: () => void;
}

export function WizardTour({ open, onClose }: WizardTourProps) {
    return <Tour open={open} onClose={onClose} steps={steps} />;
}
