import { Deferred, Head, usePoll } from '@inertiajs/react';
import { AlertCircle, CheckCircle, Send, Users } from 'lucide-react';

import { AttendanceChart } from '@/components/dashboard/attendance-chart';
import { RecentActivityFeed } from '@/components/dashboard/recent-activity-feed';
import { SmsStatusTable } from '@/components/dashboard/sms-status-table';
import { StatCard } from '@/components/dashboard/stat-card';
import { TurnstileStatusList } from '@/components/dashboard/turnstile-status-list';
import { Skeleton } from '@/components/ui/skeleton';
import type { DashboardProps } from '@/types/dashboard';

export default function Dashboard({
    stats,
    hourlyBreakdown,
    recentActivity,
    turnstiles,
    smsEntries,
}: DashboardProps) {
    // Poll every 15 seconds to refresh the dashboard data
    usePoll(15_000);

    return (
        <>
            <Head title="Dashboard" />

            <div className="px-6 py-10 md:px-10">
                <div className="space-y-6">
                        {/* KPI Cards Row */}
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <StatCard
                                title="Timed-In Students"
                                value={stats.currentlyTimedIn}
                                subtitle="Currently present in campus"
                                icon={Users}
                                themeColor="primary"
                            />
                            <StatCard
                                title="Pending SMS Alerts"
                                value={stats.pendingSmsCount}
                                subtitle="Awaiting dispatch queue"
                                icon={Send}
                                themeColor="tertiary"
                            />
                            <StatCard
                                title="Failed SMS Notifications"
                                value={stats.failedSmsCount}
                                subtitle="Requires manual retry"
                                icon={AlertCircle}
                                isDestructive
                            />
                            <StatCard
                                title="Active Terminals"
                                value={`${stats.activeTurnstiles}/${stats.totalTurnstiles}`}
                                subtitle="Operational turnstile gates"
                                icon={CheckCircle}
                                themeColor="secondary"
                            />
                        </div>

                        {/* Primary Activity & Analytics Grid */}
                        <div className="grid gap-6 md:grid-cols-1 lg:grid-cols-3">
                            <div className="lg:col-span-2">
                                <Deferred
                                    data="hourlyBreakdown"
                                    fallback={
                                        <div className="flex flex-col h-full min-h-[400px] border rounded-xl p-6 gap-4 bg-card">
                                            <Skeleton className="h-6 w-1/3" />
                                            <Skeleton className="h-4 w-1/2" />
                                            <Skeleton className="flex-1 w-full mt-4" />
                                        </div>
                                    }
                                >
                                    <AttendanceChart data={hourlyBreakdown} />
                                </Deferred>
                            </div>
                            <div>
                                <Deferred
                                    data="recentActivity"
                                    fallback={
                                        <div className="flex flex-col h-full min-h-[400px] border rounded-xl p-6 gap-4 bg-card">
                                            <Skeleton className="h-6 w-1/3" />
                                            <Skeleton className="h-4 w-1/2" />
                                            <div className="flex-1 flex flex-col gap-3 mt-4">
                                                {[...Array(5)].map((_, i) => (
                                                    <div key={i} className="flex items-center gap-3">
                                                        <Skeleton className="size-9 rounded-full" />
                                                        <div className="flex-1 space-y-2">
                                                            <Skeleton className="h-4 w-3/4" />
                                                            <Skeleton className="h-3 w-1/2" />
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    }
                                >
                                    <RecentActivityFeed activity={recentActivity} />
                                </Deferred>
                            </div>
                        </div>

                        {/* Secondary Logs & Terminals Grid */}
                        <div className="grid gap-6 md:grid-cols-1 lg:grid-cols-2">
                            <Deferred
                                data="turnstiles"
                                fallback={
                                    <div className="flex flex-col h-full min-h-[350px] border rounded-xl p-6 gap-4 bg-card">
                                        <Skeleton className="h-6 w-1/3" />
                                        <Skeleton className="h-4 w-1/2" />
                                        <div className="flex-1 flex flex-col gap-4 mt-4">
                                            {[...Array(3)].map((_, i) => (
                                                <div key={i} className="flex items-center justify-between">
                                                    <div className="space-y-2">
                                                        <Skeleton className="h-4 w-32" />
                                                        <Skeleton className="h-3 w-48" />
                                                    </div>
                                                    <Skeleton className="h-6 w-16" />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                }
                            >
                                <TurnstileStatusList turnstiles={turnstiles} />
                            </Deferred>

                            <Deferred
                                data="smsEntries"
                                fallback={
                                    <div className="flex flex-col h-full min-h-[350px] border rounded-xl p-6 gap-4 bg-card">
                                        <Skeleton className="h-6 w-1/3" />
                                        <Skeleton className="h-4 w-1/2" />
                                        <div className="flex-1 flex flex-col gap-4 mt-4">
                                            {[...Array(4)].map((_, i) => (
                                                <div key={i} className="flex items-center justify-between">
                                                    <Skeleton className="h-4 w-24" />
                                                    <Skeleton className="h-4 w-28" />
                                                    <Skeleton className="h-4 w-16" />
                                                    <Skeleton className="h-4 w-16" />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                }
                            >
                                <SmsStatusTable smsEntries={smsEntries} />
                            </Deferred>
                        </div>
                    </div>
                </div>
        </>
    );
}
