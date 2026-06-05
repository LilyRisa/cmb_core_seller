import { type ReactNode, useEffect, useRef, useState } from 'react';
import { useNavigate, useParams, useSearchParams } from 'react-router-dom';
import { App, Button, Card, Col, Empty, Row, Steps, Tag, Tooltip } from 'antd';
import {
    AimOutlined,
    DollarOutlined,
    LayoutOutlined,
    PictureOutlined,
    QuestionCircleOutlined,
    RobotOutlined,
    RocketOutlined,
    TeamOutlined,
} from '@ant-design/icons';
import { useDraftStore } from '@/lib/adWizard/draftStore';
import { useAdDraft, useCreateDraft, useUpdateDraft } from '@/lib/adWizard';
import { PageHeader } from '@/components/PageHeader';
import { StepObjective } from '@/pages/adWizard/StepObjective';
import { StepBudget } from '@/pages/adWizard/StepBudget';
import { StepAudience } from '@/pages/adWizard/StepAudience';
import { StepPlacements } from '@/pages/adWizard/StepPlacements';
import { StepCreative } from '@/pages/adWizard/StepCreative';
import { StepReview } from '@/pages/adWizard/StepReview';
import { WizardTour } from '@/pages/adWizard/WizardTour';
import { AiAssistantDrawer } from '@/pages/adWizard/AiAssistantDrawer';
import { AdSetSelector } from '@/pages/adWizard/AdSetSelector';
import { AdPreviewPanel } from '@/pages/adWizard/AdPreviewPanel';

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
    switch (step) {
        case 0: return <StepObjective />;
        case 1: return <><AdSetSelector /><StepBudget /></>;
        case 2: return <><AdSetSelector /><StepAudience /></>;
        case 3: return <><AdSetSelector /><StepPlacements /></>;
        case 4: return <><AdSetSelector /><StepCreative /></>;
        case 5: return <StepReview />;
        default: return <Empty description={STEP_LABELS[step]} />;
    }
}

export function AdWizardPage() {
    const { message } = App.useApp();
    const { draftId: draftIdParam } = useParams<{ draftId?: string }>();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();

    const [tourOpen, setTourOpen] = useState(false);
    const [aiOpen, setAiOpen] = useState(false);

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
    const adsets = useDraftStore((s) => s.adsets);
    const selectedAdSetKey = useDraftStore((s) => s.selectedAdSetKey);
    const copyAdSet = useDraftStore((s) => s.copyAdSet);
    const pasteClipboard = useDraftStore((s) => s.pasteClipboard);
    const duplicateAdSet = useDraftStore((s) => s.duplicateAdSet);
    const copyAd = useDraftStore((s) => s.copyAd);
    const duplicateAd = useDraftStore((s) => s.duplicateAd);

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

    // Clone shortcuts: Ctrl/Cmd+C copy, +V paste (clone), +D duplicate.
    // On the Nội dung step (4) they target the focused AD; elsewhere the AD SET.
    // Ignored while typing in a field or when text is selected.
    useEffect(() => {
        function onKey(e: KeyboardEvent) {
            if (!(e.ctrlKey || e.metaKey) || e.altKey) return;
            const t = e.target as HTMLElement | null;
            if (t != null && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.isContentEditable)) return;
            const k = e.key.toLowerCase();
            const hasTextSelection = (window.getSelection()?.toString() ?? '') !== '';
            const { step: curStep, selectedAdKey, selectedAdSetKey: adsetKey, clipboard } = useDraftStore.getState();
            const adMode = curStep === 4 && adsetKey != null && selectedAdKey != null;

            if (k === 'c' && !hasTextSelection) {
                if (adMode) {
                    copyAd(adsetKey!, selectedAdKey!);
                    message.success('Đã sao chép quảng cáo — Ctrl+V để dán.');
                    e.preventDefault();
                } else if (adsetKey != null) {
                    copyAdSet(adsetKey);
                    message.success('Đã sao chép nhóm quảng cáo — Ctrl+V để dán.');
                    e.preventDefault();
                }
            } else if (k === 'v' && clipboard != null) {
                pasteClipboard();
                message.success(clipboard.kind === 'ad' ? 'Đã dán bản sao quảng cáo.' : 'Đã dán bản sao nhóm quảng cáo.');
                e.preventDefault();
            } else if (k === 'd') {
                if (adMode) {
                    duplicateAd(adsetKey!, selectedAdKey!);
                    message.success('Đã nhân bản quảng cáo.');
                    e.preventDefault();
                } else if (adsetKey != null) {
                    duplicateAdSet(adsetKey);
                    message.success('Đã nhân bản nhóm quảng cáo.');
                    e.preventDefault();
                }
            }
        }
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [copyAdSet, pasteClipboard, duplicateAdSet, copyAd, duplicateAd, message]);

    // Auto-open tour on first visit
    useEffect(() => {
        if (localStorage.getItem('adwizard.tour.seen') == null) {
            setTourOpen(true);
            localStorage.setItem('adwizard.tour.seen', '1');
        }
    }, []);

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

    function canProceed(currentStep: number): boolean {
        switch (currentStep) {
            case 0: return objective != null;
            case 1: {
                const mode = payload.campaign?.budget_mode ?? 'adset';
                if (mode === 'campaign') return (payload.campaign?.daily_budget_major ?? 0) > 0;
                const sel = adsets.find((a) => a.key === selectedAdSetKey);
                return (sel?.budget?.daily_major ?? 0) > 0;
            }
            case 4: {
                return adsets.length > 0 && adsets.every(
                    (as) => as.ads.length > 0 && as.ads.every(
                        (ad) => (ad.creative.page_post_id ?? '') !== '' || (ad.creative.primary_text ?? '') !== '',
                    ),
                );
            }
            default: return true;
        }
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
                    <>
                        <Button icon={<QuestionCircleOutlined />} onClick={() => setTourOpen(true)}>
                            Hướng dẫn
                        </Button>
                        <Button icon={<RobotOutlined />} onClick={() => setAiOpen(true)} style={{ marginLeft: 8 }}>
                            Trợ lý AI
                        </Button>
                    </>
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
                            // Last step (Xuất bản) has its own publish button — no "Tiếp tục".
                            ...(step < 5 ? [
                                <Tooltip
                                    key="next"
                                    title={!canProceed(step) ? 'Hãy hoàn tất bước này' : undefined}
                                >
                                    <Button
                                        type="primary"
                                        disabled={!canProceed(step)}
                                        onClick={() => setStep(step + 1)}
                                    >
                                        Tiếp tục
                                    </Button>
                                </Tooltip>,
                            ] : []),
                        ]}
                    >
                        {renderStep(step)}
                    </Card>
                </Col>

                {/* Right: preview */}
                <Col flex="340px">
                    <Card title="Xem trước" styles={{ body: { minHeight: 200 } }}>
                        <AdPreviewPanel />
                    </Card>
                </Col>
            </Row>

            <WizardTour open={tourOpen} onClose={() => setTourOpen(false)} />
            <AiAssistantDrawer
                open={aiOpen}
                onClose={() => setAiOpen(false)}
                step={step}
                payload={payload}
            />
        </div>
    );
}
