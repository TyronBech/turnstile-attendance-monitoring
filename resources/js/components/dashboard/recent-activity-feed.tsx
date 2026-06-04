import { ArrowDownLeft, ArrowUpRight, Clock } from 'lucide-react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAppearance } from '@/hooks/use-appearance';
import { useInitials } from '@/hooks/use-initials';
import { useUiTheme } from '@/hooks/use-ui-theme';
import type { ActivityEntry } from '@/types/dashboard';

type RecentActivityFeedProps = {
    activity: ActivityEntry[];
};

export function RecentActivityFeed({ activity }: RecentActivityFeedProps) {
    const { palette, rgb } = useUiTheme();
    const { resolvedAppearance } = useAppearance();
    const getInitials = useInitials();

    const isDark = resolvedAppearance === 'dark';
    const borderColor = isDark ? rgb(palette.primary['800']) : rgb(palette.primary['200']);

    const formatTime = (isoString: string) => {
        try {
            const date = new Date(isoString);

            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
            });
        } catch {
            return '--:--:--';
        }
    };

    return (
        <Card className="flex flex-col h-full min-h-[400px] border bg-card text-card-foreground" style={{ borderColor }}>
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>Latest scans across all turnstiles</CardDescription>
                    </div>
                    <Badge variant="outline" className="flex items-center gap-1 font-mono text-[10px]">
                        <Clock className="size-3" /> Real-time
                    </Badge>
                </div>
            </CardHeader>
            <CardContent className="flex-1 overflow-y-auto px-0 py-2">
                {activity.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-muted-foreground py-20">
                        No activity recorded yet.
                    </div>
                ) : (
                    <div className="divide-y divide-border">
                        {activity.map((entry) => {
                            const isTimeIn = entry.action === 'IN';

                            return (
                                <div
                                    key={entry.id}
                                    className="flex items-center justify-between px-6 py-3 transition-colors hover:bg-muted/40"
                                >
                                    <div className="flex items-center gap-3 min-w-0">
                                        <Avatar className="size-9 border">
                                            {entry.profileImage ? (
                                                <AvatarImage src={entry.profileImage} alt={entry.userName} />
                                            ) : null}
                                            <AvatarFallback className="text-xs font-semibold bg-muted-foreground/10 text-foreground">
                                                {getInitials(entry.userName)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0">
                                            <p className="text-sm font-semibold truncate text-foreground">
                                                {entry.userName}
                                            </p>
                                            <p className="text-xs text-muted-foreground truncate">
                                                via <span className="font-medium text-foreground/80">{entry.turnstileName}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4 shrink-0">
                                        <span className="text-xs font-medium text-muted-foreground tabular-nums">
                                            {formatTime(entry.scannedAt)}
                                        </span>
                                        <Badge
                                            className="min-w-[80px] justify-center gap-1 font-bold text-xs"
                                            variant={isTimeIn ? 'default' : 'secondary'}
                                            style={
                                                isTimeIn
                                                    ? { backgroundColor: 'var(--color-primary)' }
                                                    : {
                                                          backgroundColor: 'var(--color-amber-500/10)',
                                                          color: 'var(--color-amber-600)',
                                                          borderColor: 'var(--color-amber-500/20)',
                                                          borderWidth: '1px',
                                                      }
                                            }
                                        >
                                            {isTimeIn ? (
                                                <>
                                                    <ArrowDownLeft className="size-3 stroke-[2.5]" />
                                                    Time In
                                                </>
                                            ) : (
                                                <>
                                                    <ArrowUpRight className="size-3 stroke-[2.5]" />
                                                    Time Out
                                                </>
                                            )}
                                        </Badge>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
