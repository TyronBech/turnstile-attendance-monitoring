import { router } from '@inertiajs/react';
import { AlertCircle, ArrowDownLeft, ArrowUpRight, CheckCircle2, Loader2, RefreshCw, Send } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAppearance } from '@/hooks/use-appearance';
import { useUiTheme } from '@/hooks/use-ui-theme';
import type { SmsEntry } from '@/types/dashboard';

type SmsStatusTableProps = {
    smsEntries: SmsEntry[];
};

export function SmsStatusTable({ smsEntries }: SmsStatusTableProps) {
    const { palette, rgb } = useUiTheme();
    const { resolvedAppearance } = useAppearance();
    const [retryingId, setRetryingId] = useState<number | null>(null);

    const isDark = resolvedAppearance === 'dark';
    const borderColor = isDark ? rgb(palette.primary['800']) : rgb(palette.primary['200']);

    const handleRetry = (id: number) => {
        setRetryingId(id);
        router.post(
            `/dashboard/retry-sms/${id}`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('SMS retry request dispatched.');
                    setRetryingId(null);
                },
                onError: () => {
                    toast.error('Failed to retry SMS request.');
                    setRetryingId(null);
                },
                onFinish: () => {
                    setRetryingId(null);
                },
            },
        );
    };

    const formatTime = (isoString: string) => {
        try {
            const date = new Date(isoString);

            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });
        } catch {
            return '--:--';
        }
    };

    return (
        <Card className="flex flex-col h-full min-h-[350px] border bg-card text-card-foreground" style={{ borderColor }}>
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>SMS Notification Log</CardTitle>
                        <CardDescription>Guardian updates status for today's scans</CardDescription>
                    </div>
                    <Send className="size-5 text-muted-foreground" />
                </div>
            </CardHeader>
            <CardContent className="flex-1 overflow-x-auto p-0">
                {smsEntries.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-muted-foreground py-16">
                        No SMS logs recorded today.
                    </div>
                ) : (
                    <div className="min-w-[600px] overflow-y-auto max-h-[320px]">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b bg-muted/30 text-xs font-semibold text-muted-foreground">
                                    <th className="py-3 px-6">Recipient / Student</th>
                                    <th className="py-3 px-4">Contact</th>
                                    <th className="py-3 px-4">Activity</th>
                                    <th className="py-3 px-4">Status</th>
                                    <th className="py-3 px-6 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {smsEntries.map((entry) => {
                                    const isTimeIn = entry.action === 'IN';
                                    const isPending = entry.smsStatus === 'PENDING';
                                    const isFailed = entry.smsStatus === 'FAILED';
                                    const isSent = entry.smsStatus === 'SENT';

                                    return (
                                        <tr key={entry.id} className="text-sm transition-colors hover:bg-muted/40">
                                            <td className="py-3 px-6 font-medium text-foreground">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-semibold">{entry.userName}</span>
                                                </div>
                                            </td>
                                            <td className="py-3 px-4 text-xs font-mono text-muted-foreground">
                                                {entry.guardianContact}
                                            </td>
                                            <td className="py-3 px-4">
                                                <div className="flex items-center gap-1.5 text-xs">
                                                    {isTimeIn ? (
                                                        <ArrowDownLeft className="size-3.5 text-primary" />
                                                    ) : (
                                                        <ArrowUpRight className="size-3.5 text-amber-500" />
                                                    )}
                                                    <span>{isTimeIn ? 'Time In' : 'Time Out'}</span>
                                                    <span className="text-muted-foreground">({formatTime(entry.scannedAt)})</span>
                                                </div>
                                            </td>
                                            <td className="py-3 px-4">
                                                {isSent && (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-emerald-500/20 bg-emerald-500/10 text-emerald-600 font-bold text-[10px] dark:text-emerald-400 gap-1"
                                                    >
                                                        <CheckCircle2 className="size-3" /> Sent
                                                    </Badge>
                                                )}
                                                {isPending && (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-amber-500/20 bg-amber-500/10 text-amber-600 font-bold text-[10px] dark:text-amber-400 gap-1"
                                                    >
                                                        <Loader2 className="size-3 animate-spin" /> Pending
                                                    </Badge>
                                                )}
                                                {isFailed && (
                                                    <Badge
                                                        variant="outline"
                                                        className="border-destructive/20 bg-destructive/10 text-destructive font-bold text-[10px] gap-1"
                                                    >
                                                        <AlertCircle className="size-3" /> Failed
                                                    </Badge>
                                                )}
                                            </td>
                                            <td className="py-3 px-6 text-right">
                                                {(isFailed || isPending) && (
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => handleRetry(entry.id)}
                                                        disabled={retryingId === entry.id}
                                                        className="h-7 text-[10px] font-bold gap-1 px-2.5"
                                                    >
                                                        {retryingId === entry.id ? (
                                                            <Loader2 className="size-3 animate-spin" />
                                                        ) : (
                                                            <RefreshCw className="size-3" />
                                                        )}
                                                        Retry
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
