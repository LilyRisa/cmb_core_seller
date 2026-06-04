import { type ReactNode, useEffect, useRef } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { App, Button, Card, Col, Empty, Row, Steps, Tag } from 'antd';
import {
    AimOutlined,
    DollarOutlined,
    LayoutOutlined,
    PictureOutlined,
    QuestionCircleOutlined,
    RocketOutlined,
    TeamOutlined,
} from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import { useAdDraft, useCreateDraft, useUpdateDraft } from '@/lib/adWizard';
import { PageHeader } from '@/components/PageHeader';

const STEP_LABELS = ['Mục tiêu', 'Ngân sách', 'Đối tượng', 'Vị trí', 'Nội dung', 'Xuất bản'];
const STEP_ICONS = [
    <AimOutlined />,
    <DollarOutlined />,
    <TeamOutlined />,
    <LayoutOutlined />,
    <PictureOutlined />,
    <RocketOutlined />,
];

function renderStep(step: number): ReactNode {
    return <Empty description={STEP_LABELS[step]} />;
}

export function AdWizardPage() {
    const { message } = App.useApp();
    const { draftId: draftIdParam } = useParams<{ draftId?: string }>();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const draftId = draftIdParam != null ? Number(draftIdParam) : null;
    const accountIdFromParams = searchParams.get('accountId');
    const accountId = accountIdFromParams != null ? Number(accountIdFromParams) : null;

    // Store selectors — individual to avoid re-render storms
    const storeDraftId = useDraftStore((s) => s.draftId);
    const step = useDraftStore((s) => s.step);
    const name = useDraftStore((s) => s.name);
    const objective = useDraftStore((s) => s.objective);
    const payload = useDraftStore((s) => s.payload);
    const dirty = useDraftStore((s) => s.dirty);
    const load = useDraftStore((s) => s.load);
    const setStep = useDraftStore((s) => s.setStep);
    const markSaved = useDraftStore((s) => s.markSaved);

    // Mutations
    const createDraft = useCreateDraft();
    const updateDraft = useUpdateDraft();

    // Query for existing draft (only when draftId param present)
    const { data: draftData } = useAdDraft(draftId);

    // Guard ref: ensures create-draft fires only once when entering /new
    const createFiredRef = useRef(false);
    // Guard ref: ensures load only fires once per draftId
    const loadedDraftIdRef = useRef<number | null>(null);

    // Bootstrap: load existing draft
    useEffect(() => {
        if (draftId != null && draftData != null && loadedDraftIdRef.current !== draftId) {
            loadedDraftIdRef.current = draftId;
            load({
                id: draftData.id,
                accountId: draftData.ad_account_id,
                name: draftData.name,
                objective: draftData.objective,
                payload: draftData.payload,
            });
        }
    }, [draftId, draftData, load]);

    // Bootstrap: create new draft when no draftId param but accountId is present.
    // Wrapped in a debounced timer so React 18 StrictMode's double-invoke (mount →
    // cleanup → mount) cancels the first timer and fires the create exactly once.
    useEffect(() => {
        if (draftId != null || accountId == null || createFiredRef.current) return;
        const timer = setTimeout(() => {
            createFiredRef.current = true;
            createDraft.mutate(
                { ad_account_id: accountId, payload: {} },
                {
                    onSuccess: (created) => {
                        load({
                            id: created.id,
                            accountId: created.ad_account_id,
                            name: created.name,
                            objective: created.objective,
                            payload: created.payload,
                        });
                        navigate('/marketing/ads/' + created.id + '/edit', { replace: true });
                    },
                    onError: () => {
                        message.error('Không tạo được bản nháp. Vui lòng thử lại.');
                    },
                },
            );
        }, 50);

        return () => clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [draftId, accountId]);

    // Autosave: debounce 800ms when dirty and draftId is known
    useEffect(() => {
        const activeDraftId = storeDraftId;
        if (!dirty || activeDraftId == null) return;

        const timer = setTimeout(() => {
            // Read latest values inside timeout via closure (they are reactive via deps)
            updateDraft.mutate(
                { id: activeDraftId, patch: { name, objective: objective ?? undefined, payload } },
                { onSuccess: () => markSaved() },
            );
        }, 800);

        return () => clearTimeout(timer);
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [dirty, storeDraftId, name, objective, payload]);

    // No accountId and no draftId — dead end
    if (draftId == null && accountId == null) {
        return (
            <div>
                <PageHeader title="Tạo quảng cáo Facebook" />
                <Card>
                    <Empty description="Vui lòng chọn tài khoản quảng cáo trước.">
                        <Button onClick={() => navigate('/marketing')}>Quay lại Marketing</Button>
                    </Empty>
                </Card>
            </div>
        );
    }

    const autosaveIndicator = dirty
        ? <Tag color="processing">Đang lưu...</Tag>
        : <Tag color="success">Đã lưu</Tag>;

    const stepItems = STEP_LABELS.map((label, i) => ({
        title: label,
        icon: STEP_ICONS[i],
    }));

    return (
        <div>
            <PageHeader
                title="Tạo quảng cáo Facebook"
                subtitle={autosaveIndicator}
                extra={
                    <Button icon={<QuestionCircleOutlined />}>Hướng dẫn</Button>
                }
            />

            <Row gutter={16} style={{ alignItems: 'flex-start' }}>
                {/* Left: vertical steps */}
                <Col flex="200px">
                    <Card styles={{ body: { padding: '16px 8px' } }}>
                        <Steps
                            direction="vertical"
                            current={step}
                            onChange={setStep}
                            items={stepItems}
                            size="small"
                            style={{ minHeight: 360 }}
                        />
                    </Card>
                </Col>

                {/* Center: step content */}
                <Col flex="1">
                    <Card
                        styles={{ body: { minHeight: 360 } }}
                        actions={[
                            <Button
                                key="prev"
                                disabled={step === 0}
                                onClick={() => setStep(step - 1)}
                            >
                                Quay lại
                            </Button>,
                            <Button
                                key="next"
                                type="primary"
                                disabled={step === 5}
                                onClick={() => setStep(step + 1)}
                            >
                                Tiếp tục
                            </Button>,
                        ]}
                    >
                        {renderStep(step)}
                    </Card>
                </Col>

                {/* Right: preview */}
                <Col flex="200px">
                    <Card title="Xem trước" styles={{ body: { minHeight: 200 } }}>
                        <Empty description="Xem trước sẽ hiển thị ở đây" />
                    </Card>
                </Col>
            </Row>
        </div>
    );
}
