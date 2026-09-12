<div style="position:absolute; right:70px; top:130px; font-size:12.5px;">{{ $contract['contract_number'] }}</div>
<div style="position:absolute; right:70px; top:170px; font-size:12.5px;">{{ date('M d, Y', strtotime($contract['created_at'])) }}</div>
<div style="position:absolute; left:255px; top:205px; font-size:12.5px;">{{ $contract['station'] }}</div>
<div style="position:absolute; left:310px; top:235px; font-size:12.5px;">{{ $contract->Advertiser->advertiser_name }}</div>
<div style="position:absolute; left:45px; top:295px; font-size:12.5px;">{{ $contract->Agency->agency_name }}</div>
<div style="position:absolute; right:35px; top:295px; font-size:12.5px;">{{ $contract['product'] }}</div>
<div style="position:absolute; left:60px; top:415px; font-size:12.5px;"><pre>{{ $contract['detail'] }}</pre></div>

<div style="position:absolute; right:35px; bottom:390px; font-size:12.5px;">
    @if($contract['total_prod'] != 0.00){{ $contract['prod_cost'] }}
    @elseif($contract['prod_cost_vat'] == 'VATINC'){{ 'VAT INC.' }}@elseif($contract['prod_cost_vat'] == 'NONVAT'){{ 'NON VAT' }}
    @endif
</div>
<div style="position:absolute; right:35px; bottom:375px; font-size:12.5px;">@if($contract['total_prod'] != 0.00){{ number_format($contract['total_prod'], 2, '.', ',') }}@endif</div>

<div style="position:absolute; left:170px; bottom:385px; font-size:12.5px;">@if($contract['manila_prod'] != 0.00){{ 'Manila '. number_format($contract['manila_prod'], 2, '.', ',') }}@endif</div>
<div style="position:absolute; left:320px; bottom:385px; font-size:12.5px;">@if($contract['cebu_prod'] != 0.00){{ 'Cebu '. number_format($contract['cebu_prod'], 2, '.', ',') }}@endif</div>
<div style="position:absolute; left:470px; bottom:385px; font-size:12.5px;">@if($contract['davao_prod'] != 0.00){{ 'Davao '. number_format($contract['davao_prod'], 2, '.', ',') }}@endif</div>

<div style="position:absolute; right:35px; bottom:360px; font-size:12.5px;">
    @if($contract['total_amount'] != 0.00){{ $contract['package_cost'] }}
    @elseif($contract['package_cost_vat'] == 'VATINC'){{ 'VAT INC.' }}
    @elseif($contract['package_cost_vat'] == 'NONVAT'){{ 'NON VAT' }}
    @endif
</div>
<div style="position:absolute; right:35px; bottom:345px; font-size:12.5px;">@if($contract['total_amount'] != 0.00){{ number_format($contract['total_amount'], 2, '.', ',') }}@endif</div>

<div style="position:absolute; left:170px; bottom:360px; font-size:12.5px;">@if($contract['manila_cash'] != 0.00){{ 'Manila '. number_format($contract['manila_cash'], 2, '.', ',') }}@endif</div>
<div style="position:absolute; left:320px; bottom:360px; font-size:12.5px;">@if($contract['cebu_cash'] != 0.00){{ 'Cebu '. number_format($contract['cebu_cash'], 2, '.', ',') }}@endif</div>
<div style="position:absolute; left:470px; bottom:360px; font-size:12.5px;">@if($contract['davao_cash'] != 0.00){{ 'Davao '. number_format($contract['davao_cash'], 2, '.', ',') }}@endif</div>

<div style="position:absolute; left:170px; bottom:330px; font-size:12.5px;">@if($contract['manila_ex'] != 0.00){{ 'Exdeal '. number_format($contract['manila_ex'], 2, '.', ',') }}@endif</div>
<div style="position:absolute; left:320px; bottom:330px; font-size:12.5px;">@if($contract['cebu_ex'] != 0.00){{ 'Exdeal '. number_format($contract['cebu_ex'], 2, '.', ',') }}@endif</div>
<div style="position:absolute; left:470px; bottom:330px; font-size:12.5px;">@if($contract['davao_ex'] != 0.00){{ 'Exdeal '. number_format($contract['davao_ex'], 2, '.', ',') }}@endif</div>

<div style="position:absolute; left:100px; bottom:227px; font-size:12.5px;">{{ date('m d Y', strtotime($contract['commencement'])) }}</div>
<div style="position:absolute; left:420px; bottom:227px; font-size:12.5px;">{{ date('m d Y', strtotime($contract['end_of_broadcast'])) }}</div>
<div style="position:absolute; left:180px; bottom:185px; font-size:12.5px;">{{ date('m d Y', strtotime($contract['bo_date'])) }}</div>
<div style="position:absolute; left:280px; bottom:185px; font-size:12.5px;">{{ $contract['bo_number'] }}</div>
<div style="position:absolute; left:35px; bottom:130px; font-size:12.5px;">{{ $contract->Advertiser->advertiser_name }}</div>
<div style="position:absolute; right:80px; bottom:130px; font-size:12.5px;">{{ $contract->Agency->agency_name }}</div>

<div style="position:absolute; left:45px; bottom:25px; font-size:12.5px;">{{ date("M d, Y") }}</div>
<div style="position:absolute; left:150px; bottom:25px; font-size:12.5px;">{{ 'AE / '.$contract->Employee->first_name[0] }}{{ $contract->Employee->middle_name[0] }}{{ $contract->Employee->last_name[0] }}</div>

<div style="position:absolute; right:55px; bottom:25px; font-size:12.5px;">
    @php
        $manilatocebu = strpos($contract['contract_number'],"BT02");
        $manilatodavao = strpos($contract['contract_number'],"CT02");
        $manilatocebudavao = strpos($contract['contract_number'],"BTCT02");
        $cebu = strpos($contract['contract_number'],"CEB");
        $davao = strpos($contract['contract_number'],"DAV");
    @endphp
    @if($manilatocebu != false || $manilatodavao != false || $manilatocebudavao != false)
        {{ 'CECILIA C. BARREIRO' }}
    @elseif($cebu != false)
        {{ 'ANTONIO V. BARREIRO JR.' }}
    @elseif($davao != false)
        {{ 'ANTONIO V. BARREIRO JR.' }}
    @else
        {{ 'LUIS MARI V. BARREIRO' }}
    @endif
</div>
