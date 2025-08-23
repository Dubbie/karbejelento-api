<?php

namespace App\Imports;

use App\Http\Requests\Building\StoreBuildingRequest;
use App\Models\BuildingImport;
use App\Services\BuildingService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class BuildingsImport implements OnEachRow, WithHeadingRow, WithChunkReading, ShouldQueue
{
    private array $headerMap;

    public function __construct(
        protected BuildingImport $importRecord,
        protected BuildingService $buildingService
    ) {
        $this->headerMap = [
            'tarsashaz_neve'     => 'name',
            'iranyitoszam'       => 'postcode',
            'varos'              => 'city',
            'kozterulet_neve'    => 'street_name',
            'kozterulet_tipusa'  => 'street_type',
            'hazszam'            => 'street_number',
            'bankszamlaszam'     => 'account_number',
            'kotvenyszam'        => 'bond_number',
            'biztosito'          => 'insurer',
        ];
    }

    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();

        try {
            $rowData = $row->toArray();

            Log::info('Importing building row ' . $rowIndex, $rowData);

            $translatedData = [];
            // The keys in $rowData are already slugified by the library.
            foreach ($rowData as $slugifiedHeader => $value) {
                if (isset($this->headerMap[$slugifiedHeader])) {
                    $internalKey = $this->headerMap[$slugifiedHeader];
                    $translatedData[$internalKey] = $value;
                }
            }

            $rules = (new StoreBuildingRequest())->rules();
            unset($rules['customer_id']);

            $validatedData = Validator::make($translatedData, $rules)->validate();
            $validatedData['customer_id'] = $this->importRecord->customer_id;

            $this->buildingService->createBuilding($validatedData);
        } catch (ValidationException $e) {
            $flatErrors = Arr::flatten($e->errors());
            $currentErrors = $this->importRecord->errors ?? [];
            $newError = [$rowIndex => $flatErrors];

            $this->importRecord->update([
                'errors' => $currentErrors + $newError
            ]);
        } catch (Exception $e) {
            $currentErrors = $this->importRecord->errors ?? [];
            $newError = [$rowIndex => ['system' => $e->getMessage()]];

            $this->importRecord->update([
                'errors' => $currentErrors + $newError
            ]);
            Log::error('Building import failed for row ' . $rowIndex, ['exception' => $e]);
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
