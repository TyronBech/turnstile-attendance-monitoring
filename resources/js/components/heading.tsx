import { useUiTheme } from '@/hooks/use-ui-theme';

export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    const { palette, rgb } = useUiTheme();

    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <h2
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'text-xl font-semibold tracking-tight'
                }
                style={{
                    color: rgb(palette.primary['800']),
                }}
            >
                {title}
            </h2>
            {description && (
                <p
                    className="text-sm"
                    style={{
                        color: rgb(palette.primary['500']),
                    }}
                >
                    {description}
                </p>
            )}
        </header>
    );
}
