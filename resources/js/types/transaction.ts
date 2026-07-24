export type TransactionStatus = 'processing' | 'approved' | 'denied';

export type StatusOption = {
    value: TransactionStatus;
    label: string;
};

export type TransactionListItem = {
    id: number;
    created_at: string | null;
    amount: string;
};

export type TransactionDetail = TransactionListItem & {
    cpf: string;
    status: TransactionStatus;
    status_label: string;
    document: {
        name: string;
        download_url: string;
    } | null;
    created_by: {
        id: number;
        name: string;
    };
};

export type TransactionPaginator = {
    data: TransactionListItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
