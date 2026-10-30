<?php

declare(strict_types=1);

namespace App\DTOs\Accounting;

final class JournalLineData
{
    public function __construct(
        public string $chartOfAccountCode,
        public string $debit,
        public string $credit,
        public ?string $lineDescription = null,
    ) {
        // Normalisasi nilai ke 4 desimal.
        $this->debit = number_format((float) $debit, 4, '.', '');
        $this->credit = number_format((float) $credit, 4, '.', '');
    }

    public function isDebit(): bool
    {
        return \bccomp($this->debit, '0.0000', 4) === 1;
    }

    public function isCredit(): bool
    {
        return \bccomp($this->credit, '0.0000', 4) === 1;
    }
}
