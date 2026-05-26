import { Head, Link, usePoll } from '@inertiajs/react';
import { LogOut } from 'lucide-react';
import { useEffect, useState  } from 'react';
import type {CSSProperties} from 'react';
import { useUiTheme } from '@/hooks/use-ui-theme';
import { logout } from '@/routes';

type AttendancePanel = {
    id: number | string;
    slot: number;
    state: 'active' | 'idle' | 'waiting';
    isRecent: boolean;
    name: string;
    role: string;
    gradeSection: string | null;
    profileImage: string | null;
    action: 'IN' | 'OUT' | null;
    actionLabel: string;
    timeLabel: string;
};

type AttendanceDisplayProps = {
    panels: AttendancePanel[];
    tickerItems: string[];
};

const timeFormatter = new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
});

const dateFormatter = new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
});

type PanelBackgroundImageProps = {
    imageUrl: string | null;
    name: string;
};

function PanelBackgroundImage({ imageUrl, name }: PanelBackgroundImageProps) {
    const [hasImageError, setHasImageError] = useState(false);

    if (! imageUrl || hasImageError) {
        return null;
    }

    return (
        <img
            src={imageUrl}
            alt={name}
            className="absolute inset-0 h-full w-full object-cover object-[center_18%]"
            onError={() => {
                setHasImageError(true);
            }}
        />
    );
}

export default function AttendanceDisplay({
    panels,
    tickerItems,
}: AttendanceDisplayProps) {
    const { orgInitial, orgName, theme } = useUiTheme();
    const [now, setNow] = useState(() => new Date());

    usePoll(3000, {
        only: ['panels'],
    }, {
        keepAlive: true,
    });

    useEffect(() => {
        const clockIntervalId = window.setInterval(() => {
            setNow(new Date());
        }, 1000);

        return () => {
            window.clearInterval(clockIntervalId);
        };
    }, []);

    const marqueeGroups = [tickerItems, tickerItems, tickerItems];

    return (
        <>
            <Head title="Attendance Display" />

            <div
                className="attendance-display h-[100dvh] w-full overflow-hidden text-white"
                style={
                    {
                        '--attendance-header': theme.themePalette.primary['600'],
                        '--attendance-header-deep': theme.themePalette.primary['700'],
                        '--attendance-accent': theme.themePalette.tertiary['500'],
                        '--attendance-idle': '36 36 36',
                        '--attendance-idle-border': '58 58 58',
                    } as CSSProperties
                }
            >
                <div className="flex h-full min-h-0 flex-col overflow-hidden">
                    <header className="flex flex-col justify-between gap-1.5 border-b border-black/15 bg-[rgb(var(--attendance-header-deep))] px-[clamp(0.75rem,1vw,1.2rem)] py-[clamp(0.32rem,0.5vw,0.55rem)] text-white md:flex-row md:items-center">
                        <div className="flex items-center gap-2">
                            <div className="group relative flex items-center">
                                {theme.logoUrl ? (
                                    <div className="flex h-[clamp(2rem,2.7vw,2.6rem)] w-[clamp(2rem,2.7vw,2.6rem)] items-center justify-center overflow-hidden">
                                        <img
                                            src={theme.logoUrl}
                                            alt={orgName}
                                            className="h-full w-full object-contain"
                                        />
                                    </div>
                                ) : (
                                    <div className="flex h-[clamp(1.8rem,2.45vw,2.35rem)] w-[clamp(1.8rem,2.45vw,2.35rem)] items-center justify-center rounded-xl bg-black/20 text-[clamp(0.8rem,1.15vw,0.95rem)] font-black tracking-tight text-white">
                                        {orgInitial}
                                    </div>
                                )}

                                <Link
                                    href={logout()}
                                    as="button"
                                    className="absolute top-1/2 left-full z-10 flex min-w-max -translate-y-1/2 items-center gap-2 rounded-2xl border border-white/15 bg-black/90 px-4 py-2 text-[0.72rem] font-bold uppercase tracking-[0.16em] text-white opacity-0 shadow-[0_14px_28px_rgba(0,0,0,0.28)] backdrop-blur-sm transition duration-150 pointer-events-none -ml-1 group-hover:pointer-events-auto group-hover:translate-x-1 group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:translate-x-1 group-focus-within:opacity-100"
                                >
                                    Log out
                                    <LogOut className="size-3.5 shrink-0" />
                                </Link>
                            </div>

                            <div>
                                <p className="text-[clamp(0.98rem,1.8vw,1.55rem)] font-black tracking-tight">{orgName}</p>
                                <p className="text-[clamp(0.5rem,0.66vw,0.68rem)] uppercase tracking-[0.24em] text-white/75">
                                    RFID Attendance Monitoring System
                                </p>
                            </div>
                        </div>

                        <div className="text-left md:text-right">
                            <p className="text-[clamp(1.32rem,2.85vw,2.2rem)] font-black tabular-nums">{timeFormatter.format(now)}</p>
                            <p className="mt-0.5 text-[clamp(0.9rem,1.35vw,1.25rem)] font-semibold text-white/85">{dateFormatter.format(now)}</p>
                        </div>
                    </header>

                    <main className="attendance-panels min-h-0 flex-1 bg-black/25">
                        {panels.map((panel) => {
                            const isWaiting = panel.state === 'waiting';

                            return (
                                <article
                                    key={panel.id}
                                    className="attendance-panel relative flex min-h-0 min-w-0 flex-col overflow-hidden border-black/10 px-[clamp(0.65rem,0.95vw,1rem)] py-[clamp(0.85rem,1.2vw,1.25rem)]"
                                    style={{
                                        backgroundColor: 'rgb(var(--attendance-idle))',
                                    }}
                                >
                                    {!isWaiting && panel.profileImage ? (
                                        <>
                                            <PanelBackgroundImage imageUrl={panel.profileImage} name={panel.name} />
                                            <div
                                                className="absolute inset-0"
                                                style={{
                                                    background:
                                                        'linear-gradient(180deg, rgba(20, 10, 10, 0) 0%, rgba(20, 10, 10, 0) 56%, rgba(20, 10, 10, 0.54) 76%, rgba(12, 8, 8, 0.96) 100%)',
                                                }}
                                            />
                                        </>
                                    ) : (
                                        <>
                                            <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.045),transparent_38%),linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0))]" />
                                            {theme.logoUrl ? (
                                                <div className="absolute inset-0 flex items-center justify-center">
                                                    <img
                                                        src={theme.logoUrl}
                                                        alt=""
                                                        aria-hidden="true"
                                                        className="h-[44%] w-[44%] object-contain opacity-[0.11] saturate-75 brightness-110 contrast-90"
                                                    />
                                                </div>
                                            ) : (
                                                <div className="absolute inset-0 flex items-center justify-center">
                                                    <div className="text-[clamp(4rem,10vw,7rem)] font-black tracking-tight text-white/[0.05]">
                                                        {orgInitial}
                                                    </div>
                                                </div>
                                            )}
                                            <div className="absolute inset-x-0 top-[24%] text-center text-[clamp(0.52rem,0.7vw,0.72rem)] font-bold uppercase tracking-[0.45em] text-white/22">
                                                Awaiting Next Tap
                                            </div>
                                        </>
                                    )}

                                    <div className="relative mt-auto min-w-0 pt-[clamp(2.5rem,5vw,3.5rem)]">
                                        <p className="mt-[clamp(0.5rem,0.9vw,1rem)] text-[clamp(1rem,1.9vw,2.1rem)] font-black leading-[0.95] break-words">
                                            {panel.name}
                                        </p>
                                        <div className="mt-[clamp(0.35rem,0.7vw,0.8rem)] flex items-center justify-between gap-[clamp(0.5rem,0.8vw,0.9rem)]">
                                            {panel.gradeSection ? (
                                                <p className="min-w-0 text-[clamp(0.68rem,1vw,1.05rem)] font-medium text-white/82">
                                                    {panel.gradeSection}
                                                </p>
                                            ) : null}
                                            <span
                                                className="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[clamp(0.5rem,0.68vw,0.72rem)] font-bold uppercase tracking-[0.22em] text-white"
                                                style={{
                                                    backgroundColor: 'rgb(var(--attendance-header-deep) / 0.92)',
                                                }}
                                            >
                                                {panel.role}
                                            </span>
                                        </div>

                                        <div className="mt-[clamp(0.85rem,1.4vw,1.8rem)] border-t border-white/15 pt-[clamp(0.7rem,1vw,1.2rem)]">
                                            <div className="flex items-end justify-between gap-[clamp(0.5rem,0.8vw,0.9rem)]">
                                                <p className="text-[clamp(0.92rem,1.35vw,1.5rem)] font-semibold tabular-nums text-right">
                                                    {panel.timeLabel}
                                                </p>
                                                <p className="text-[clamp(0.52rem,0.72vw,0.72rem)] font-bold uppercase tracking-[0.38em] text-white/60">
                                                    {panel.actionLabel}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </main>

                    <footer className="flex flex-col gap-4 border-t border-white/10 bg-black/50 px-[clamp(0.9rem,1.4vw,1.75rem)] py-[clamp(0.7rem,1vw,1rem)] text-[clamp(0.68rem,0.95vw,0.9rem)] text-white/75 md:flex-row md:items-center">
                        <div className="shrink-0 font-semibold text-white/60">{orgName}</div>

                        <div className="min-w-0 flex-1 overflow-hidden">
                            <div className="attendance-ticker-track">
                                {marqueeGroups.map((group, groupIndex) => (
                                    <div key={groupIndex} className="attendance-ticker-group">
                                        {group.map((phrase, phraseIndex) => (
                                            <span key={`${phrase}-${groupIndex}-${phraseIndex}`} className="attendance-ticker-item">
                                                {phrase}
                                            </span>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="shrink-0 text-right">
                            <div className="font-semibold text-white/55">Powered by OwlQuery</div>
                        </div>
                    </footer>
                </div>
            </div>
        </>
    );
}
