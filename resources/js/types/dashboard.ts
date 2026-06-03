export type DashboardStats = {
    currentlyTimedIn: number;
    totalScansToday: number;
    pendingSmsCount: number;
    failedSmsCount: number;
    sentSmsCount: number;
    activeTurnstiles: number;
    totalTurnstiles: number;
};

export type HourlyDataPoint = {
    hour: string;
    timeIn: number;
    timeOut: number;
};

export type ActivityEntry = {
    id: number;
    userName: string;
    action: 'IN' | 'OUT';
    scannedAt: string;
    turnstileName: string;
    profileImage: string | null;
};

export type TurnstileStatus = {
    id: number;
    name: string;
    location: string;
    ipAddress: string;
    isActive: boolean;
    isOnline: boolean;
    lastSeenAt: string | null;
};

export type SmsEntry = {
    id: number;
    userName: string;
    guardianContact: string;
    action: 'IN' | 'OUT';
    scannedAt: string;
    smsStatus: 'PENDING' | 'SENT' | 'FAILED';
};

export type DashboardProps = {
    stats: DashboardStats;
    hourlyBreakdown: HourlyDataPoint[];
    recentActivity: ActivityEntry[];
    turnstiles: TurnstileStatus[];
    smsEntries: SmsEntry[];
};
