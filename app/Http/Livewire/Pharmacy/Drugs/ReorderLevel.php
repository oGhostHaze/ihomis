<?php

namespace App\Http\Livewire\Pharmacy\Drugs;

use App\Models\Pharmacy\Drugs\DrugStockReorderLevel;
use App\Models\Pharmacy\PharmLocation;
use App\Models\References\ChargeCode;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class ReorderLevel extends Component
{
    use LivewireAlert;

    protected $listeners = ['update_reorder'];
    public $search, $location_id;

    public function render()
    {

        $stocks = DB::select("SELECT pds.drug_concat, SUM(pds.stock_bal) as stock_bal,
                            (SELECT reorder_point
                                FROM pharm_drug_stock_reorder_levels as level
                                WHERE pds.dmdcomb = level.dmdcomb AND pds.dmdctr = level.dmdctr AND pds.loc_code = level.loc_code) as reorder_point,
                                pds.dmdcomb, pds.dmdctr
                            FROM pharm_drug_stocks as pds
                            JOIN hcharge ON pds.chrgcode = hcharge.chrgcode
                            WHERE pds.loc_code = " . $this->location_id . "
                                AND pds.drug_concat LIKE '%" . $this->search . "%'
                            GROUP BY pds.drug_concat, pds.loc_code, pds.dmdcomb, pds.dmdctr
                    ");

        $locations = PharmLocation::all();


        return view('livewire.pharmacy.drugs.reorder-level', [
            'stocks' => $stocks,
            'locations' => $locations,
        ]);
    }

    public function mount()
    {
        $this->location_id = session('pharm_location_id');
    }

    public function update_reorder($dmdcomb, $dmdctr, $reorder_point)
    {
        DrugStockReorderLevel::updateOrCreate([
            'dmdcomb' => $dmdcomb,
            'dmdctr' => $dmdctr,
            'loc_code' => $this->location_id,
        ], [
            'reorder_point' => $reorder_point,
            'user_id' => session('user_id'),
        ]);

        $this->alert('success', 'Reorder level updated');
    }
}
