<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BreakdownController extends Controller
{
    // Unused

    public function getBreakdowns(Request $request)
    {
        $user_level = Auth::user()->Job->level;

        $sales_breakdown = Sale::with('Contract.Agency', 'Contract.Advertiser', 'Contract.Employee')
            ->where('bo_number', $request['bo_number'])
            ->get();

        foreach ($sales_breakdown as $breakdowns) {
            if ($breakdowns->station === 'Manila') {
                $breakdowns->station = '<div class="badge badge-primary text-center">Manila</div>';
            }

            if ($breakdowns->station === 'Cebu') {
                $breakdowns->station = '<div class="badge badge-warning text-center">Cebu</div>';
            }

            if ($breakdowns->station === 'Davao') {
                $breakdowns->station = '<div class="badge badge-dark text-center">Davao</div>';
            }

            $breakdowns->amount = '<div class="badge badge-primary">'.number_format($breakdowns->amount, 2).'</div>';

            $month = $this->convertNumberToDate($breakdowns->month);

            $breakdowns->month = $month;

            $breakdowns->type = $this->translateSaleType($breakdowns->type);

            $breakdowns->revision_number = $breakdowns->Archive->count() >= 1 ? $breakdowns->Archive->count() + 1 : 1;

            if ($user_level === '0') {
                $breakdowns->options = ''.
                    "<button type='button' class='btn btn-outline-dark dropdown-toggle' id='optionsDropdown' data-toggle='dropdown' aria-expanded='false'>".
                    '   Options'.
                    '</button>'.
                    "<div class='dropdown-menu' aria-labelledby='optionsDropdown'>".
                    "   <div class='dropdown-header'>Versions</div>".
                    "   <a href='#version-sale-modal' data-link='".route('sales.show')."' data-action='view' data-id='".$breakdowns->id."' modal='true' version='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-sort-numeric-down'></i>  View".
                    '   </a>'.
                    "   <div class='dropdown-header'>Actions</div> ".
                    "   <a href='#update-sale-modal' data-link='".route('sales.show')."' data-action='open' data-id='".$breakdowns->id."' modal='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-edit'></i>  Update".
                    '   </a>'.
                    "   <a href='#delete-sale-modal' data-link='".route('sales.show')."' data-action='open' data-id='".$breakdowns->id."' modal='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-trash-alt'></i>  Delete".
                    '   </a>'.
                    '</div>';
            } elseif ($user_level === '1') {
                $breakdowns->options = ''.
                    "<button type='button' class='btn btn-outline-dark dropdown-toggle' id='optionsDropdown' data-toggle='dropdown' aria-expanded='false'>".
                    '   Options'.
                    '</button>'.
                    "<div class='dropdown-menu' aria-labelledby='optionsDropdown'>".
                    "   <div class='dropdown-header'>Versions</div>".
                    "   <a href='#version-sale-modal' data-link='' data-action='open' data-id='".$breakdowns->id."' modal='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-sort-numeric-down'></i>  View".
                    '   </a>'.
                    "   <div class='dropdown-header'>Actions</div> ".
                    "   <a href='#update-sale-modal' data-link='".route('sales.show')."' data-action='open' data-id='".$breakdowns->id."' modal='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-edit'></i>  Update".
                    '   </a>'.
                    '</div>';
            } elseif ($user_level === '2') {
                $breakdowns->options = ''.
                    "<button type='button' class='btn btn-outline-dark dropdown-toggle' id='optionsDropdown' data-toggle='dropdown' aria-expanded='false'>".
                    '   Options'.
                    '</button>'.
                    "<div class='dropdown-menu' aria-labelledby='optionsDropdown'>".
                    "   <div class='dropdown-header'>Versions</div>".
                    "   <a href='#version-sale-modal' data-link='' data-action='open' data-id='".$breakdowns->id."' modal='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-sort-numeric-down'></i>  View".
                    '   </a>'.
                    '</div>';
            } elseif ($user_level === '3') {
                $breakdowns->options = ''.
                    "<button type='button' class='btn btn-outline-dark dropdown-toggle' id='optionsDropdown' data-toggle='dropdown' aria-expanded='false'>".
                    '   Options'.
                    '</button>'.
                    "<div class='dropdown-menu' aria-labelledby='optionsDropdown'>".
                    "   <div class='dropdown-header'>Versions</div>".
                    "   <a href='#version-sale-modal' data-link='' data-action='open' data-id='".$breakdowns->id."' modal='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-sort-numeric-down'></i>  View".
                    '   </a>'.
                    '</div>';
            } else {
                $breakdowns->options = ''.
                    "<button type='button' class='btn btn-outline-dark dropdown-toggle' id='optionsDropdown' data-toggle='dropdown' aria-expanded='false'>".
                    '   Options'.
                    '</button>'.
                    "<div class='dropdown-menu' aria-labelledby='optionsDropdown'>".
                    "   <div class='dropdown-header'>Versions</div>".
                    "   <a href='#version-sale-modal' data-link='' data-action='open' data-id='".$breakdowns->id."' modal='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-sort-numeric-down'></i>  View".
                    '   </a>'.
                    "   <div class='dropdown-header'>Actions</div> ".
                    "   <a href='#update-sale-modal' data-link='".route('sales.show')."' data-action='open' data-id='".$breakdowns->id."' modal='true' data-toggle='modal' class='dropdown-item'>".
                    "       <i class='fas fa-edit'></i>  Update".
                    '   </a>'.
                    '</div>';
            }
        }

        return response()->json($sales_breakdown);
    }
}
