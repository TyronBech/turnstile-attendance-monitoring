import { useUiTheme } from '@/hooks/use-ui-theme';

interface OrgFooterProps {
    maxWidth?: string;
}

export function OrgFooter({ maxWidth = 'max-w-6xl' }: OrgFooterProps) {
    const { palette, rgb, orgName } = useUiTheme();
    const primary700 = rgb(palette.primary['700']);

    return (
        <footer
            className="mt-auto border-t px-6 py-5 text-white md:px-10"
            style={{
                backgroundColor: primary700,
                borderColor: rgb(palette.primary['600']),
            }}
        >
            <div className={`mx-auto flex flex-col gap-2 text-sm md:flex-row md:items-center md:justify-between ${maxWidth}`}>
                <p className="font-medium text-white">{orgName}</p>
                <p className="text-white/72">Attendance system.</p>
            </div>
        </footer>
    );
}
