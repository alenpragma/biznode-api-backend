<?php
namespace App\Exports;

use App\Models\Package;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PackageExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Package::select('id', 'name', 'min_amount', 'max_amount', 'interest_rate', 'duration', 'return_type', 'active', 'created_at')->get();
    }

    public function headings(): array
    {
        return ['ID', 'Name', 'Min Amount', 'Max Amount', 'Interest Rate', 'Duration', 'Return Type', 'Active', 'Created At'];
    }
}
