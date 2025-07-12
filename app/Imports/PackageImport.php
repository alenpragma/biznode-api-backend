<?php

namespace App\Imports;

use App\Models\Package;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PackageImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Package([
            'name' => $row['name'],
            'min_amount' => $row['min_amount'],
            'max_amount' => $row['max_amount'],
            'interest_rate' => $row['interest_rate'],
            'duration' => $row['duration'],
            'return_type' => $row['return_type'],
            'active' => $row['active'],
        ]);
    }
}
