import { useUiTheme } from '@/hooks/use-ui-theme';
import type { ReactNode } from 'react';

interface OrgFooterProps {
    maxWidth?: string;
    secondaryContent?: ReactNode;
}

export function OrgFooter({
    maxWidth = 'max-w-6xl',
    secondaryContent = 'Attendance system.',
}: OrgFooterProps) {
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
                <div className="text-white/72">{secondaryContent}</div>
            </div>
        </footer>
    );
}
