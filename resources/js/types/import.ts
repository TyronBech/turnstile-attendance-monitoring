export type ImportLogEntry = {
    id: number;
    original_filename: string;
    total_rows: number;
    processed_rows: number;
    failed_rows: number;
    status: ImportStatus;
    error_summary: ImportError[] | null;
    created_at: string;
};

export type ImportStatus =
    | 'pending'
    | 'processing'
    | 'completed'
    | 'completed_with_errors'
    | 'failed';

export type ImportError = {
    row: number;
    id_number?: string;
    employee_id?: string;
    message: string;
};

export type ImportProgressResponse = {
    id: number;
    totalRows: number;
    processedRows: number;
    failedRows: number;
    progress: number;
    status: ImportStatus;
    finished: boolean;
    errors: ImportError[];
};

export type ImportPageProps = {
    imports: ImportLogEntry[];
};
