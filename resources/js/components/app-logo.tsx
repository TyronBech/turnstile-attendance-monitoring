import { useUiTheme } from '@/hooks/use-ui-theme';

export default function AppLogo() {
    const { orgInitial, orgName, palette, rgb } = useUiTheme();
    const fallbackInitial = (orgInitial || orgName
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 3)
        .map((part) => part[0])
        .join('')
        .toUpperCase()) || 'TAM';

    return (
        <>
            <div
                className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md"
                style={{
                    backgroundColor: rgb(palette.secondary['50']),
                    color: rgb(palette.primary['700']),
                    boxShadow: `inset 0 0 0 1px ${rgb(palette.primary['200'], 0.85)}`,
                }}
            >
                <span className="text-sm font-black tracking-tight">
                    {fallbackInitial}
                </span>
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {orgName}
                </span>
                <span className="truncate text-xs text-sidebar-foreground/65">
                    {fallbackInitial}
                </span>
            </div>
        </>
    );
}
