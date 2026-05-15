import { ReactNode } from 'react';
import { Result, Button } from 'antd';
import { Link } from 'react-router-dom';
import { useAuth } from '@/lib/auth';

/**
 * SPEC 0020 — gate cho route `/admin/*`. Chỉ user có `is_super_admin=true` đi qua.
 * Không phải super-admin ⇒ render 403 page (không silent redirect — admin sai URL biết ngay).
 */
export function RequireSuperAdmin({ children }: { children: ReactNode }) {
    const { data: user } = useAuth();
    if (user?.is_super_admin === true) {
        return <>{children}</>;
    }

    return (
        <Result
            status="403"
            title="Không đủ quyền"
            subTitle="Trang này chỉ dành cho super-admin hệ thống."
            extra={<Link to="/"><Button type="primary">Về Bảng điều khiển</Button></Link>}
        />
    );
}
