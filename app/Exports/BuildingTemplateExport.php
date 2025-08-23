<?php

namespace App\Exports;

use App\Constants\StreetType;
use App\Services\BuildingService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use ReflectionClass;

class BuildingTemplateExport extends DefaultValueBinder implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithEvents, WithCustomValueBinder
{
    protected array $streetTypes;

    public function __construct(protected BuildingService $buildingService)
    {
        $this->streetTypes = array_values((new ReflectionClass(StreetType::class))->getConstants());
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect([]);
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT, // Building name
            'B' => NumberFormat::FORMAT_TEXT, // Postcode
            'C' => NumberFormat::FORMAT_TEXT, // City
            'D' => NumberFormat::FORMAT_TEXT, // Street name
            'E' => NumberFormat::FORMAT_TEXT, // Street type
            'F' => NumberFormat::FORMAT_TEXT, // Street number
            'G' => NumberFormat::FORMAT_TEXT, // Bank account number
            'H' => NumberFormat::FORMAT_TEXT, // Bond Number
            'I' => NumberFormat::FORMAT_TEXT, // Insurer
        ];
    }

    public function headings(): array
    {
        return [
            'Társasház neve',    // A
            'Irányítószám',      // B
            'Város',             // C
            'Közterület neve',   // D
            'Közterület típusa', // E
            'Házszám',           // F
            'Bankszámlaszám',    // G
            'Kötvényszám',       // H
            'Biztosító',         // I
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $columnLetter = 'E'; // Column for "Közterület tipusa"

                $validation = $event->sheet->getDelegate()->getDataValidation("{$columnLetter}2");
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Hibás érték');
                $validation->setError('A megadott érték nem szerepel a közterület tipus listában.');
                $validation->setPromptTitle('Válassz a listából');
                $validation->setPrompt('Válaszd ki a megfelelő közterület tipust a legördülő listából');

                // The formula1 is the list of options
                $validation->setFormula1('"' . implode(',', $this->streetTypes) . '"');

                // Clone validation to all rows in that column
                $rowCount = $this->collection()->count() + 1;
                for ($i = 2; $i <= $rowCount; $i++) {
                    $event->sheet->getDelegate()->getCell("{$columnLetter}{$i}")->setDataValidation(clone $validation);
                }
            }
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getColumn() == 'H') { // Bond Number column
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        // For all other columns, use the parent method
        return parent::bindValue($cell, $value);
    }
}
