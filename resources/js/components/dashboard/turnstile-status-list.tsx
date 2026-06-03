import { Server } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAppearance } from '@/hooks/use-appearance';
import { useUiTheme } from '@/hooks/use-ui-theme';
import type { TurnstileStatus } from '@/types/dashboard';

type TurnstileStatusListProps = {
    turnstiles: TurnstileStatus[];
};

export function TurnstileStatusList({ turnstiles }: TurnstileStatusListProps) {
    const { palette, rgb } = useUiTheme();
    const { resolvedAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';
    const borderColor = isDark ? rgb(palette.primary['800']) : rgb(palette.primary['200']);

    const formatLastSeen = (isoString: string | null) => {
        if (!isoString) {
            return 'Never scanned';
        }

        try {
            const date = new Date(isoString);

            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            });
        } catch {
            return 'Invalid date';
        }
    };

    return (
        <Card className="flex flex-col h-full min-h-[350px] border bg-card text-card-foreground" style={{ borderColor }}>
            <CardHeader className="pb-2">
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>Turnstile Terminals</CardTitle>
                        <CardDescription>Status and health of connected devices</CardDescription>
                    </div>
                    <Server className="size-5 text-muted-foreground" />
                </div>
            </CardHeader>
            <CardContent className="flex-1 overflow-y-auto px-0 py-2">
                {turnstiles.length === 0 ? (
                    <div className="flex h-full items-center justify-center text-sm text-muted-foreground py-16">
                        No turnstiles configured.
                    </div>
                ) : (
                    <div className="divide-y divide-border">
                        {turnstiles.map((t) => {
                            const isOnline = t.isActive && t.isOnline;

                            return (
                                <div
                                    key={t.id}
                                    className="flex items-center justify-between px-6 py-4 transition-colors hover:bg-muted/40"
                                >
                                    <div className="min-w-0 flex-1 pr-4">
                                        <div className="flex items-center gap-2">
                                            <span className="font-semibold text-sm text-foreground truncate">
                                                {t.name}
                                            </span>
                                            <span className="text-[10px] font-mono text-muted-foreground">
                                                {t.ipAddress}
                                            </span>
                                        </div>
                                        <p className="text-xs text-muted-foreground truncate mt-0.5">
                                            {t.location} • Last seen: <span className="font-medium text-foreground/75">{formatLastSeen(t.lastSeenAt)}</span>
                                        </p>
                                    </div>

                                    <div className="flex items-center gap-3 shrink-0">
                                        {!t.isActive ? (
                                            <Badge variant="destructive" className="font-bold text-[10px] px-2 py-0.5">
                                                Inactive
                                            </Badge>
                                        ) : isOnline ? (
                                            <div className="flex items-center gap-2">
                                                <span className="relative flex h-2 w-2">
                                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                    <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                                </span>
                                                <Badge
                                                    variant="outline"
                                                    className="border-emerald-500/20 bg-emerald-500/10 text-emerald-600 font-bold text-[10px] dark:text-emerald-400 dark:border-emerald-500/30"
                                                >
                                                    Online
                                                </Badge>
                                            </div>
                                        ) : (
                                            <div className="flex items-center gap-2">
                                                <span className="h-2 w-2 rounded-full bg-muted-foreground/30"></span>
                                                <Badge
                                                    variant="outline"
                                                    className="border-muted-foreground/20 bg-muted-foreground/5 text-muted-foreground font-bold text-[10px]"
                                                >
                                                    Offline
                                                </Badge>
                                            </div>
                                        )}
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
