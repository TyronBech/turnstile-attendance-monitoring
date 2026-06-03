import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { useAppearance } from '@/hooks/use-appearance';
import { useUiTheme } from '@/hooks/use-ui-theme';

type StatCardProps = {
    title: string;
    value: number | string;
    subtitle?: string;
    icon: LucideIcon;
    themeColor?: 'primary' | 'secondary' | 'tertiary';
    isDestructive?: boolean;
};

/**
 * KPI stat card for the dashboard header row.
 * Renders a numeric value with a descriptive label and icon.
 */
export function StatCard({
    title,
    value,
    subtitle,
    icon: Icon,
    themeColor = 'primary',
    isDestructive = false,
}: StatCardProps) {
    const { palette, rgb } = useUiTheme();
    const { resolvedAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';

    // Set colors depending on appearance mode
    let iconBg = isDark ? rgb(palette[themeColor]['400'], 0.15) : rgb(palette[themeColor]['500'], 0.1);
    let iconColor = isDark ? rgb(palette[themeColor]['300']) : rgb(palette[themeColor]['600']);
    let borderColor = isDark ? rgb(palette[themeColor]['800']) : rgb(palette[themeColor]['200']);

    if (isDestructive) {
        iconBg = 'rgba(239, 68, 68, 0.1)';
        iconColor = 'rgb(239, 68, 68)';
        borderColor = isDark ? 'rgba(239, 68, 68, 0.25)' : 'rgba(239, 68, 68, 0.2)';
    }

    return (
        <Card
            className="relative overflow-hidden transition-shadow hover:shadow-md border bg-card text-card-foreground"
            style={{ borderColor }}
        >
            <CardContent className="flex items-center gap-4">
                <div
                    className="flex size-12 shrink-0 items-center justify-center rounded-xl font-semibold"
                    style={{ backgroundColor: iconBg, color: iconColor }}
                >
                    <Icon className="size-6" />
                </div>
                <div className="min-w-0">
                    <p className="text-muted-foreground truncate text-xs font-medium uppercase tracking-wide">
                        {title}
                    </p>
                    <p className="text-2xl font-bold tabular-nums text-foreground">{value}</p>
                    {subtitle ? (
                        <p className="text-muted-foreground truncate text-xs">{subtitle}</p>
                    ) : null}
                </div>
            </CardContent>
        </Card>
    );
}
