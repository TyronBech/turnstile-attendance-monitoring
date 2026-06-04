import {
    AlertCircle,
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Loader2,
    XCircle,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import type { ImportError, ImportProgressResponse } from '@/types/import';

type ImportProgressModalProps = {
    isOpen: boolean;
    onClose: () => void;
    importLogId: number | null;
    progressUrl: (id: number) => string;
    importType: string;
};

export function ImportProgressModal({
    isOpen,
    onClose,
    importLogId,
    progressUrl,
    importType,
}: ImportProgressModalProps) {
    const [progress, setProgress] = useState<ImportProgressResponse | null>(
        null,
    );
    const [isErrorsExpanded, setIsErrorsExpanded] = useState(false);
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    // Adjust state when importLogId changes during render to avoid useEffect warning
    const [prevImportLogId, setPrevImportLogId] = useState<number | null>(null);

    if (importLogId !== prevImportLogId) {
        setPrevImportLogId(importLogId);
        setProgress(null);
        setIsErrorsExpanded(false);
    }

    const fetchProgress = useCallback(async () => {
        if (importLogId === null) {
            return;
        }

        try {
            const response = await fetch(progressUrl(importLogId));

            if (!response.ok) {
                return;
            }

            const data = (await response.json()) as ImportProgressResponse;
            setProgress(data);

            if (data.finished && intervalRef.current !== null) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
        } catch {
            // Silently retry on next interval
        }
    }, [importLogId, progressUrl]);

    useEffect(() => {
        if (!isOpen || importLogId === null) {
            return;
        }

        // Initial fetch deferred to avoid synchronous setState warning inside effect
        const timer = setTimeout(() => {
            void fetchProgress();
        }, 0);

        // Poll every 2 seconds
        intervalRef.current = setInterval(() => {
            void fetchProgress();
        }, 2000);

        return () => {
            clearTimeout(timer);

            if (intervalRef.current !== null) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
        };
    }, [isOpen, importLogId, fetchProgress]);

    const getStatusIcon = () => {
        if (progress === null || !progress.finished) {
            return (
                <Loader2 className="h-6 w-6 animate-spin text-blue-500" />
            );
        }

        if (progress.status === 'completed') {
            return <CheckCircle2 className="h-6 w-6 text-emerald-500" />;
        }

        if (progress.status === 'completed_with_errors') {
            return <AlertCircle className="h-6 w-6 text-amber-500" />;
        }

        return <XCircle className="h-6 w-6 text-red-500" />;
    };

    const getStatusText = (): string => {
        if (progress === null) {
            return 'Preparing import...';
        }

        if (!progress.finished) {
            return 'Processing import...';
        }

        if (progress.status === 'completed') {
            return 'Import completed successfully!';
        }

        if (progress.status === 'completed_with_errors') {
            return `Import completed with ${progress.failedRows} error(s).`;
        }

        return 'Import failed.';
    };

    const progressPercentage = progress?.progress ?? 0;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && progress?.finished && onClose()}>
            <DialogContent
                className="sm:max-w-md"
                onPointerDownOutside={(e) => {
                    if (!progress?.finished) {
                        e.preventDefault();
                    }
                }}
                onEscapeKeyDown={(e) => {
                    if (!progress?.finished) {
                        e.preventDefault();
                    }
                }}
            >
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        {getStatusIcon()}
                        <span>{importType} Import</span>
                    </DialogTitle>
                    <DialogDescription>{getStatusText()}</DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Progress Bar */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                Progress
                            </span>
                            <span className="font-medium tabular-nums">
                                {progressPercentage}%
                            </span>
                        </div>
                        <div className="h-3 w-full overflow-hidden rounded-full bg-secondary">
                            <div
                                className={cn(
                                    'h-full rounded-full transition-all duration-500 ease-out',
                                    progress?.status === 'failed'
                                        ? 'bg-red-500'
                                        : progress?.status ===
                                            'completed_with_errors'
                                          ? 'bg-amber-500'
                                          : 'bg-blue-500',
                                )}
                                style={{ width: `${progressPercentage}%` }}
                            />
                        </div>
                    </div>

                    {/* Stats */}
                    {progress !== null && (
                        <div className="grid grid-cols-3 gap-3">
                            <div className="rounded-lg border p-3 text-center">
                                <div className="text-lg font-semibold tabular-nums">
                                    {progress.totalRows}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    Total Rows
                                </div>
                            </div>
                            <div className="rounded-lg border p-3 text-center">
                                <div className="text-lg font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                                    {progress.processedRows}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    Processed
                                </div>
                            </div>
                            <div className="rounded-lg border p-3 text-center">
                                <div
                                    className={cn(
                                        'text-lg font-semibold tabular-nums',
                                        progress.failedRows > 0
                                            ? 'text-red-600 dark:text-red-400'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {progress.failedRows}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                    Failed
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Errors Section */}
                    {progress !== null &&
                        progress.errors.length > 0 &&
                        progress.finished && (
                            <div className="space-y-2">
                                <button
                                    type="button"
                                    onClick={() =>
                                        setIsErrorsExpanded(!isErrorsExpanded)
                                    }
                                    className="flex w-full items-center justify-between rounded-md p-2 text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30"
                                >
                                    <span>
                                        View {progress.errors.length} Error(s)
                                    </span>
                                    {isErrorsExpanded ? (
                                        <ChevronUp className="h-4 w-4" />
                                    ) : (
                                        <ChevronDown className="h-4 w-4" />
                                    )}
                                </button>
                                {isErrorsExpanded && (
                                    <div className="max-h-40 space-y-1 overflow-y-auto rounded-md border p-2">
                                        {progress.errors.map(
                                            (
                                                error: ImportError,
                                                idx: number,
                                            ) => (
                                                <div
                                                    key={idx}
                                                    className="rounded-sm px-2 py-1 text-xs text-red-700 odd:bg-red-50/50 dark:text-red-300 dark:odd:bg-red-950/20"
                                                >
                                                    <span className="font-medium">
                                                        Row {error.row}:
                                                    </span>{' '}
                                                    {error.message}
                                                </div>
                                            ),
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                </div>

                <DialogFooter>
                    <Button
                        onClick={onClose}
                        disabled={!progress?.finished}
                        className="w-full"
                        variant={
                            progress?.status === 'failed'
                                ? 'destructive'
                                : 'default'
                        }
                    >
                        {progress?.finished ? 'Close' : 'Processing...'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
