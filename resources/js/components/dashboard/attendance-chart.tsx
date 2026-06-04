import { Bar, BarChart, CartesianGrid, Legend, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useAppearance } from '@/hooks/use-appearance';
import { useUiTheme } from '@/hooks/use-ui-theme';
import type { HourlyDataPoint } from '@/types/dashboard';

type AttendanceChartProps = {
    data: HourlyDataPoint[];
};

export function AttendanceChart({ data }: AttendanceChartProps) {
    const { palette, rgb } = useUiTheme();
    const { resolvedAppearance } = useAppearance();
    const hasData = data.some((d) => d.timeIn > 0 || d.timeOut > 0);

    const isDark = resolvedAppearance === 'dark';
    const borderColor = isDark ? rgb(palette.primary['800']) : rgb(palette.primary['200']);

    return (
        <Card className="flex flex-col h-full min-h-[400px] border bg-card text-card-foreground" style={{ borderColor }}>
            <CardHeader className="pb-2">
                <CardTitle>Attendance Trends</CardTitle>
                <CardDescription>Hourly breakdown of Time Ins and Time Outs today</CardDescription>
            </CardHeader>
            <CardContent className="flex-1 min-h-0 pb-4">
                {!hasData ? (
                    <div className="flex h-full items-center justify-center text-sm text-muted-foreground py-20">
                        No scans recorded today.
                    </div>
                ) : (
                    <div className="h-[300px] w-full mt-4">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart
                                data={data}
                                margin={{
                                    top: 10,
                                    right: 10,
                                    left: -20,
                                    bottom: 0,
                                }}
                            >
                                <CartesianGrid strokeDasharray="3 3" vertical={false} className="stroke-muted/40" />
                                <XAxis
                                    dataKey="hour"
                                    tickLine={false}
                                    axisLine={false}
                                    className="text-xs fill-muted-foreground"
                                    dy={10}
                                />
                                <YAxis
                                    tickLine={false}
                                    axisLine={false}
                                    className="text-xs fill-muted-foreground"
                                    allowDecimals={false}
                                    dx={-5}
                                />
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: 'var(--color-popover)',
                                        borderColor: 'var(--color-border)',
                                        borderRadius: 'var(--radius-lg)',
                                        color: 'var(--color-popover-foreground)',
                                    }}
                                    cursor={{ fill: 'var(--color-muted)', opacity: 0.1 }}
                                />
                                <Legend
                                    verticalAlign="top"
                                    height={36}
                                    iconType="circle"
                                    iconSize={8}
                                    wrapperStyle={{ fontSize: '12px', paddingBottom: '10px' }}
                                />
                                <Bar
                                    name="Time In"
                                    dataKey="timeIn"
                                    fill="var(--color-primary)"
                                    radius={[4, 4, 0, 0]}
                                    maxBarSize={40}
                                />
                                <Bar
                                    name="Time Out"
                                    dataKey="timeOut"
                                    fill="var(--color-amber-500)"
                                    radius={[4, 4, 0, 0]}
                                    maxBarSize={40}
                                />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
