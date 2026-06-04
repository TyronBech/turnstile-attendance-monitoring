import { Head, router } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock,
    FileWarning,
    Loader2,
    UserCog,
    XCircle,
} from 'lucide-react';
import { useCallback, useState } from 'react';
import { ImportProgressModal } from '@/components/imports/import-progress-modal';
import { ImportUploadCard } from '@/components/imports/import-upload-card';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import imports from '@/routes/imports';
import type { ImportLogEntry, ImportPageProps } from '@/types/import';

const statusConfig: Record<
    string,
    {
        label: string;
        variant: 'default' | 'secondary' | 'destructive' | 'outline';
        icon: typeof CheckCircle2;
    }
> = {
    pending: {
        label: 'Pending',
        variant: 'secondary',
        icon: Clock,
    },
    processing: {
        label: 'Processing',
        variant: 'default',
        icon: Loader2,
    },
    completed: {
        label: 'Completed',
        variant: 'default',
        icon: CheckCircle2,
    },
    completed_with_errors: {
        label: 'Partial',
        variant: 'outline',
        icon: FileWarning,
    },
    failed: {
        label: 'Failed',
        variant: 'destructive',
        icon: XCircle,
    },
};

export default function FacultiesStaffsImport({
    imports: importLogs,
}: ImportPageProps) {
    const [progressModalOpen, setProgressModalOpen] = useState(false);
    const [activeImportLogId, setActiveImportLogId] = useState<number | null>(
        null,
    );

    const handleImportStarted = useCallback((importLogId: number) => {
        setActiveImportLogId(importLogId);
        setProgressModalOpen(true);
    }, []);

    const handleProgressModalClose = useCallback(() => {
        setProgressModalOpen(false);
        setActiveImportLogId(null);
        router.reload({ only: ['imports'] });
    }, []);

    const getProgressUrl = useCallback((id: number): string => {
        return imports.facultiesStaffs.progress.url(id);
    }, []);

    return (
        <>
            <Head title="Import Faculties & Staffs" />

            <div className="px-6 py-10 md:px-10">
                <div className="space-y-6">
                        {/* Page Title */}
                        <div className="flex items-center gap-3">
                            <UserCog className="h-7 w-7 text-violet-600 dark:text-violet-400" />
                            <div>
                                <h1 className="text-2xl font-bold tracking-tight">
                                    Import Faculties & Staffs
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    Upload an Excel file to bulk import
                                    faculty and staff records.
                                </p>
                            </div>
                        </div>

                        {/* Upload Card */}
                        <ImportUploadCard
                            title="Upload Faculty & Staff Data"
                            description="Upload an Excel file (.xlsx, .xls) or CSV file containing faculty/staff information. Download the template first for the correct format."
                            storeUrl={imports.facultiesStaffs.store.url()}
                            templateUrl={imports.facultiesStaffs.template.url()}
                            onImportStarted={handleImportStarted}
                        />

                        {/* Import History */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Import History</CardTitle>
                                <CardDescription>
                                    Recent faculty & staff import activity
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {importLogs.length === 0 ? (
                                    <div className="py-8 text-center text-sm text-muted-foreground">
                                        No import history yet. Upload your first
                                        file to get started.
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead>
                                                <tr className="border-b text-left text-muted-foreground">
                                                    <th className="pb-3 pr-4 font-medium">
                                                        File
                                                    </th>
                                                    <th className="pb-3 pr-4 font-medium">
                                                        Status
                                                    </th>
                                                    <th className="pb-3 pr-4 text-right font-medium">
                                                        Total
                                                    </th>
                                                    <th className="pb-3 pr-4 text-right font-medium">
                                                        Processed
                                                    </th>
                                                    <th className="pb-3 pr-4 text-right font-medium">
                                                        Failed
                                                    </th>
                                                    <th className="pb-3 font-medium">
                                                        Date
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {importLogs.map(
                                                    (
                                                        log: ImportLogEntry,
                                                    ) => {
                                                        const config =
                                                            statusConfig[
                                                                log.status
                                                            ] ??
                                                            statusConfig.pending;
                                                        const StatusIcon =
                                                            config.icon;

                                                        return (
                                                            <tr
                                                                key={log.id}
                                                                className="border-b last:border-0"
                                                            >
                                                                <td className="py-3 pr-4">
                                                                    <span className="font-medium">
                                                                        {
                                                                            log.original_filename
                                                                        }
                                                                    </span>
                                                                </td>
                                                                <td className="py-3 pr-4">
                                                                    <Badge
                                                                        variant={
                                                                            config.variant
                                                                        }
                                                                        className="gap-1"
                                                                    >
                                                                        <StatusIcon className="h-3 w-3" />
                                                                        {
                                                                            config.label
                                                                        }
                                                                    </Badge>
                                                                </td>
                                                                <td className="py-3 pr-4 text-right tabular-nums">
                                                                    {
                                                                        log.total_rows
                                                                    }
                                                                </td>
                                                                <td className="py-3 pr-4 text-right tabular-nums text-emerald-600 dark:text-emerald-400">
                                                                    {
                                                                        log.processed_rows
                                                                    }
                                                                </td>
                                                                <td className="py-3 pr-4 text-right tabular-nums text-red-600 dark:text-red-400">
                                                                    {
                                                                        log.failed_rows
                                                                    }
                                                                </td>
                                                                <td className="py-3 text-muted-foreground">
                                                                    {
                                                                        log.created_at
                                                                    }
                                                                </td>
                                                            </tr>
                                                        );
                                                    },
                                                )}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

            {/* Progress Modal */}
            <ImportProgressModal
                isOpen={progressModalOpen}
                onClose={handleProgressModalClose}
                importLogId={activeImportLogId}
                progressUrl={getProgressUrl}
                importType="Faculty & Staff"
            />
        </>
    );
}
