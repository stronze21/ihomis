<div class="container max-w-xl mx-auto mt-5">
    <div class="flex justify-between mb-3 align-middle">
        <div class="form-control">
            <label class="cursor-pointer label">
                <span class="text-lg font-bold uppercase label-text">Show returned items</span>
                <input type="checkbox" class="ml-2 checkbox checkbox-primary" wire:model="view_returns" />
            </label>
        </div>
        <button class="btn btn-sm btn-primary" id="btnPrint" onclick="printMe()">Print</button>
    </div>
    <div id="print" class="bg-white w-box-border">
        <div class="p-2">
            <div class="flex flex-col text-xs/4">
                <h5 class="mb-0 text-2xl text-left"><strong class="uppercase">*{{ $pcchrgcod }}*</strong></h5>
                <div class="flex flex-col text-center whitespace-nowrap">
                    <div>MMMHMC-A-PHB-QP-005 Form 1 Rev 0 Charge Slip</div>
                    <div>MARIANO MARCOS MEM HOSP. MED CTR</div>
                    <div>CHARGE SLIP</div>
                    <div class="font-bold">{{ $pcchrgcod }}</div>
                </div>
                <div class="flex flex-col text-left whitespace-nowrap">
                    <div>Dep't./Section: <span
                            class="font-semibold">{{ $prescription->employee->dept->deptname ?? '' }}</span></div>
                    <div>Date/Time: <span
                            class="font-semibold">{{ date('F j, Y h:i A', strtotime($rxo_header->dodate)) }}</span>
                    </div>
                    <div>Patient's Name: <span class="font-semibold">{{ $rxo_header->patient->fullname() }}</span></div>
                    <div>Hosp Number: <span class="font-semibold">{{ $rxo_header->patient->hpercode }}</span></div>
                    <div>Ward:
                        @if ($toecode == 'ADM' or $toecode == 'OPDAD' or $toecode == 'ERADM')
                            <span class="font-semibold">{{ $wardname->wardname }}</span>
                        @endif
                        <span
                            class="font-semibold">{{ $prescription && $prescription->adm_pat_room ? $prescription->adm_pat_room->ward->wardname : 'N/A' }}
                            / {{ $toecode }}</span>
                    </div>

                    <div>Ordering Physician: <span
                            class="font-semibold">{{ $prescription && $prescription->adm_pat_room ? 'Dr. ' . $prescription->employee->fullname() : 'N/A' }}</span>
                    </div>
                </div>
            </div>
            <table class="w-full text-xs/4">
                <thead class="border border-black">
                    <tr class="border-b-2 border-b-black">
                        <th class="text-left">ITEM</th>
                        @if ($view_returns)
                            <th class="text-left">R. QTY</th>
                        @endif
                        <th class="w-20 text-right">QTY</th>
                        <th class="w-20 text-right">UNIT COST</th>
                        <th class="w-20 text-right">AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rxo as $item)
                        @php
                            $concat = implode(',', explode('_,', $item->dm->drug_concat));
                        @endphp
                        <tr class="border-t border-black border-x">
                            <td class="font-semibold text-wrap" colspan="4">{{ $concat }}</td>
                        </tr>
                        <tr class="border-black border-x">
                            <td class="text-xs text-wrap" colspan="4">{{ $item->charge->chrgdesc }}</td>
                        </tr>
                        <tr class="border-b border-black border-x">
                            @if ($view_returns)
                                <td class="text-right">{{ $item->returns->sum('qty') }}</td>
                            @endif
                            <td class="text-right" colspan="2">{{ number_format($item->qtyissued, 0) }}</td>
                            <td class="text-right">{{ $item->pchrgup }}</td>
                            <td class="text-right">{{ number_format($item->pcchrgamt, 2) }}</td>
                        </tr>
                        @php
                            if ($view_returns) {
                                $returned_qty += $item->returns->sum('qty');
                            }
                        @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr align="right" class="font-bold border border-t-2 border-black">
                        @if ($view_returns)
                            <td class="text-right ">{{ $returned_qty }} Item/s Returned</td>
                        @endif
                        <td colspan="2">{{ number_format((float) $rxo->sum('qtyissued') ?? 0) }} ITEMS</td>
                        <td colspan="2">TOTAL {{ number_format((float) $rxo->sum('pcchrgamt'), 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            <div class="flex flex-col py-0 my-0 text-left text-xs/4 whitespace-nowrap">
                <div>Issued by: {{ $rxo_header->employee ? $rxo_header->employee->fullname() : $rxo_header->entry_by }}
                </div>
                <div><span>Time: {{ \Carbon\Carbon::create($rxo_header->dodate)->format('h:m A') }}</span></div>
                <div><span>Verified by Nurse/N.A.: _________________________</span></div>
                <div><span>Received by Patient/Watcher: ____________________</span></div>
                <div class="mt-10 text-right justify-content-end"><span class="border-t border-black">Signature Over
                        Printed Name</span></div>
            </div>
        </div>
    </div>
    {{-- @if ($print)
        <button id="btnPrint" class="btn btn-secondary hidden-print" onclick="printMe('print')">Print</button>
    @endif --}}
</div>


@push('scripts')
    <script>
        function printMe() {
            var printContents = document.getElementById('print').innerHTML;
            var originalContents = document.body.innerHTML;

            document.body.innerHTML = printContents;

            window.print();

            document.body.innerHTML = originalContents;
            history.go(-1);
        }
    </script>
@endpush
