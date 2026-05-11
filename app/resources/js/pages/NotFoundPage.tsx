import { Result, Button } from 'antd';
import { Link } from 'react-router-dom';

export function NotFoundPage() {
    return (
        <Result
            status="404"
            title="404"
            subTitle="Trang không tồn tại."
            extra={<Link to="/"><Button type="primary">Về trang chủ</Button></Link>}
        />
    );
}
