import { Deferred, Head, usePoll } from '@inertiajs/react';
import {
    Activity,
    ArrowDownToLine,
    ArrowUpFromLine,
    BellRing,
    Clock3,
    ScanLine,
    UserRoundCheck,
} from 'lucide-react';
import type { CSSProperties } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useUiTheme } from '@/hooks/use-ui-theme';

type DashboardSummary = {
    totalIn: number;
    totalOut: number;
    uniqueStudents: number;
    smsAttention: number;
    activeTurnstiles: number;
    inactiveTurnstiles: number;
    lastScanLabel: string;
};

type HourlyActivityPoint = {
    hour: number;
    label: string;
    shortLabel: string;
    scanCount: number;
};

type TodayActivity = {
    totalScans: number;
    peakHourLabel: string | null;
    latestScanLabel: string | null;
    hasData: boolean;
    points: HourlyActivityPoint[];
};

type RecentActivityItem = {
    id: number;
    studentName: string;
    roleLabel: string;
    gradeSection: string | null;
    actionLabel: string;
    turnstileName: string;
    scannedAtLabel: string;
};

type TurnstileStatusItem = {
    id: number;
    name: string;
    location: string;
    statusLabel: string;
    statusTone: 'default' | 'secondary' | 'destructive';
    todayScans: number;
    lastScanLabel: string;
};

type DashboardProps = {
    summary: DashboardSummary;
    generatedAt: string;
    todayActivity?: TodayActivity;
    recentActivity?: RecentActivityItem[];
    turnstileStatus?: TurnstileStatusItem[];
};

const numberFormatter = new Intl.NumberFormat('en-US');

function DashboardLoadingRows() {
    return (
        <div className="space-y-3">
            {Array.from({ length: 4 }).map((_, index) => (
                <div key={index} className="grid grid-cols-[1.4fr_1fr_1fr] gap-3">
                    <Skeleton className="h-12" />
                    <Skeleton className="h-12" />
                    <Skeleton className="h-12" />
                </div>
            ))}
        </div>
    );
}

function DashboardLoadingList() {
    return (
        <div className="space-y-3">
            {Array.from({ length: 4 }).map((_, index) => (
                <Skeleton key={index} className="h-18" />
            ))}
        </div>
    );
}

export default function Dashboard({
    summary,
    generatedAt,
    todayActivity,
    recentActivity,
    turnstileStatus,
}: DashboardProps) {
    usePoll(20000, {
        only: ['summary', 'generatedAt', 'todayActivity', 'recentActivity', 'turnstileStatus'],
    }, {
        keepAlive: true,
    });
    const { theme, palette, rgb, orgInitial, orgName } = useUiTheme();

    const kpiCards = [
        {
            title: 'Time In Today',
            value: summary.totalIn,
            description: `Last scan recorded at ${summary.lastScanLabel}`,
            icon: ArrowDownToLine,
        },
        {
            title: 'Time Out Today',
            value: summary.totalOut,
            description: `${summary.activeTurnstiles} active turnstiles`,
            icon: ArrowUpFromLine,
        },
        {
            title: 'Unique Students',
            value: summary.uniqueStudents,
            description: `${summary.inactiveTurnstiles} inactive turnstiles`,
            icon: UserRoundCheck,
        },
        {
            title: 'SMS Attention',
            value: summary.smsAttention,
            description: `Pending or failed notices as of ${generatedAt}`,
            icon: BellRing,
        },
    ];

    const activityPoints = todayActivity?.points ?? [];
    const peakHourlyCount = Math.max(...activityPoints.map((point) => point.scanCount), 1);
    const themedCardClassName = 'border bg-transparent shadow-none';
    const sectionSurfaceStyle = {
        backgroundColor: 'transparent',
        borderColor: rgb(palette.primary['200'], 0.95),
        color: rgb(palette.primary['800']),
    } satisfies CSSProperties;
    const mutedSurfaceStyle = {
        backgroundColor: 'transparent',
        borderColor: rgb(palette.primary['200'], 0.82),
        color: rgb(palette.primary['800']),
    } satisfies CSSProperties;
    const descriptionStyle = {
        color: rgb(palette.primary['500']),
    } satisfies CSSProperties;
    const titleStyle = {
        color: rgb(palette.primary['800']),
    } satisfies CSSProperties;
    const shellStyle = {
        '--dashboard-primary-50': palette.primary['50'],
        '--dashboard-primary-100': palette.primary['100'],
        '--dashboard-primary-500': palette.primary['500'],
        '--dashboard-primary-700': palette.primary['700'],
        '--dashboard-secondary-50': palette.secondary['50'],
        '--dashboard-secondary-100': palette.secondary['100'],
        '--dashboard-tertiary-500': palette.tertiary['500'],
    } as CSSProperties;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4" style={shellStyle}>
                <section
                    className="overflow-hidden rounded-[1.75rem] border"
                    style={{
                        backgroundColor: rgb(palette.primary['700']),
                        borderColor: rgb(palette.primary['600'], 0.95),
                        color: rgb(palette.secondary['50']),
                    }}
                >
                    <div className="flex flex-col gap-6 px-6 py-6 md:px-8 md:py-7 xl:flex-row xl:items-center xl:justify-between">
                        <div className="flex items-center gap-4">
                            <div
                                className="flex h-16 w-16 shrink-0 items-center justify-center rounded-[1.35rem] border"
                                style={{
                                    backgroundColor: rgb(palette.secondary['50'], 0.08),
                                    borderColor: rgb(palette.secondary['50'], 0.22),
                                }}
                            >
                                {theme.logoUrl ? (
                                    <img
                                        src={theme.logoUrl}
                                        alt={orgName}
                                        className="h-10 w-10 object-contain"
                                    />
                                ) : (
                                    <span className="text-xl font-black tracking-tight text-white">{orgInitial}</span>
                                )}
                            </div>

                            <div className="space-y-2">
                                <p className="text-xs font-semibold tracking-[0.34em] uppercase text-white/68">
                                    {orgInitial}
                                </p>
                                <div>
                                    <h1 className="text-2xl font-black tracking-tight text-white md:text-3xl">{orgName}</h1>
                                    <p className="mt-1 text-sm text-white/78 md:text-base">
                                        Attendance dashboard for quick operational review and monitoring.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid gap-3 sm:grid-cols-2 xl:min-w-[24rem]">
                            <div
                                className="rounded-2xl border px-4 py-3"
                                style={{
                                    backgroundColor: rgb(palette.secondary['50'], 0.08),
                                    borderColor: rgb(palette.secondary['50'], 0.22),
                                }}
                            >
                                <p className="text-xs font-semibold tracking-[0.22em] uppercase text-white/64">
                                    Last Scan
                                </p>
                                <p className="mt-2 text-2xl font-black tabular-nums text-white">{summary.lastScanLabel}</p>
                            </div>
                            <div
                                className="rounded-2xl border px-4 py-3"
                                style={{
                                    backgroundColor: rgb(palette.secondary['50'], 0.08),
                                    borderColor: rgb(palette.secondary['50'], 0.22),
                                }}
                            >
                                <p className="text-xs font-semibold tracking-[0.22em] uppercase text-white/72">
                                    Active Devices
                                </p>
                                <p className="mt-2 text-2xl font-black tabular-nums text-white">{summary.activeTurnstiles}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {kpiCards.map((card) => {
                        const Icon = card.icon;

                        return (
                            <Card key={card.title} className={themedCardClassName} style={sectionSurfaceStyle}>
                                <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                                    <div>
                                        <CardDescription style={descriptionStyle}>{card.title}</CardDescription>
                                        <CardTitle className="mt-2 text-3xl font-black tracking-tight" style={titleStyle}>
                                            {numberFormatter.format(card.value)}
                                        </CardTitle>
                                    </div>
                                    <div
                                        className="rounded-xl border p-2.5"
                                        style={{
                                            backgroundColor: rgb(palette.primary['50'], 0.94),
                                            borderColor: rgb(palette.primary['200'], 0.9),
                                            color: rgb(palette.primary['700']),
                                        }}
                                    >
                                        <Icon className="size-5" />
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm" style={descriptionStyle}>{card.description}</p>
                                </CardContent>
                            </Card>
                        );
                    })}
                </section>

                <section className="grid gap-4 xl:grid-cols-[1.35fr_0.95fr]">
                    <Deferred
                        data="todayActivity"
                        fallback={
                            <Card className={themedCardClassName} style={sectionSurfaceStyle}>
                                <CardHeader>
                                    <CardDescription style={descriptionStyle}>Today&apos;s Activity</CardDescription>
                                    <CardTitle style={titleStyle}>Current Day Scan Trend</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-5">
                                    <div className="grid gap-3 sm:grid-cols-3">
                                        {Array.from({ length: 3 }).map((_, index) => (
                                            <Skeleton key={index} className="h-16" />
                                        ))}
                                    </div>
                                    <div className="flex h-44 items-end gap-2">
                                        {Array.from({ length: 14 }).map((_, index) => (
                                            <Skeleton key={index} className="w-full" style={{ height: `${40 + ((index % 5) * 16)}px` }} />
                                        ))}
                                    </div>
                                    <div className="grid grid-cols-4 gap-2">
                                        {Array.from({ length: 4 }).map((_, index) => (
                                            <Skeleton key={index} className="h-4" />
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        }
                    >
                        <Card className={themedCardClassName} style={sectionSurfaceStyle}>
                            <CardHeader className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <CardDescription style={descriptionStyle}>Today&apos;s Activity</CardDescription>
                                    <CardTitle style={titleStyle}>Current Day Scan Trend</CardTitle>
                                </div>
                                <Badge
                                    variant="secondary"
                                    className="gap-1.5 border-0"
                                    style={{
                                        backgroundColor: rgb(palette.primary['100']),
                                        color: rgb(palette.primary['700']),
                                    }}
                                >
                                    <Activity className="size-3.5" />
                                    Updated {generatedAt}
                                </Badge>
                            </CardHeader>
                            <CardContent className="space-y-5">
                                <div className="grid gap-3 sm:grid-cols-3">
                                    <div className="rounded-2xl border px-4 py-3" style={mutedSurfaceStyle}>
                                        <p className="text-xs font-semibold uppercase tracking-[0.18em]" style={descriptionStyle}>
                                            Total
                                        </p>
                                        <p className="mt-2 text-2xl font-black tabular-nums" style={titleStyle}>
                                            {numberFormatter.format(todayActivity?.totalScans ?? 0)}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border px-4 py-3" style={mutedSurfaceStyle}>
                                        <p className="text-xs font-semibold uppercase tracking-[0.18em]" style={descriptionStyle}>
                                            Peak Hour
                                        </p>
                                        <p className="mt-2 text-2xl font-black tabular-nums" style={titleStyle}>
                                            {todayActivity?.peakHourLabel ?? '--'}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border px-4 py-3" style={mutedSurfaceStyle}>
                                        <p className="text-xs font-semibold uppercase tracking-[0.18em]" style={descriptionStyle}>
                                            Last Scan
                                        </p>
                                        <p className="mt-2 text-2xl font-black tabular-nums" style={titleStyle}>
                                            {todayActivity?.latestScanLabel ?? '--'}
                                        </p>
                                    </div>
                                </div>

                                {todayActivity?.hasData ? (
                                    <>
                                        <div className="flex h-48 items-end gap-2">
                                            {activityPoints.map((point) => (
                                                <div key={point.hour} className="flex min-w-0 flex-1 flex-col items-center gap-2">
                                                    <div className="flex h-40 w-full items-end">
                                                        <div
                                                            className="w-full rounded-t-[0.9rem] transition-[height]"
                                                            style={{
                                                                height: `${Math.max((point.scanCount / peakHourlyCount) * 100, point.scanCount > 0 ? 10 : 0)}%`,
                                                                background: `linear-gradient(180deg, ${rgb(palette.tertiary['500'])} 0%, ${rgb(palette.primary['500'])} 100%)`,
                                                                boxShadow: `0 10px 24px ${rgb(palette.primary['200'], 0.5)}`,
                                                            }}
                                                            title={`${point.label}: ${point.scanCount} scans`}
                                                        />
                                                    </div>
                                                    <div className="text-xs font-semibold tabular-nums" style={titleStyle}>
                                                        {point.scanCount}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>

                                        <div className="grid grid-cols-4 gap-2 text-center text-xs font-semibold tracking-[0.18em] uppercase" style={descriptionStyle}>
                                            {activityPoints
                                                .filter((point, index) => index === 0 || index === 3 || index === 6 || index === 12)
                                                .map((point) => (
                                                    <div key={`tick-${point.hour}`}>{point.label}</div>
                                                ))}
                                        </div>
                                    </>
                                ) : (
                                    <div
                                        className="flex min-h-52 flex-col items-center justify-center rounded-[1.5rem] border px-6 py-10 text-center"
                                        style={mutedSurfaceStyle}
                                    >
                                        <div
                                            className="flex h-14 w-14 items-center justify-center rounded-full"
                                            style={{
                                                backgroundColor: rgb(palette.primary['100']),
                                                color: rgb(palette.primary['700']),
                                            }}
                                        >
                                            <Activity className="size-6" />
                                        </div>
                                        <p className="mt-4 text-lg font-bold" style={titleStyle}>No scans recorded today</p>
                                        <p className="mt-2 max-w-md text-sm" style={descriptionStyle}>
                                            This chart refreshes for the current day only and will populate once attendance scans are logged.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </Deferred>

                    <Deferred
                        data="turnstileStatus"
                        fallback={
                            <Card className={themedCardClassName} style={sectionSurfaceStyle}>
                                <CardHeader>
                                    <CardDescription style={descriptionStyle}>Device Health</CardDescription>
                                    <CardTitle style={titleStyle}>Turnstile Status</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <DashboardLoadingList />
                                </CardContent>
                            </Card>
                        }
                    >
                        <Card className={themedCardClassName} style={sectionSurfaceStyle}>
                            <CardHeader>
                                <CardDescription style={descriptionStyle}>Device Health</CardDescription>
                                <CardTitle style={titleStyle}>Turnstile Status</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {(turnstileStatus ?? []).map((turnstile) => (
                                    <div
                                        key={turnstile.id}
                                        className="flex items-start justify-between gap-3 rounded-xl border p-4"
                                        style={mutedSurfaceStyle}
                                    >
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <p className="font-semibold">{turnstile.name}</p>
                                                <Badge
                                                    variant={turnstile.statusTone}
                                                    style={turnstile.statusTone === 'destructive'
                                                        ? undefined
                                                        : {
                                                            backgroundColor: rgb(palette.primary['100']),
                                                            color: rgb(palette.primary['700']),
                                                            borderColor: rgb(palette.primary['200']),
                                                        }}
                                                >
                                                    {turnstile.statusLabel}
                                                </Badge>
                                            </div>
                                            <p className="text-sm" style={descriptionStyle}>{turnstile.location}</p>
                                        </div>

                                        <div className="text-right text-sm">
                                            <p className="font-semibold tabular-nums">{turnstile.todayScans} scans</p>
                                            <p style={descriptionStyle}>Last scan {turnstile.lastScanLabel}</p>
                                        </div>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </Deferred>
                </section>

                <Deferred
                    data="recentActivity"
                    fallback={
                        <Card className={themedCardClassName} style={sectionSurfaceStyle}>
                            <CardHeader>
                                <CardDescription style={descriptionStyle}>Latest Attendance Records</CardDescription>
                                <CardTitle style={titleStyle}>Recent Activity</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <DashboardLoadingRows />
                            </CardContent>
                        </Card>
                        }
                    >
                    <Card className={themedCardClassName} style={sectionSurfaceStyle}>
                        <CardHeader className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <CardDescription style={descriptionStyle}>Latest Attendance Records</CardDescription>
                                    <CardTitle style={titleStyle}>Recent Activity</CardTitle>
                                </div>
                            <Badge
                                variant="outline"
                                className="gap-1.5"
                                style={{
                                    backgroundColor: rgb(palette.secondary['50'], 0.55),
                                    borderColor: rgb(palette.primary['200']),
                                    color: rgb(palette.primary['700']),
                                }}
                            >
                                <Clock3 className="size-3.5" />
                                Quick View for Recent Activity
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[42rem] text-sm">
                                    <thead className="text-left text-xs uppercase tracking-[0.18em]" style={descriptionStyle}>
                                        <tr className="border-b" style={{ borderColor: rgb(palette.primary['100']) }}>
                                            <th className="px-3 py-3 font-semibold">Name</th>
                                            <th className="px-3 py-3 font-semibold">Context</th>
                                            <th className="px-3 py-3 font-semibold">Action</th>
                                            <th className="px-3 py-3 font-semibold">Turnstile</th>
                                            <th className="px-3 py-3 font-semibold">Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {(recentActivity ?? []).map((activityItem) => (
                                            <tr
                                                key={activityItem.id}
                                                className="border-b last:border-b-0"
                                                style={{ borderColor: rgb(palette.primary['100']) }}
                                            >
                                                <td className="px-3 py-3">
                                                    <div className="font-semibold">{activityItem.studentName}</div>
                                                    <div style={descriptionStyle}>{activityItem.roleLabel}</div>
                                                </td>
                                                <td className="px-3 py-3" style={descriptionStyle}>
                                                    {activityItem.gradeSection ?? 'General access'}
                                                </td>
                                                <td className="px-3 py-3">
                                                    <Badge
                                                        variant={
                                                            activityItem.actionLabel === 'Time Out'
                                                                ? 'outline'
                                                                : 'secondary'
                                                        }
                                                        className="gap-1.5"
                                                        style={activityItem.actionLabel === 'Time Out'
                                                            ? {
                                                                backgroundColor: rgb(palette.secondary['50'], 0.55),
                                                                borderColor: rgb(palette.primary['200']),
                                                                color: rgb(palette.primary['700']),
                                                            }
                                                            : {
                                                                backgroundColor: rgb(palette.tertiary['500'], 0.18),
                                                                color: rgb(palette.primary['800']),
                                                                borderColor: rgb(palette.tertiary['500'], 0.28),
                                                            }}
                                                    >
                                                        <ScanLine className="size-3.5" />
                                                        {activityItem.actionLabel}
                                                    </Badge>
                                                </td>
                                                <td className="px-3 py-3" style={descriptionStyle}>{activityItem.turnstileName}</td>
                                                <td className="px-3 py-3 font-semibold tabular-nums">{activityItem.scannedAtLabel}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </Deferred>
            </div>
        </>
    );
}
